<?php
/*
 * Copyright (C) 2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// For users upgrading from DB version 1.4.0 to 1.4.5

global $pdo;

echo 'Creating email queue table...<br />';
$query = "CREATE TABLE IF NOT EXISTS `{$_SESSION['db_prefix']}email_queue` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Adding email queue settings...<br />';

// Email queue processing settings
$email_settings = [
    [
        'name' => 'email_queue_enabled',
        'value' => 'True',
        'description' => 'Enable asynchronous email queue processing. When enabled, emails are queued and processed in background.',
        'type' => 'bool'
    ],
    [
        'name' => 'email_queue_batch_size',
        'value' => '10',
        'description' => 'Number of emails to process in each queue batch. Lower values reduce server load.',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_retry_delay',
        'value' => '300',
        'description' => 'Delay in seconds before retrying failed emails (default: 5 minutes).',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_max_attempts',
        'value' => '3',
        'description' => 'Maximum number of retry attempts for failed emails.',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_cleanup_days',
        'value' => '30',
        'description' => 'Number of days to keep processed emails in queue before cleanup.',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_priority_high',
        'value' => '1',
        'description' => 'Priority level for high priority emails (lower numbers = higher priority).',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_priority_normal',
        'value' => '5',
        'description' => 'Priority level for normal priority emails.',
        'type' => 'num'
    ],
    [
        'name' => 'email_queue_priority_low',
        'value' => '10',
        'description' => 'Priority level for low priority emails.',
        'type' => 'num'
    ]
];

foreach ($email_settings as $setting) {
    // Check if setting already exists
    $check_query = "SELECT id FROM `{$_SESSION['db_prefix']}settings` WHERE name = :name";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(array(':name' => $setting['name']));

    switch ($check_stmt->rowCount()) {
            case 0:
                $insert_query = "INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES (NULL, :name, :value, :description, :validation)";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([
                    ':name' => $setting['name'],
                    ':value' => $setting['value'],
                    ':description' => $setting['description'],
                    ':validation' => $setting['type']  // Map 'type' to 'validation' column
                ]);
                echo "Added setting: {$setting['name']}<br />";
                break;
            default:
                echo "Setting {$setting['name']} already exists, skipping<br />";
                break;
        }
}

echo 'Creating email queue statistics table...<br />';
$query = "CREATE TABLE IF NOT EXISTS `{$_SESSION['db_prefix']}email_queue_stats` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Email queue management will be available to admin users...<br />';
echo 'Admin users can access email queue monitoring through the admin panel<br />';

echo 'Updating db version...<br />';
$query = "UPDATE {$_SESSION['db_prefix']}odmsys SET sys_value='1.4.5' WHERE sys_name='version'";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Database update from 1.4.0 to 1.4.5 complete.<br />';
echo '<br />';
echo '<strong>Email Queue System Installed Successfully!</strong><br />';
echo '<br />';
echo 'The following components have been added:<br />';
echo '• Email queue table for asynchronous email processing<br />';
echo '• Email queue statistics tracking<br />';
echo '• Configuration settings for queue management<br />';
echo '• Admin permissions for queue monitoring<br />';
echo '<br />';
echo '<strong>Next Steps:</strong><br />';
echo '1. Set up a cron job to process the email queue: <code>*/5 * * * * php /path/to/opendocman/application/cron/process_email_queue.php</code><br />';
echo '2. Visit Admin Settings to configure email queue options<br />';
echo '3. Monitor queue status through the admin panel<br />';
echo '<br />';
