#!/bin/bash

# Update Script for Attendance Application
# Use this to update your deployed Laravel application

set -e

APP_DIR="/var/www/attendance"
APP_USER="www-data"

echo "========================================"
echo "Updating Attendance Application"
echo "========================================"

cd $APP_DIR

# Backup database (recommended)
echo "Creating database backup..."
mysqldump -u attendance_user -pSecurePassword123! attendance_db > database_backup_$(date +%Y%m%d_%H%M%S).sql

# Pull latest code
echo "Pulling latest code from repository..."
sudo -u $APP_USER git pull origin main

# Install/update dependencies
echo "Installing composer dependencies..."
sudo -u $APP_USER composer install --optimize-autoloader --no-dev

# Update npm packages and rebuild assets
echo "Building frontend assets..."
sudo -u $APP_USER npm install
sudo -u $APP_USER npm run build

# Run migrations if any
echo "Running database migrations..."
php artisan migrate --force

# Clear caches
echo "Clearing application caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
echo "Restarting services..."
sudo systemctl restart php8.2-fpm nginx

echo ""
echo "========================================"
echo "Update completed successfully!"
echo "========================================"
