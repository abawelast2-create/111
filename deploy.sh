#!/bin/bash

# Laravel Attendance Application Deployment Script
# For Ubuntu 24.04 LTS on Hostinger VPS

set -e  # Exit on error

echo "========================================"
echo "Laravel Attendance App - Deployment"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration variables
APP_DIR="/var/www/attendance"
APP_USER="www-data"
DOMAIN="your-domain.com"  # Change this
DB_NAME="attendance_db"
DB_USER="attendance_user"

# Step 1: Update system
echo -e "${YELLOW}[1/12] Updating system packages...${NC}"
sudo apt-get update
sudo apt-get upgrade -y

# Step 2: Install required packages
echo -e "${YELLOW}[2/12] Installing PHP and dependencies...${NC}"
sudo apt-get install -y \
    software-properties-common \
    curl \
    wget \
    git \
    zip \
    unzip \
    nginx \
    mysql-server \
    redis-server \
    certbot \
    python3-certbot-nginx

# Step 3: Install PHP 8.2 with required extensions
echo -e "${YELLOW}[3/12] Installing PHP 8.2...${NC}"
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-json \
    php8.2-curl \
    php8.2-gd \
    php8.2-zip \
    php8.2-intl \
    php8.2-redis

# Step 4: Enable PHP-FPM
echo -e "${YELLOW}[4/12] Enabling PHP-FPM...${NC}"
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Step 5: Install Composer
echo -e "${YELLOW}[5/12] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Step 6: Install Node.js and npm
echo -e "${YELLOW}[6/12] Installing Node.js and npm...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Step 7: Create application directory
echo -e "${YELLOW}[7/12] Creating application directory...${NC}"
sudo mkdir -p $APP_DIR
sudo chown -R $APP_USER:$APP_USER $APP_DIR

# Step 8: Clone or upload application
echo -e "${YELLOW}[8/12] Setting up application files...${NC}"
# If using git clone:
# sudo -u $APP_USER git clone https://your-repo-url.git $APP_DIR
# For now, assume files are already uploaded via SFTP

# Step 9: Install PHP dependencies
echo -e "${YELLOW}[9/12] Installing PHP dependencies...${NC}"
cd $APP_DIR
sudo -u $APP_USER composer install --optimize-autoloader --no-dev

# Step 10: Install Node dependencies and build assets
echo -e "${YELLOW}[10/12] Building frontend assets...${NC}"
sudo -u $APP_USER npm install
sudo -u $APP_USER npm run build

# Step 11: Generate app key and setup environment
echo -e "${YELLOW}[11/12] Configuring application...${NC}"
sudo -u $APP_USER cp .env.example .env 2>/dev/null || true
sudo -u $APP_USER php artisan key:generate --force
sudo -u $APP_USER chmod 775 storage bootstrap/cache
sudo -u $APP_USER php artisan migrate --force
sudo -u $APP_USER php artisan optimize

# Step 12: Configure Nginx
echo -e "${YELLOW}[12/12] Configuring Nginx...${NC}"
# Nginx config will be created separately. See nginx.conf file.

# Final messages
echo ""
echo -e "${GREEN}========================================"
echo "Deployment completed!"
echo "========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Upload nginx.conf to: /etc/nginx/sites-available/attendance"
echo "2. Enable site: sudo ln -s /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/"
echo "3. Test nginx: sudo nginx -t"
echo "4. Restart nginx: sudo systemctl restart nginx"
echo "5. Setup SSL: sudo certbot --nginx -d your-domain.com"
echo "6. Configure MySQL database in .env file"
echo "7. Test application at: http://your-domain.com"
echo ""
