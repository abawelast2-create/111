#!/bin/bash
# Git Deployment Script - Upload application to Hostinger VPS

# Configuration
REMOTE_HOST="187.77.173.160"
REMOTE_USER="root"
REMOTE_PATH="/var/www/attendance"
LOCAL_PATH="."

echo "========================================"
echo "Git Deployment to VPS"
echo "========================================"

# Add Git remote if not exists
if ! git remote | grep -q "production"; then
    echo "[*] Adding production remote..."
    git remote add production ssh://${REMOTE_USER}@${REMOTE_HOST}${REMOTE_PATH}
else
    echo "[*] Production remote already configured"
fi

# Push to production
echo "[*] Pushing code to production..."
git push -u production main

if [ $? -eq 0 ]; then
    echo ""
    echo "[+] Code pushed successfully!"
    echo ""
    echo "[*] Next steps on the server:"
    echo "    1. cd /var/www/attendance"
    echo "    2. composer install --no-dev --optimize-autoloader"
    echo "    3. npm install && npm run build"
    echo "    4. php artisan migrate --force"
    echo "    5. php artisan optimize"
else
    echo "[!] Push failed!"
    exit 1
fi
