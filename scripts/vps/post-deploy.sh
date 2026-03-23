#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/attendance-laravel}"

cd "${APP_DIR}"

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

# Never rotate an existing APP_KEY. Generate only when missing.
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force || true
fi

# Keep deploy successful even when DB credentials are not ready yet.
if php artisan migrate --force; then
  echo "Database migrations completed."
else
  echo "WARNING: Database migrations failed. Verify DB_* settings in .env and MySQL user permissions."
fi
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart || true

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

echo "Post-deploy completed at $(date -u +%Y-%m-%dT%H:%M:%SZ)"