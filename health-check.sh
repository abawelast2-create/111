#!/bin/bash

# Health Check Script - Monitor application health
# Schedule with cron: */5 * * * * /var/www/attendance/health-check.sh

APP_DIR="/var/www/attendance"
LOG_FILE="/var/log/attendance-health.log"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting health check..." >> $LOG_FILE

# Check if Nginx is running
if ! sudo systemctl is-active --quiet nginx; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Nginx is not running" >> $LOG_FILE
    sudo systemctl restart nginx
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Nginx restarted" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ Nginx running" >> $LOG_FILE
fi

# Check if PHP-FPM is running
if ! sudo systemctl is-active --quiet php8.2-fpm; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: PHP-FPM is not running" >> $LOG_FILE
    sudo systemctl restart php8.2-fpm
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] PHP-FPM restarted" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ PHP-FPM running" >> $LOG_FILE
fi

# Check if MySQL is running
if ! sudo systemctl is-active --quiet mysql; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: MySQL is not running" >> $LOG_FILE
    sudo systemctl restart mysql
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] MySQL restarted" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ MySQL running" >> $LOG_FILE
fi

# Check disk space
DISK_USAGE=$(df /var/www/attendance | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: Disk usage is ${DISK_USAGE}%" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ Disk usage: ${DISK_USAGE}%" >> $LOG_FILE
fi

# Check application logs for errors
if sudo tail -100 $APP_DIR/storage/logs/laravel.log | grep -q "ERROR\|CRITICAL"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: Errors found in application logs" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ Application logs clean" >> $LOG_FILE
fi

# HTTP check
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com --connect-timeout 5)
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ Application responding (HTTP $HTTP_STATUS)" >> $LOG_FILE
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Application not responding (HTTP $HTTP_STATUS)" >> $LOG_FILE
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Health check completed" >> $LOG_FILE
echo "" >> $LOG_FILE
