# Deployment Scripts Documentation

## Overview
This directory contains all necessary scripts and configurations for deploying the Attendance Laravel application to Hostinger VPS.

## Files Description

### 1. `deploy.sh` - Initial Deployment
**Purpose:** Complete setup of the VPS with all required software and the application.

**What it does:**
- Updates system packages
- Installs PHP 8.2 with required extensions
- Installs Nginx, MySQL, Redis
- Installs Node.js and npm
- Installs Composer
- Clones/sets up the application
- Creates directories and sets permissions

**How to use:**
```bash
sudo bash deploy.sh
```

**Time required:** 15-20 minutes

---

### 2. `nginx.conf` - Web Server Configuration
**Purpose:** Nginx server block configuration for optimal performance and security.

**Features:**
- SSL/TLS support (HTTPS)
- Gzip compression
- Security headers
- Static file caching
- PHP-FPM integration
- Protection against directory listing
- Denial of access to sensitive files

**How to use:**
```bash
# Copy to Nginx sites-available
sudo cp nginx.conf /etc/nginx/sites-available/attendance

# Edit with your domain
sudo nano /etc/nginx/sites-available/attendance

# Update these lines:
# - server_name your-domain.com www.your-domain.com;
# - root /var/www/attendance/public;

# Enable the site
sudo ln -s /etc/nginx/sites-available/attendance /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart service
sudo systemctl restart nginx
```

---

### 3. `setup_database.sql` - Database Setup
**Purpose:** Creates database and user for the application.

**What it does:**
- Creates `attendance_db` database
- Creates `attendance_user` with password
- Grants all privileges
- Sets proper character set

**How to use:**
```bash
# Method 1: Direct execution
sudo mysql < setup_database.sql

# Method 2: Interactive
sudo mysql
mysql> source setup_database.sql;
mysql> exit;
```

**Note:** Change the password in `.env` after running this.

---

### 4. `supervisor-attendance.conf` - Queue Worker
**Purpose:** Manages background job processing using Supervisor.

**Required if:**
- Application uses Laravel queues
- Sending emails in background
- Processing reports
- Storing logs

**How to use:**
```bash
# Install Supervisor
sudo apt-get install supervisor

# Copy configuration
sudo cp supervisor-attendance.conf /etc/supervisor/conf.d/

# Start monitoring
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start attendance:*

# Check status
sudo supervisorctl status
```

---

### 5. `update.sh` - Application Updates
**Purpose:** Safely update application with zero downtime.

**What it does:**
- Creates database backup
- Pulls latest code from Git
- Installs dependencies (Composer, npm)
- Builds frontend assets
- Runs database migrations
- Clears all caches

**How to use:**
```bash
cd /var/www/attendance
sudo bash update.sh
```

**When to use:**
- After pushing new code to repository
- Deploying new features
- Applying security patches

---

### 6. `backup.sh` - Database & Files Backup
**Purpose:** Creates automated backups of database and application files.

**What it does:**
- Exports MySQL database to .sql.gz
- Compresses application files to .tar.gz
- Stores in `/var/backups/attendance/`
- Removes backups older than 30 days

**How to use:**
```bash
# Manual backup
sudo bash backup.sh

# Scheduled backup (daily at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /var/www/attendance/backup.sh
```

**Backup locations:**
- Database: `/var/backups/attendance/database_TIMESTAMP.sql.gz`
- Files: `/var/backups/attendance/app_files_TIMESTAMP.tar.gz`

**Restore database:**
```bash
gunzip < database_TIMESTAMP.sql.gz | mysql -u attendance_user -p attendance_db
```

---

### 7. `health-check.sh` - Application Monitoring
**Purpose:** Monitors application health and restarts services if needed.

**Checks:**
- Nginx status and auto-restart
- PHP-FPM status and auto-restart
- MySQL status and auto-restart
- Disk usage warning (>90%)
- Application logs for errors
- HTTP response code (200, 301, 302)

**How to use:**
```bash
# Manual check
sudo bash health-check.sh

# Scheduled every 5 minutes
sudo crontab -e
# Add: */5 * * * * /var/www/attendance/health-check.sh

# View results
tail -f /var/log/attendance-health.log
```

---

