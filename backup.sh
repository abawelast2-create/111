#!/bin/bash

# Backup Script for Attendance Application
# Schedule with cron: 0 2 * * * /var/www/attendance/backup.sh

set -e

APP_DIR="/var/www/attendance"
BACKUP_DIR="/var/backups/attendance"
DATE=$(date +%Y%m%d_%H%M%S)
HOSTNAME=$(hostname)

# Create backup directory if not exists
mkdir -p $BACKUP_DIR

echo "[$(date)] Starting backup..."

# Backup database
echo "Backing up database..."
mysqldump -u attendance_user -pSecurePassword123! attendance_db | gzip > $BACKUP_DIR/database_$DATE.sql.gz

# Backup application files
echo "Backing up application files..."
tar -czf $BACKUP_DIR/app_files_$DATE.tar.gz \
    --exclude='storage/logs' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='vendor' \
    -C /var/www attendance

# Keep only last 30 days of backups
find $BACKUP_DIR -type f -mtime +30 -delete

echo "[$(date)] Backup completed successfully!"
echo "Database backup: $BACKUP_DIR/database_$DATE.sql.gz"
echo "Files backup: $BACKUP_DIR/app_files_$DATE.tar.gz"

# Optional: Send to remote storage
# aws s3 cp $BACKUP_DIR s3://your-bucket/backups/ --recursive
