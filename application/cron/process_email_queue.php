#!/usr/bin/env php
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

/**
 * Email Queue Processor Cron Job
 *
 * This script processes the email queue in the background to send
 * notification emails asynchronously, improving upload performance.
 *
 * Usage:
 * - Add to crontab to run every minute
 * - Or run every 5 minutes
 * - Or run manually: php process_email_queue.php
 *
 * Command line options:
 * --batch-size=N    Process N emails per batch (default: 50)
 * --max-batches=N   Process at most N batches (default: 10)
 * --timeout=N       Maximum execution time in seconds (default: 120)
 * --verbose         Enable verbose output
 * --help            Show this help message
 */

// Prevent web access
if (isset($_SERVER['HTTP_HOST'])) {
    die("This script can only be run from the command line.\n");
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set memory and time limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Parse command line arguments
$options = getopt('', [
    'batch-size::',
    'max-batches::',
    'timeout::',
    'verbose',
    'help'
]);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$batch_size = isset($options['batch-size']) ? (int)$options['batch-size'] : 50;
$max_batches = isset($options['max-batches']) ? (int)$options['max-batches'] : 10;
$timeout = isset($options['timeout']) ? (int)$options['timeout'] : 120;
$verbose = isset($options['verbose']);

// Validate parameters
if ($batch_size < 1 || $batch_size > 1000) {
    die("Error: batch-size must be between 1 and 1000\n");
}

if ($max_batches < 1 || $max_batches > 100) {
    die("Error: max-batches must be between 1 and 100\n");
}

if ($timeout < 10 || $timeout > 3600) {
    die("Error: timeout must be between 10 and 3600 seconds\n");
}

// Set execution timeout
set_time_limit($timeout);

// Include OpenDocMan initialization
$script_dir = dirname(__FILE__);
$app_dir = dirname($script_dir);

// Check if config exists
$config_files = [
    $app_dir . '/configs/config.php',
    $app_dir . '/configs/docker-configs/config.php'
];

$config_loaded = false;
foreach ($config_files as $config_file) {
    if (file_exists($config_file)) {
        require_once $config_file;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Error: Could not find OpenDocMan configuration file\n");
}

// Include required classes
require_once $app_dir . '/controllers/helpers/functions.php';

// Check if all required classes exist
$required_classes = ['PDO', 'User', 'FileData', 'Reviewer', 'AccessLog'];
$missing_classes = [];

// Set up database connection
try {
    $dsn = 'mysql:host=' . APP_DB_HOST . ';dbname=' . APP_DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    die("Error: Database connection failed: " . $e->getMessage() . "\n");
}

// Load settings
try {
    require_once $app_dir . '/models/Settings.class.php';
    $settings = new Settings($pdo);
    $settings->load();
} catch (Exception $e) {
    die("Error: Could not load settings: " . $e->getMessage() . "\n");
}

// Include the EmailQueue class
require_once $app_dir . '/models/EmailQueue.class.php';

/**
 * Show help message
 */
function showHelp() {
    echo "OpenDocMan Email Queue Processor\n";
    echo "================================\n\n";
    echo "This script processes the email queue in the background.\n\n";
    echo "Usage: php process_email_queue.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --batch-size=N    Process N emails per batch (default: 50, max: 1000)\n";
    echo "  --max-batches=N   Process at most N batches (default: 10, max: 100)\n";
    echo "  --timeout=N       Maximum execution time in seconds (default: 120, max: 3600)\n";
    echo "  --verbose         Enable verbose output\n";
    echo "  --help            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php process_email_queue.php\n";
    echo "  php process_email_queue.php --batch-size=100 --verbose\n";
    echo "  php process_email_queue.php --max-batches=5 --timeout=60\n\n";
    echo "Crontab examples:\n";
    echo "  # Process every minute\n";
    echo "  * * * * * /usr/bin/php /path/to/opendocman/application/cron/process_email_queue.php\n\n";
    echo "  # Process every 5 minutes\n";
    echo "  */5 * * * * /usr/bin/php /path/to/opendocman/application/cron/process_email_queue.php\n\n";
}

/**
 * Log message with timestamp
 */
function logMessage($message, $force_output = false) {
    global $verbose;

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";

    if ($verbose || $force_output) {
        echo $log_message . "\n";
    }

    error_log("EmailQueueCron: {$message}");
}

/**
 * Format processing statistics for output
 */
function formatStats($stats) {
    return sprintf(
        "processed: %d, sent: %d, failed: %d, skipped: %d, batches: %d",
        $stats['processed'],
        $stats['sent'],
        $stats['failed'],
        $stats['skipped'],
        $stats['batches']
    );
}

// Main execution starts here
$start_time = microtime(true);

logMessage("Starting email queue processing", true);
logMessage("Configuration: batch_size={$batch_size}, max_batches={$max_batches}, timeout={$timeout}s");

try {
    // Initialize email queue
    $email_queue = new EmailQueue($pdo);

    // Get initial queue statistics
    $initial_stats = $email_queue->getStats();
    logMessage("Initial queue stats: " . json_encode($initial_stats));

    if ($initial_stats['pending'] == 0 && $initial_stats['retry'] == 0) {
        logMessage("No emails to process", true);
        exit(0);
    }

    // Process the queue
    logMessage("Processing queue...");
    $processing_stats = $email_queue->cronProcess($batch_size, $max_batches);

    // Get final queue statistics
    $final_stats = $email_queue->getStats();

    // Calculate execution time
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);

    // Log results
    logMessage("Processing completed in {$execution_time}ms", true);
    logMessage("Processing stats: " . formatStats($processing_stats), true);
    logMessage("Final queue stats: " . json_encode($final_stats));

    // Show summary if verbose
    if ($verbose) {
        echo "\nSummary:\n";
        echo "========\n";
        echo "Execution time: {$execution_time}ms\n";
        echo "Emails processed: {$processing_stats['processed']}\n";
        echo "Emails sent: {$processing_stats['sent']}\n";
        echo "Emails failed: {$processing_stats['failed']}\n";
        echo "Batches processed: {$processing_stats['batches']}\n";
        echo "Remaining in queue: " . ($final_stats['pending'] + $final_stats['retry']) . "\n";

        if ($processing_stats['failed'] > 0) {
            echo "\nNote: Failed emails will be retried automatically.\n";
        }

        if (($final_stats['pending'] + $final_stats['retry']) > 0) {
            echo "Note: There are still emails in the queue. Consider running this script more frequently.\n";
        }
    }

    // Exit with appropriate code
    if ($processing_stats['failed'] > 0) {
        exit(1); // Some emails failed
    } else {
        exit(0); // Success
    }

} catch (Exception $e) {
    $error_message = "Fatal error: " . $e->getMessage();
    logMessage($error_message, true);
    logMessage("Stack trace: " . $e->getTraceAsString());

    if ($verbose) {
        echo "\nFatal Error:\n";
        echo "============\n";
        echo $e->getMessage() . "\n";
        echo "\nStack Trace:\n";
        echo $e->getTraceAsString() . "\n";
    }

    exit(2); // Fatal error
}
