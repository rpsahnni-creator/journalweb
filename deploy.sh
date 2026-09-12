#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [[ ! -f .env ]]; then
    echo "Missing .env. Copy .env.production.example to .env and fill real values first."
    exit 1
fi

if ! grep -q '^APP_ENV=production' .env; then
    echo "Refuse to deploy: APP_ENV must be production in .env."
    exit 1
fi

if grep -q '^APP_DEBUG=true' .env; then
    echo "Refuse to deploy: APP_DEBUG must be false in production."
    exit 1
fi

if [[ -d .git ]] && command -v git >/dev/null 2>&1; then
    git pull
fi

if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
else
    echo "composer not found. Upload the vendor/ directory from a local install."
fi

if command -v npm >/dev/null 2>&1; then
    npm install
    npm run build
elif [[ ! -f public/build/manifest.json ]]; then
    echo "Node/npm is not available and public/build is missing."
    echo "On your PC run: npm install && npm run build"
    echo "Then upload the public/build folder."
    exit 1
fi

php artisan migrate --force
php artisan db:seed --force --class=ProductionSeeder
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan journal:check-production-safety

echo
echo "Deployment completed. Next, once: php artisan admin:create"
echo "Do not run php artisan migrate --seed on production."
