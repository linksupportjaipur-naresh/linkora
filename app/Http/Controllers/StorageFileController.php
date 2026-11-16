<?php

namespace App\Http\Controllers;

/**
 * Serves files from storage/app/public (profile photos, background images) on hosts where
 * `php artisan storage:link` can't create the public/storage symlink.
 * Only real files inside that folder are served; anything else is a 404.
 */
class StorageFileController extends Controller
{
    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

    public function __invoke(string $path)
    {
        $root = realpath(storage_path('app/public'));
        $file = $root ? realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path)) : false;
        $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));

        // must stay inside storage/app/public, be a file, not hidden, and be an image we create
        if (! $file || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0 || ! is_file($file)
            || strpos(basename($file), '.') === 0 || ! isset(self::TYPES[$ext])) {
            abort(404);
        }

        return response()->file($file, [
            'Content-Type'           => self::TYPES[$ext],
            'Cache-Control'          => 'public, max-age=2592000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
