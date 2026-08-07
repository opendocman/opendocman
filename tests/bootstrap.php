<?php
/*
 * PHPUnit Bootstrap File for OpenDocMan Tests
 * 
 * This file is executed before any tests run and sets up the testing environment.
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path for the application
define('BASE_PATH', dirname(__DIR__));
define('APPLICATION_PATH', BASE_PATH . '/application');

// Prevent output during tests — must be set before any includes
ob_start();

// Set up autoloading for Composer dependencies
require_once APPLICATION_PATH . '/vendor/autoload.php';

// Include the application's class headers and configuration
require_once APPLICATION_PATH . '/models/classHeaders.php';

// Manually include the model classes since they're not autoloaded
require_once APPLICATION_PATH . '/models/databaseData.class.php';
require_once APPLICATION_PATH . '/models/User.class.php';
require_once APPLICATION_PATH . '/models/Category.class.php';
require_once APPLICATION_PATH . '/models/Department.class.php';
require_once APPLICATION_PATH . '/models/File.class.php';
require_once APPLICATION_PATH . '/models/Plugin.class.php';
require_once APPLICATION_PATH . '/models/Snapshot.class.php';
require_once APPLICATION_PATH . '/models/SnapshotManager.class.php';
require_once APPLICATION_PATH . '/version.php';
require_once APPLICATION_PATH . '/controllers/helpers/functions.php';

// Set up test database configuration (you may want to customize this)
$GLOBALS['CONFIG'] = [
    'root_id' => 1,
    'database_host' => 'localhost',
    'database_name' => 'opendocman_test',
    'database_user' => 'test_user',
    'database_pass' => 'test_pass',
    'database_prefix' => 'odm_',
    'db_prefix' => 'odm_',
];

// Include installer classes
require_once APPLICATION_PATH . '/installer/CheckResult.php';
require_once APPLICATION_PATH . '/installer/CheckerInterface.php';
require_once APPLICATION_PATH . '/installer/RequirementChecker.php';

// Include common test utilities
require_once __DIR__ . '/TestCase.php';

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Set up quiet testing environment
ini_set('log_errors', 1);
ini_set('error_log', '/dev/null');