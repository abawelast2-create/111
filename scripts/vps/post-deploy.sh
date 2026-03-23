#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/attendance-laravel}"

cd "${APP_DIR}"

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

php artisan key:generate --force || true
php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart || true

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

echo "Post-deploy completed at $(date -u +%Y-%m-%dT%H:%M:%SZ)"