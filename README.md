# Academic Journal Management System

Laravel 12 application for academic journal management. PHP 8.2+ is required (PHP 8.3+ recommended). This local machine currently runs PHP 8.2.12; the codebase is compatible with PHP 8.3.

## Requirements

- PHP 8.2+ (8.3+ preferred) with `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, and `xml`
- Composer 2
- Node.js 20+ and npm
- MySQL 8.x or MariaDB 10.6+
- Apache with `mod_rewrite`, or `php artisan serve` for local development

## Installation

1. Clone or open the project folder.

2. Copy the environment file and generate an application key:

```bash
copy .env.example .env
php artisan key:generate
```

On Linux/macOS use `cp .env.example .env`.

3. Set MySQL credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=journal_system
DB_USERNAME=root
DB_PASSWORD=
```

4. Create the database in MySQL or phpMyAdmin, then migrate and seed:

```bash
php artisan migrate --seed
```

5. Install PHP and front-end dependencies:

```bash
composer install
npm install
npm run build
```

## Run locally

Build assets once, then start the Laravel development server:

```bash
npm run build
php artisan serve
```

Open:

- Home: http://127.0.0.1:8000
- Log in: http://127.0.0.1:8000/login
- Register: http://127.0.0.1:8000/register
- Health check: http://127.0.0.1:8000/health

Development admin (local only):

- Email: `admin@example.com`
- Password: `password`

The password is stored as a one-way hash, never as plain text.

For Vite hot reload during UI work, run this in a second terminal:

```bash
npm run dev
```

Apache: point the virtual host document root to the `public` directory.

## Tests

```bash
php artisan test
```

## Production Deployment

Production on [journal.srtc.ac.in](https://journal.srtc.ac.in) uses these files:

- [`.env.production.example`](.env.production.example) — production environment placeholders (no secrets)
- [`deploy.sh`](deploy.sh) — idempotent deploy script (`set -e`, stops on first failure)
- [`docs/deploy/apache-vhost.conf`](docs/deploy/apache-vhost.conf) — sample Apache virtual host (`DocumentRoot` is `/public`)

1. Copy `.env.production.example` to `.env` and fill in real values.
2. Run `deploy.sh`.
3. Run `php artisan admin:create` once.
4. Point DNS and enable SSL.