### 8. `.env.production` - Production Environment Variables
**Purpose:** Template for production environment configuration.

**Key settings:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- Database credentials
- Cache driver: Redis
- Queue driver: Redis
- Session timeout: 120 minutes
- Mail configuration (if needed)

**How to use:**
```bash
# Copy template to .env
cp .env.production .env

# Edit with actual values
nano .env

# Generate app key if not present
php artisan key:generate --force
```

---

### 9. `DEPLOYMENT_GUIDE_AR.md` - Detailed Arabic Guide
**Purpose:** Step-by-step Arabic instructions for deployment.

**Covers:**
- VPS information
- File upload methods
- Nginx configuration
- Database setup
- Environment variables
- SSL certificate installation
- Security hardening
- Troubleshooting

---

### 10. `QUICK_DEPLOY.md` - Quick Reference
**Purpose:** Quick step-by-step checklist for fast deployment.

**Best for:**
- First-time deployment
- Quick reference
- Troubleshooting shortcuts

---

## Typical Deployment Flow

### First Time Setup
1. SSH into VPS: `ssh root@187.77.173.160`
2. Upload files to `/var/www/attendance`
3. Run `deploy.sh`
4. Configure `nginx.conf`
5. Setup database using `setup_database.sql`
6. Create `.env` and configure
7. Run migrations
8. Install SSL certificate

### Regular Operations
- **Weekly:** Run `backup.sh` manually or via cron
- **Every update:** Run `update.sh`
- **Always running:** `health-check.sh` in background
- **Queue workers:** Managed by `supervisor-attendance.conf`

### Emergency Recovery
```bash
# If services crash
sudo bash /var/www/attendance/health-check.sh

# Restore from backup
gunzip < database_TIMESTAMP.sql.gz | mysql -u attendance_user -p attendance_db
tar -xzf app_files_TIMESTAMP.tar.gz -C /

# Full redeployment
cd /var/www/attendance
sudo bash deploy.sh
```

---

## Cron Job Setup

Add these to your crontab (`sudo crontab -e`):

```bash
# Daily backup at 2 AM
0 2 * * * /var/www/attendance/backup.sh >> /var/log/backup.log 2>&1

# Hourly health check
0 * * * * /var/www/attendance/health-check.sh >> /var/log/health-check.log 2>&1

# Schedule tasks every minute
* * * * * cd /var/www/attendance && php artisan schedule:run >> /dev/null 2>&1

# Queue worker (if using supervisor instead)
# Managed by supervisor-attendance.conf
```

---

## Important Notes

⚠️ **Security:**
- Always change default passwords in `.env`
- Remove `APP_DEBUG=true` in production
- Use strong database passwords
- Enable SSH key authentication
- Keep system updated

⚠️ **Backups:**
- Test restore process regularly
- Keep backups off-server if possible
- Encrypt sensitive backups

⚠️ **Monitoring:**
- Regularly check application logs
- Monitor disk space
- Monitor resource usage
- Set up alerts for critical errors

---

## Troubleshooting

### Application not loading
```bash
# Check all services
sudo systemctl status nginx php8.2-fpm mysql redis-server

# Check logs
tail -f /var/log/nginx/attendance_error.log
tail -f /var/www/attendance/storage/logs/laravel.log
```

### Database connection error
```bash
# Test connection
sudo mysql -u attendance_user -p
# Enter password from .env

# Check database exists
SHOW DATABASES;

# Select database
USE attendance_db;

# Show tables
SHOW TABLES;
```

### Permission errors
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/attendance

# Fix permissions
sudo chmod 755 /var/www/attendance
sudo chmod 775 /var/www/attendance/storage /var/www/attendance/bootstrap/cache
```

---

## Support

For issues, check:
1. [DEPLOYMENT_GUIDE_AR.md](DEPLOYMENT_GUIDE_AR.md) - Detailed Arabic guide
2. [QUICK_DEPLOY.md](QUICK_DEPLOY.md) - Quick reference
3. Application logs in `/var/www/attendance/storage/logs/`
4. Nginx logs in `/var/log/nginx/`
5. System logs: `sudo journalctl -xe`

---

**Last Updated:** March 2026
**Tested on:** Ubuntu 24.04 LTS, Hostinger VPS KVM 2
