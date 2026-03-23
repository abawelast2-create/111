# QUICK DEPLOYMENT STEPS

## Prerequisites
- SSH access to VPS: `ssh root@187.77.173.160`
- Domain name configured
- File transfer method (SFTP, Git, or MCP)

## Step-by-Step

### 1. Connect to VPS
```bash
ssh root@187.77.173.160
```

### 2. Upload Application Files
Upload all project files to `/var/www/attendance` via SFTP or Git

### 3. Run Deployment Script
```bash
cd /var/www/attendance
chmod +x deploy.sh
sudo ./deploy.sh
```
*This takes 10-15 minutes*

### 4. Configure Nginx
```bash
sudo cp nginx.conf /etc/nginx/sites-available/attendance
sudo nano /etc/nginx/sites-available/attendance  # Edit domain names
sudo ln -s /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. Setup Database
```bash
sudo mysql < setup_database.sql
```

### 6. Configure .env File
```bash
cd /var/www/attendance
sudo nano .env
```

Update:
- `APP_ENV=production`
- `APP_URL=https://your-domain.com`
- `DB_DATABASE=attendance_db`
- `DB_USERNAME=attendance_user`
- `DB_PASSWORD=SecurePassword123!`

### 7. Migrate Database
```bash
cd /var/www/attendance
php artisan migrate --force
php artisan optimize
```

### 8. Install SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com
```

### 9. Final Permissions
```bash
cd /var/www/attendance
sudo chown -R www-data:www-data .
sudo chmod 775 storage bootstrap/cache
```

### 10. Test Application
Visit: `https://your-domain.com`

---

## Troubleshooting

### View Errors
```bash
tail -f /var/log/nginx/attendance_error.log
tail -f /var/www/attendance/storage/logs/laravel.log
```

### Check Services
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
```

### Restart Services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mysql
```

---

## Important Files Created
- `deploy.sh` - Full deployment automation
- `nginx.conf` - Nginx server configuration
- `setup_database.sql` - Database setup
- `supervisor-attendance.conf` - Queue worker config
- `DEPLOYMENT_GUIDE_AR.md` - Detailed Arabic guide

---

## After Deployment

1. **Change passwords** - SSH root, DB user, and any default app passwords
2. **Enable firewall** - `sudo ufw enable`
3. **Setup backups** - Configure automated backups in Hostinger panel
4. **Monitor logs** - Check `/var/log/nginx/` and Laravel logs regularly
5. **Update regularly** - Run `sudo apt-get update && upgrade` weekly

---

**Success! Your Laravel app is now live on Hostinger VPS**
