#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/attendance-laravel}"
PHP_VER="${2:-8.3}"

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y software-properties-common curl git unzip rsync ca-certificates lsb-release gnupg

if ! command -v php >/dev/null 2>&1; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update
fi

apt-get install -y \
  nginx \
  php${PHP_VER} php${PHP_VER}-fpm php${PHP_VER}-cli \
  php${PHP_VER}-common php${PHP_VER}-mbstring php${PHP_VER}-xml \
  php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-mysql \
  php${PHP_VER}-bcmath php${PHP_VER}-intl php${PHP_VER}-gd \
  php${PHP_VER}-sqlite3 php${PHP_VER}-redis \
  composer

mkdir -p "${APP_DIR}"
mkdir -p "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

id -u www-data >/dev/null 2>&1 || useradd -r -s /usr/sbin/nologin www-data
chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" || true
chmod -R ug+rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" || true

systemctl enable nginx
systemctl restart nginx

echo "Bootstrap completed for ${APP_DIR} with PHP ${PHP_VER}"