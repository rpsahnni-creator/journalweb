# Shared hosting deploy (cPanel)

Use this when the server has PHP and MySQL, but often **no Node.js** and sometimes **no Composer/Git**.

## 1. On this computer (before upload)

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Confirm `public/build/manifest.json` exists.

## 2. Server folders

Create a MySQL database in cPanel. Upload the project **above** `public_html` if possible:

```text
home/USER/journal-system/     ← full Laravel project
home/USER/public_html/        ← only the contents of public/  OR a subdomain DocumentRoot
```

**Preferred:** create subdomain `journal.srtc.ac.in` whose Document Root is:

```text
/home/USER/journal-system/public
```

Do **not** point the domain at the project root.

## 3. `.env` on the server

Copy `.env.production.example` to `.env` on the server (never upload your local `.env`).

Fill in:

- `APP_KEY` — run `php artisan key:generate --force` once on the server
- `APP_URL=https://journal.srtc.ac.in`
- `APP_ENV=production` and `APP_DEBUG=false`
- real `DB_*` values
- real `MAIL_*` SMTP values (password reset will not work until mail works)

Then:

```bash
php artisan config:cache
```

## 4. Permissions

Make these writable (0755 or 0775, owner = hosting user):

- `storage/`
- `storage/logs/`
- `storage/framework/`
- `bootstrap/cache/`

## 5. First-time commands (SSH or cPanel Terminal)

```bash
php artisan migrate --force
php artisan db:seed --force --class=ProductionSeeder
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan admin:create
php artisan journal:check-production-safety
```

Never run:

```bash
php artisan migrate --seed
php artisan db:seed
```

Those can install development sample articles. `ProductionSeeder` only adds roles, permissions, and journal identity. The admin account comes from `admin:create`.

## 6. Upload list when Node/Composer are missing on the server

From this PC, upload:

- `app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `public/` (including `public/build`), `resources/views/`, `routes/`, `storage/` (empty framework folders), `vendor/`
- `artisan`, `composer.json`, `composer.lock`, `.env` (server copy only)

Do not upload:

- local `.env`
- `node_modules/`
- `tests/` (optional)

## 7. SSL

In cPanel use AutoSSL / Let's Encrypt for `journal.srtc.ac.in`. Force HTTPS.

## 8. After each update

On this PC: `npm run build`, then upload changed PHP files and `public/build`.

If the server has SSH and Composer, you can run `bash deploy.sh` instead. It skips `git pull` / `npm` when those tools are missing, as long as `public/build` is already uploaded.
