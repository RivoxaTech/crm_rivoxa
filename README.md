# Rivoxa CRM

Two folders: `backend/` (Laravel API + PDF invoices) and `frontend/` (Vue app).

Needs: PHP 8.2+ (extensions: mbstring, dom, gd, fileinfo, pdo_mysql), Composer, MySQL running (XAMPP/Laragon), Node 18+.

## 1. Backend (terminal 1)

```
cd backend
composer install
php artisan rivoxa:install
php artisan serve
```

`rivoxa:install` creates the `rivoxa_crm` database if it does not exist, runs the migrations and creates the admin login.
It also loads the RS Home project (3 invoices, payments, expenses, renewals) so you can see the system working. Delete it from the Projects page when you no longer need it.

Database settings are in `backend/.env` (default: user `root`, empty password). Change `DB_USERNAME` / `DB_PASSWORD` if yours differ.

## 2. Frontend (terminal 2)

```
cd frontend
npm install
npm run dev
```

Open **http://localhost:5173**

Login: `admin@rivoxa.tech` / `Rivoxa@2026` (change it in the app under Settings, or in `backend/.env` before running the install).

## Updating an existing install

Copy the new files over, keeping `backend/vendor`, `backend/.env` and `frontend/node_modules`. Then in `backend`:

```
php artisan migrate
php artisan db:seed
```

`db:seed` is safe to repeat: it renames the partners to Usman and Mahrose, adds any missing Rivoxa services, and adds the RS Home project once (it never adds it twice).

## Notes

- Keep both terminals running. The frontend forwards `/api` to the backend at http://127.0.0.1:8000. If the backend runs elsewhere, edit `BACKEND_URL` in `frontend/.env`.
- Reset the admin password any time: `php artisan rivoxa:admin you@mail.com NewPassword`
- Run the tests: `php artisan test`
- Email sending needs the `MAIL_*` values in `backend/.env`.

## Going live (cPanel)

1. Backend: upload `backend/` and point a subdomain (for example `api.yourdomain.com`) at `backend/public`. Create the MySQL database and user in cPanel, edit `.env` (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `MAIL_*`), run `composer install --no-dev`, `php artisan rivoxa:install`, and make `storage/` and `bootstrap/cache/` writable.
2. Frontend: `BACKEND_URL` is for development only. For production, serve the built app and the API on the same domain so cookies work: run `npm run build`, upload `frontend/dist` to the main domain, and route `/api` and `/i` to the backend (reverse proxy or the same document root). Ask me and I will prepare this layout for your hosting.
3. Cron (daily, monthly auto-billing): `0 6 * * * php /path/backend/artisan invoices:recurring`
