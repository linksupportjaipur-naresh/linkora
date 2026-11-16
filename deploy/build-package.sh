#!/usr/bin/env bash
# Builds an upload-ready package for shared hosting (no SSH needed on the server).
#
#   bash deploy/build-package.sh
#
# Output (in dist/):
#   linkora-app.zip       → extract into /home/<user>/linkora       (the app, with vendor/ installed)
#   linkora-public.zip    → extract into /home/<user>/public_html   (the website files)
#
# Takes the files in this folder that git would track (so .env, vendor, uploads and logs are
# never included), installs production dependencies, and splits public/ out for public_html.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/dist"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "→ Copying project files (respects .gitignore: no .env, vendor, uploads, logs)…"
mkdir -p "$WORK/app" "$WORK/public_html"
( cd "$ROOT" && git ls-files -co --exclude-standard | while IFS= read -r f; do
    [ -f "$f" ] || continue                       # skip files deleted locally
    mkdir -p "$WORK/app/$(dirname "$f")" && cp -p "$f" "$WORK/app/$f"
  done )
rm -f "$WORK/app/.env"                             # belt and braces: never ship a local .env

echo "→ Installing production dependencies (composer --no-dev)…"
( cd "$WORK/app" && composer install --no-dev --optimize-autoloader --no-interaction --quiet )

echo "→ Splitting public/ into public_html/…"
cp -R "$WORK/app/public/." "$WORK/public_html/"
cp "$WORK/app/deploy/shared-hosting/index.php" "$WORK/public_html/index.php"
rm -rf "$WORK/public_html/storage"                     # created on the server by storage:link (or served by the app)
rm -rf "$WORK/app/tests" "$WORK/app/dist" "$WORK/app/.phpunit.result.cache"

# empty runtime folders the app expects
for d in storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
  mkdir -p "$WORK/app/$d"
done

echo "→ Zipping…"
mkdir -p "$OUT"
rm -f "$OUT/linkora-app.zip" "$OUT/linkora-public.zip"
# PHP's ZipArchive writes "/" paths on every OS (PowerShell's zip uses "\", which breaks on Linux servers)
php "$ROOT/deploy/zip-dir.php" "$WORK/app" "$OUT/linkora-app.zip"
php "$ROOT/deploy/zip-dir.php" "$WORK/public_html" "$OUT/linkora-public.zip"

echo "✓ Done:"
ls -lh "$OUT"/*.zip
