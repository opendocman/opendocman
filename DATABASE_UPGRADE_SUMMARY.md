# OpenDocMan Database Upgrade Implementation Summary

## Overview

This document summarizes the implementation of the database upgrade system for OpenDocMan version 1.4.5, which introduces asynchronous email queue functionality to improve file upload performance.

## Version Information

- **Previous Version**: 1.4.4
- **New Version**: 1.4.5
- **Database Schema Version**: 1.4.5
- **Upgrade Type**: Feature Addition (Email Queue System)

## Database Schema Changes

### New Tables Created

#### 1. Email Queue Table (`odm_email_queue`)
```sql
CREATE TABLE `odm_email_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `to_email` varchar(255) NOT NULL,
    `from_email` varchar(255) NOT NULL,
    `subject` varchar(500) NOT NULL,
    `body` text NOT NULL,
    `headers` text DEFAULT NULL,
    `priority` int(11) DEFAULT 5,
    `status` enum('pending','processing','sent','failed') DEFAULT 'pending',
    `attempts` int(11) DEFAULT 0,
    `max_attempts` int(11) DEFAULT 3,
    `error_message` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status_scheduled` (`status`, `scheduled_at`),
    KEY `created_at` (`created_at`),
    KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. Email Queue Statistics Table (`odm_email_queue_stats`)
```sql
CREATE TABLE `odm_email_queue_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL,
    `emails_queued` int(11) DEFAULT 0,
    `emails_sent` int(11) DEFAULT 0,
    `emails_failed` int(11) DEFAULT 0,
    `avg_processing_time` decimal(10,3) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### New Settings Added

The following configuration settings were added to the `odm_settings` table:

| Setting Name | Default Value | Description |
|--------------|---------------|-------------|
| `email_queue_enabled` | `True` | Enable asynchronous email queue processing |
| `email_queue_batch_size` | `10` | Number of emails to process in each batch |
| `email_queue_retry_delay` | `300` | Delay in seconds before retrying failed emails |
| `email_queue_max_attempts` | `3` | Maximum retry attempts for failed emails |
| `email_queue_cleanup_days` | `30` | Days to keep processed emails before cleanup |
| `email_queue_priority_high` | `1` | Priority level for high priority emails |
| `email_queue_priority_normal` | `5` | Priority level for normal priority emails |
| `email_queue_priority_low` | `10` | Priority level for low priority emails |

## Implementation Files

### Database Upgrade Components

1. **`application/controllers/install/upgrade_144.php`**
   - Handles upgrade from version 1.4.4 to 1.4.5
   - Creates new email queue tables
   - Adds configuration settings
   - Provides installation feedback

2. **`application/controllers/install/index.php`** (Modified)
   - Added `do_update_144()` function
   - Added upgrade case for version 1.4.4
   - Updated all upgrade chains to include new version
   - Added upgrade link in web interface

3. **`application/controllers/install/odm.php`** (Modified)
   - Updated fresh installation to include email queue tables
   - Added email queue settings for new installations
   - Updated database version to 1.4.5

4. **`application/version.php`** (Modified)
   - Updated application version to 1.4.5

5. **`database.sql`** (Modified)
   - Updated database version to 1.4.5
   - Added email queue table definitions
   - Added email queue settings
   - Synchronized with automated installer

### Supporting Components

1. **`application/models/EmailQueue.class.php`** (Existing)
   - Email queue management class
   - Handles queuing, processing, and statistics

2. **`application/cron/process_email_queue.php`** (Existing)
   - Background processing script
   - Command-line interface for queue processing

3. **`application/controllers/optimized_add.php`** (Existing)
   - Uses EmailQueue for asynchronous notifications
   - Improved upload performance

4. **`application/controllers/view_email_queue.php`** (Existing)
   - Administrative interface for queue monitoring

5. **`application/controllers/admin.php`** (Modified)
   - Added email queue management link

## Upgrade Process

### For Existing Installations

1. **Automatic Detection**
   - System detects current database version via `odm_settings` table
   - Compares against required version (1.4.5)
   - Displays appropriate upgrade options

2. **Upgrade Execution**
   - User clicks "Upgrade from DB schema version 1.4.4"
   - System executes `upgrade_144.php`
   - Creates new tables and settings
   - Updates database version marker

3. **Post-Upgrade Tasks**
   - Set up cron job for email queue processing
   - Configure email queue settings via admin panel
   - Monitor queue status through admin interface

### For Fresh Installations

1. **Automatic Inclusion**
   - New installations automatically include email queue tables
   - All settings are pre-configured with default values
   - Database version set to 1.4.5

2. **Manual Installation Support**
   - `database.sql` file updated with email queue components
   - Maintains parity with automated installer
   - Supports manual database setup scenarios

## Performance Benefits

### Before (Version 1.4.4)
- Synchronous email sending during upload
- Upload blocked until all emails sent
- Sequential processing of reviewer notifications
- Typical upload time: 1-2 minutes

### After (Version 1.4.5)
- Asynchronous email queue processing
- Upload completes immediately
- Background email processing via cron
- Expected upload time: 5-15 seconds

## Administrative Features

### Email Queue Management
- **Queue Status Monitoring**: View pending, processing, sent, and failed emails
- **Retry Failed Emails**: Manual retry of failed email attempts
- **Queue Statistics**: Daily statistics tracking and reporting
- **Configuration Management**: Adjust batch sizes, retry delays, and priorities

### Access Control
- Email queue management requires admin privileges
- Integrated with existing OpenDocMan admin system
- Available through main admin panel

## Maintenance and Monitoring

### Cron Job Setup
```bash
# Process email queue every 5 minutes
*/5 * * * * php /path/to/opendocman/application/cron/process_email_queue.php

