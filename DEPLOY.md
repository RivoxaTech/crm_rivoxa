# Put the CRM live on crm.rivoxa.tech (cPanel)

Already done: the subdomain `crm.rivoxa.tech` with document root `/crm/public`, and the GoDaddy A record.

## 1. PHP version
cPanel > **MultiPHP Manager** > tick `crm.rivoxa.tech` > choose **PHP 8.2 or newer** > Apply.
In **Select PHP Version > Extensions**, make sure these are ticked: `mbstring`, `pdo_mysql`, `gd`, `fileinfo`, `dom`, `xml`, `zip`.

## 2. Database
cPanel > **MySQL Database Wizard**:
1. Create a database named `crm` (cPanel makes it `rivoxate_crm`).
2. Create a user named `crm` with a strong password (it becomes `rivoxate_crm`).
3. Give the user **All Privileges**.

## 3. Upload
1. cPanel > **File Manager** > open the `crm` folder (create it inside your home folder if it is missing).
2. Upload `rivoxa-crm-live.zip` and **Extract** it there. You should now see `crm/artisan`, `crm/app`, `crm/public`.
3. Delete the zip.
4. In File Manager, turn on Settings > "Show Hidden Files" and open `crm/.env`. Fill in:
   - `DB_PASSWORD` (from step 2; change `DB_DATABASE` / `DB_USERNAME` if cPanel named them differently)
   - `ADMIN_PASSWORD` (the password you will log in with)
   - the `MAIL_*` lines if you want to send invoices by email

## 4. Install (cPanel > Terminal)
```
cd ~/crm
composer install --no-dev --optimize-autoloader
php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache
chmod -R 775 storage bootstrap/cache
```
If `composer` is not found, run `php ~/composer.phar install ...` or tell me and I will give you a vendor folder to upload instead.
If `php` is the wrong version, use `/opt/cpanel/ea-php82/root/usr/bin/php` instead of `php`.

## 5. HTTPS
cPanel > **SSL/TLS Status** > tick `crm.rivoxa.tech` > **Run AutoSSL**.
(The A record `crm` must already point to this server. Wait for it if AutoSSL complains.)

## 6. Open it
https://crm.rivoxa.tech
Login: `admin@rivoxa.tech` and the `ADMIN_PASSWORD` you set.

## 7. Monthly auto-billing (optional)
cPanel > **Cron Jobs** > once a day:
```
0 6 * * * /usr/local/bin/php /home/rivoxate/crm/artisan invoices:recurring
```

## Updating later
Change the frontend on your computer, then in `frontend` run `npm run publish`. Upload the changed files from `backend/public` (the `assets` folder and `spa.html`) to `crm/public`.
For backend changes upload the changed files and run `php artisan migrate --force` and `php artisan config:cache`.

## If something fails
- Blank page or 500: look at `crm/storage/logs/laravel.log`.
- "Permission denied" errors: run the `chmod` line again.
- Login says "page expired": make sure `APP_URL=https://crm.rivoxa.tech` and `SESSION_SECURE_COOKIE=true` and that you open the site with https.
