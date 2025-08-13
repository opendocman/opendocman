# OpenDocMan Email Queue Upgrade Guide

## Quick Start Guide for Version 1.4.5

This guide walks you through upgrading OpenDocMan to version 1.4.5, which introduces asynchronous email processing to dramatically improve file upload performance.

## Pre-Upgrade Checklist

### 1. Backup Your System
```bash
# Backup database
mysqldump -u username -p opendocman > opendocman_backup_$(date +%Y%m%d).sql

# Backup files
tar -czf opendocman_files_backup_$(date +%Y%m%d).tar.gz /path/to/opendocman/
```

### 2. Check Current Version
- Login to OpenDocMan as admin
- Go to Admin Panel → About section
- Note your current database version (should be 1.4.0 or 1.4.4)

### 3. Verify Requirements
- PHP 7.4 or higher
- MySQL 8+ or MariaDB 10.0+
- Web server write permissions
- Cron job capability

## Upgrade Process

### Step 1: Update Files
1. Download OpenDocMan 1.4.5
2. Extract files to your web server
3. Preserve your `application/configs/config.php` file

### Step 2: Run Database Upgrade
1. Navigate to: `http://yoursite.com/install/`
2. The installer will detect your current version
3. Click **"Upgrade from DB schema version 1.4.4"**
4. Wait for completion message

### Expected Output:
```
Creating email queue table...
Creating email queue statistics table...
Adding email queue settings...
Email queue management will be available to admin users...
Updating db version...
Database update from 1.4.4 to 1.4.5 complete.

Email Queue System Installed Successfully!
```

### Step 3: Configure Cron Job
Add this line to your crontab to process emails every 5 minutes:

```bash
# Edit crontab
crontab -e

# Add this line:
*/5 * * * * php /path/to/opendocman/application/cron/process_email_queue.php
```

For high-volume sites, process every minute:
```bash
* * * * * php /path/to/opendocman/application/cron/process_email_queue.php
```

### Step 4: Verify Installation
1. Login to admin panel
2. Look for **"Email Queue Status"** link in Reports section
3. Upload a test file and check queue processing
4. Verify emails are being sent

## Configuration Options

### Access Email Queue Settings
1. Go to **Admin Panel → Edit Settings**
2. Look for email queue configuration options:

| Setting | Default | Description |
|---------|---------|-------------|
| Email Queue Enabled | True | Turn queue processing on/off |
| Batch Size | 10 | Emails processed per batch |
| Retry Delay | 300 | Seconds between retry attempts |
| Max Attempts | 3 | Maximum retry attempts |
| Cleanup Days | 30 | Days to keep processed emails |

### Monitoring Queue Status
1. Go to **Admin Panel → Email Queue Status**
2. View pending, processing, sent, and failed emails
3. Manually retry failed emails if needed
4. Check daily statistics

## Performance Impact

### Before Upgrade (Synchronous Email)
- File upload: **1-2 minutes**
- Server blocked during email sending
- Poor user experience

### After Upgrade (Asynchronous Email)
- File upload: **5-15 seconds**
- Background email processing
- Improved user experience

## Troubleshooting

### Common Issues

#### 1. Cron Job Not Running
**Symptoms**: Emails stuck in "pending" status
**Solution**: 
```bash
# Test cron job manually
php /path/to/opendocman/application/cron/process_email_queue.php --verbose

# Check cron logs
tail -f /var/log/cron
```

#### 2. Database Connection Errors
**Symptoms**: Upgrade fails with database errors
**Solution**:
- Verify database credentials in `config.php`
- Check database user permissions
- Ensure database server is running

#### 3. Permission Errors
**Symptoms**: Cannot create tables or insert settings
**Solution**:
- Grant CREATE, INSERT, UPDATE permissions to database user
- Check web server file permissions

#### 4. Email Queue Not Processing
**Symptoms**: Emails remain in pending status
**Solution**:
```bash
# Check queue status
php /path/to/opendocman/application/cron/process_email_queue.php --help

# Run with verbose output
php /path/to/opendocman/application/cron/process_email_queue.php --verbose
```

### Diagnostic Commands

```bash
# Check email queue table exists
mysql -u username -p -e "DESCRIBE opendocman.odm_email_queue;"

# View pending emails
mysql -u username -p -e "SELECT COUNT(*) FROM opendocman.odm_email_queue WHERE status='pending';"

# Process queue manually
php /path/to/opendocman/application/cron/process_email_queue.php --batch-size=5 --verbose
```

## Rollback Instructions

If you need to rollback the upgrade:

### Option 1: Database Restore
```bash
# Restore from backup
mysql -u username -p opendocman < opendocman_backup_YYYYMMDD.sql
```

### Option 2: Manual Rollback
```sql
-- Remove email queue tables
DROP TABLE IF EXISTS odm_email_queue;
DROP TABLE IF EXISTS odm_email_queue_stats;

-- Remove email queue settings
DELETE FROM odm_settings WHERE name LIKE 'email_queue_%';

-- Revert database version
UPDATE odm_odmsys SET sys_value='1.4.0' WHERE sys_name='version';
```

## Maintenance Tasks

### Weekly Maintenance
- Check email queue statistics
- Review failed email reports
- Monitor queue processing times

### Monthly Maintenance
- Clean up old processed emails (automatic)
- Review and adjust batch size settings
- Update cron job schedule if needed

### Performance Tuning

#### For High-Volume Sites
```bash
# Increase batch size
# Admin → Settings → Email Queue Batch Size: 25

# Process more frequently
* * * * * php /path/to/opendocman/application/cron/process_email_queue.php
```

#### For Low-Volume Sites
```bash
# Decrease batch size
# Admin → Settings → Email Queue Batch Size: 5

# Process less frequently
*/10 * * * * php /path/to/opendocman/application/cron/process_email_queue.php
```

## Security Notes

- Email queue contains sensitive document information
- Admin access required for queue management
- Email content stored securely in database
- Failed email errors logged for debugging

## Support

### Getting Help
1. Check the email queue status page for errors
2. Review system logs for detailed error messages
3. Test email configuration outside of OpenDocMan
4. Verify cron job execution and permissions

### Log Locations
- **Email Queue Logs**: Check admin queue status page
- **System Logs**: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- **Cron Logs**: `/var/log/cron` or `/var/log/syslog`

---

**Success Criteria**: 
- File uploads complete in under 15 seconds
- Emails are queued and processed in background
- Queue status shows processing activity
- No errors in email queue status page

## Database Schema Synchronization

### Manual Installation Support
The `database.sql` file has been updated to include email queue components for manual installations:

- **Email queue tables**: `odm_email_queue` and `odm_email_queue_stats`
- **Email queue settings**: 8 new configuration options
- **Updated version**: Database version set to 1.4.5

### Verification Commands
After upgrade or manual installation, verify the schema:

```sql
-- Check version
SELECT sys_value FROM odm_odmsys WHERE sys_name='version';

-- Verify email queue tables exist
SHOW TABLES LIKE 'odm_email_queue%';

-- Count email queue settings
SELECT COUNT(*) FROM odm_settings WHERE name LIKE 'email_queue_%';
```

Expected results:
- Version: `1.4.5`
- Tables: `odm_email_queue`, `odm_email_queue_stats`
- Settings: `8` email queue configuration options

---

**Questions?** Check the DATABASE_UPGRADE_SUMMARY.md for technical details and additional troubleshooting steps.