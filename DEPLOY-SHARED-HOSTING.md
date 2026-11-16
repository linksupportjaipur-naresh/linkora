# Linkora ko PHP hosting par live karna (Hostinger / cPanel)

Yeh guide shared hosting ke liye hai: Hostinger (hPanel), GoDaddy, Bluehost, ya koi bhi
cPanel hosting. Vercel PHP nahi chalata, isliye wahan `index.php` download ho jaati thi.

> Passwords ya secrets kabhi chat, GitHub ya screenshot mein mat daaliye. Woh sirf server ki `.env` file mein jaate hain.

---

## 0. Hosting lete waqt kya check karein

- **PHP 8.1** chun sakein. Yeh sabse safe hai; 7.4 aur 8.2 bhi chalte hain. PHP extensions: `bcmath ctype curl fileinfo gd mbstring openssl pdo_mysql tokenizer xml zip` (zyada-tar hosting par pehle se on hote hain).
- **MySQL database** milta ho.
- **SSH/Terminal** mile to sabse aasaan hai (Hostinger Premium/Business mein milta hai). Na mile to bhi chalega (Step 3B).
- **Free SSL** (Let's Encrypt / AutoSSL) ho.
- Domain hosting se juda ho.

---

## 1. Server par folder kaise rakhne hain

```
/home/<aapka-user>/
├── linkora/          ← poori app (public/ ko chhod kar sab kuch)
└── public_html/      ← sirf public/ ke andar ki files (website)
```

Is tarah `.env`, code aur uploads web se seedhe nahi khul sakte, jo security ke liye zaroori hai.
(Hostinger mein yeh `/home/<user>/domains/<domain>/public_html` hota hai. Tab `linkora/`
ko `/home/<user>/domains/<domain>/linkora` mein rakhiye, `public_html` ke bagal mein.)

---

## 2. Database banaiye

hPanel/cPanel → **MySQL Databases**:
1. Naya database banaiye, jaise `u123_linkora`.
2. Naya user aur ek **strong password** banaiye.
3. User ko database par **All Privileges** dijiye.

Teeno cheezein (database ka naam, user, password) Step 4 mein `.env` mein jaayengi.

---

## 3A. Code upload karna (SSH / Terminal ke saath)

```bash
cd ~
git clone https://github.com/<aapka-account>/linkora.git linkora
cd linkora
composer install --no-dev --optimize-autoloader
cp -R public/. ~/public_html/
cp deploy/shared-hosting/index.php ~/public_html/index.php
```

Hostinger mein `~/public_html` ki jagah `~/domains/<domain>/public_html` likhiye. Aur agar
`public_html` mein hosting ki default `index.html`/`default.php` pehle se ho, to use delete kar dijiye.

## 3B. Code upload karna (bina SSH ke)

Apne computer par (Git Bash mein, project folder se):

```bash
bash deploy/build-package.sh
```

Yeh `dist/` folder mein do zip banata hai, jisme `.env`, uploads aur logs nahi hote:

| Zip | Server par kahan extract karein |
|---|---|
| `dist/linkora-app.zip` | `/home/<user>/linkora/` |
| `dist/linkora-public.zip` | `/home/<user>/public_html/` |

hPanel/cPanel → **File Manager** → zip upload → **Extract**.

---

## 4. `.env` file banaiye (server par)

`linkora/.env.production.example` ki copy `linkora/.env` naam se banaiye aur har `<...>` bhariye:

| Setting | Kya daalna hai |
|---|---|
| `APP_URL` | `https://aapka-domain.com` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `PUBLIC_PATH` | public_html ka poora path, jaise `/home/u123/public_html` (File Manager mein upar dikhta hai) |
| `LINKORA_DOMAIN` | `aapka-domain.com` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Step 2 wale |
| `DB_HOST` | aksar `localhost` (hosting panel mein likha hota hai) |
| `MAIL_*` | Gmail app password, ya hosting ka email (code wale login ke liye zaroori) |
| `GOOGLE_*` | Google login ke liye (Step 7) |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | pehle admin ke liye **naya strong password** |

`APP_KEY` khaali chhod dijiye; agla step use bana dega.

---

## 5. Setup commands

**Terminal ho to** (`cd ~/linkora` ke baad):

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

- Agar `storage:link` error de (kuch hosting symlink allow nahi karti): koi baat nahi, app photos khud dikha deti hai.
- PHP version alag ho to `php` ki jagah `php8.1` likhiye. Hostinger mein `/opt/alt/php81/usr/bin/php` jaisa path hota hai.

**Terminal na ho to:**
- Database: apne computer ke XAMPP se phpMyAdmin → Export karke, hosting ke phpMyAdmin mein Import kar dijiye.
- `APP_KEY`: apne computer par `php artisan key:generate --show` chalaiye aur aaya hua key `.env` mein daaliye.
- Agar Export/Import kiya hai to `db:seed` ki zaroorat nahi, kyunki admin pehle se database mein hai.

---

## 6. SSL aur Cron

- **SSL:** hPanel → **SSL** → Install (free). Phir `.env` mein `FORCE_HTTPS=true` rakhiye.
- **Cron** (har raat backup + safai): hPanel/cPanel → **Cron Jobs** → har minute ke liye:

  ```
  cd /home/<user>/linkora && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## 7. Google login

Google Cloud Console → Credentials → aapka OAuth client → **Authorised redirect URIs** mein jodiye:
`https://aapka-domain.com/auth/google/callback`. Yahi value `.env` ke `GOOGLE_REDIRECT_URI` mein daaliye.

---

## 8. Check karein

- [ ] `https://aapka-domain.com` khulta hai (download nahi hota)
- [ ] `https://aapka-domain.com/admin` par admin login hota hai. Login ke baad admin password badal dijiye.
- [ ] Naye email se sign up karne par 6-digit code email mein aata hai
- [ ] Profile photo upload karne par dikhti hai
- [ ] Google se login hota hai
- [ ] Dashboard → QR Code ko phone se scan karne par live profile khulti hai

---

## Baad mein update karna

- **SSH ke saath:**

  ```bash
  cd ~/linkora && git pull && composer install --no-dev --optimize-autoloader
  php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
  cp -R public/assets ~/public_html/
  ```

- **Bina SSH:** `bash deploy/build-package.sh` chalaiye aur dono zip dobara extract kariye. Server ki `.env` aur `storage/app/public` (uploads) ko overwrite mat kariye.

## Kuch galat ho to

| Dikkat | Wajah / hal |
|---|---|
| Page download hota hai | PHP on nahi hai, ya galat folder mein upload hua. `public_html/index.php` hona chahiye |
| "500 Server Error" | `.env` galat hai, ya `storage`/`bootstrap/cache` writable nahi hain. Galti `linkora/storage/logs/laravel.log` mein dikhegi |
| CSS nahi dikhti | `public_html/assets` folder missing hai, ya `APP_URL` galat hai |
| Photos nahi dikhti | `PUBLIC_PATH` sahi kariye, aur `php artisan config:cache` dobara chalaiye |
| Login code email nahi aata | `MAIL_*` settings check kariye. Gmail ke liye app password chahiye |
