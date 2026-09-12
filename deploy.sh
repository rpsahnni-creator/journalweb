#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed successfully."
