# Deploying Linkora

This guide takes Linkora from XAMPP to a live Linux server with HTTPS.
It assumes Ubuntu 22.04/24.04 with Nginx, PHP-FPM and MySQL. Shared hosting
(cPanel/Hostinger) works too: see the notes at the end.

## 1. Server requirements

- PHP **7.4+** (8.1+ recommended; see "Upgrade note") with extensions:
  `bcmath ctype curl fileinfo gd json mbstring openssl pdo_mysql tokenizer xml zip`
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2, Git, `mysqldump` (for backups)
- A domain pointing (A record) at the server

## 2. Production database

```sql
CREATE DATABASE linkora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'linkora'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON linkora.* TO 'linkora'@'localhost';
FLUSH PRIVILEGES;
```

Use a dedicated user — never `root` with an empty password as on XAMPP.

## 3. Upload the code and install

```bash
cd /var/www
git clone <your-repo-url> linkora && cd linkora
composer install --no-dev --optimize-autoloader
cp .env.production.example .env      # then fill in every <...> value
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force          # creates the admin from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Log in at `/login/password` with the admin email and password, then change the
password under **Settings**.

## 4. Nginx

`/etc/nginx/sites-available/linkora`:

```nginx
server {
    listen 80;
    server_name your-domain www.your-domain;
    root /var/www/linkora/public;
    index index.php;
    client_max_body_size 5M;   # profile photos are up to 2 MB

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;   # match your PHP version
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/linkora /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 5. HTTPS (free, auto-renewing)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain -d www.your-domain --redirect
```

With `FORCE_HTTPS=true` the app always generates `https://` links, and HSTS is
sent on secure requests. Behind Cloudflare or a load balancer also set
`TRUSTED_PROXIES=*`.

## 6. Scheduler (daily backups + cleanup)

```bash
sudo crontab -u www-data -e
# add:
* * * * * cd /var/www/linkora && php artisan schedule:run >> /dev/null 2>&1
```

This runs `php artisan linkora:backup` every night at 02:30 (IST) and keeps the
last 14 dumps in `storage/app/backups`. **Copy backups off the server** too
(e.g. `rclone` to Google Drive/S3), and test a restore occasionally:

```bash
gunzip < storage/app/backups/linkora-YYYYMMDD-HHMMSS.sql.gz | mysql -u linkora -p linkora
```

## 7. Google sign-in

In Google Cloud Console → Credentials → your OAuth client, add the
authorised redirect URI `https://your-domain/auth/google/callback` and set the
same value in `GOOGLE_REDIRECT_URI`.

## 8. Updating later

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

## Launch checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` with https
- [ ] Strong DB password, dedicated DB user
- [ ] Admin password changed after first login
- [ ] Mail works: sign up with a real address and receive the 6-digit code
- [ ] Google sign-in redirect URI updated for the live domain
- [ ] `php artisan storage:link` done (profile photos show)
- [ ] Cron for `schedule:run` added; `php artisan linkora:backup` runs; backups copied off-server
- [ ] HTTPS certificate issued; `http://` redirects to `https://`
- [ ] Contact details and social links set in **Admin → Settings**
- [ ] Scan the QR code from **Dashboard → QR Code** with a phone: it must open the live profile

## Shared hosting (cPanel / Hostinger)

Step-by-step guide: **[DEPLOY-SHARED-HOSTING.md](DEPLOY-SHARED-HOSTING.md)**. In short:
the app goes in `~/linkora`, the contents of `public/` go in `public_html` with
`deploy/shared-hosting/index.php` as its `index.php`, and `PUBLIC_PATH` in `.env`
points at `public_html`. Without SSH, `bash deploy/build-package.sh` builds two
upload-ready zips. If the host blocks symlinks, uploaded photos are served by the
`/storage/{path}` route instead of `storage:link`.

Note: Vercel cannot run this app (it serves `index.php` as a download).

## Upgrade note

The project runs on Laravel 8 and supports PHP 7.4. Both are past their
official security-support dates; plan an upgrade to a current Laravel and PHP
8.2+ after launch.