# Or every minute for high-volume sites
* * * * * php /path/to/opendocman/application/cron/process_email_queue.php
```

### Queue Cleanup
- Automatic cleanup of processed emails after configured days
- Statistics retained for reporting purposes
- Configurable retention periods

### Monitoring
- Queue status dashboard in admin panel
- Error logging for failed email attempts
- Performance statistics tracking

## Backward Compatibility

- **Full Compatibility**: All existing OpenDocMan features remain unchanged
- **Gradual Migration**: Email queue can be disabled if needed
- **Data Integrity**: No changes to existing data structures
- **API Compatibility**: No breaking changes to existing APIs

## Security Considerations

- **Email Content Protection**: Email bodies stored securely in database
- **Access Control**: Admin-only access to queue management
- **Error Handling**: Sensitive information masked in error logs
- **Database Security**: InnoDB engine with proper indexing

## File Synchronization

### Database Schema Files
The following files have been updated and synchronized for version 1.4.5:

- **`application/controllers/install/odm.php`** - Automated fresh installation
- **`database.sql`** - Manual installation via SQL dump
- **`application/controllers/install/upgrade_144.php`** - Upgrade script

All three files now include:
- Email queue table definitions
- Email queue statistics table
- Email queue configuration settings
- Updated version markers

### Synchronization Verification
Both automated and manual installation methods will result in identical database schemas:
```sql
-- Version verification
SELECT sys_value FROM odm_odmsys WHERE sys_name='version'; -- Should return '1.4.5'

-- Table verification
SHOW TABLES LIKE 'odm_email_queue%'; -- Should show both queue tables

-- Settings verification
SELECT COUNT(*) FROM odm_settings WHERE name LIKE 'email_queue_%'; -- Should return 8
```

## Testing Recommendations

### Pre-Upgrade Testing
1. Backup database and files
2. Test upgrade on staging environment
3. Verify email functionality before upgrade

### Post-Upgrade Verification
1. Confirm new tables exist
2. Test file upload with email notifications
3. Verify cron job processing
4. Check admin queue interface

### Performance Testing
1. Upload multiple files simultaneously
2. Monitor queue processing times
3. Verify email delivery
4. Check system resource usage

## Rollback Plan

If issues occur, rollback can be performed by:
1. Restoring database from pre-upgrade backup
2. Reverting to previous OpenDocMan version
3. Removing email queue tables (if partial upgrade)

## Support and Documentation

- **Configuration Guide**: Available in admin settings
- **Troubleshooting**: Error logs and queue status monitoring
- **Performance Tuning**: Adjustable batch sizes and processing intervals
- **Integration**: Compatible with existing email infrastructure

## Future Enhancements

The email queue system provides foundation for:
- **Email Templates**: Customizable notification templates
- **Bulk Operations**: Mass email notifications
- **Priority Systems**: Advanced email prioritization
- **Analytics**: Enhanced email delivery reporting
- **Integration**: Third-party email service support

---

**Note**: This upgrade maintains full backward compatibility while providing significant performance improvements for file upload operations. The asynchronous email processing reduces server load and improves user experience during file uploads.