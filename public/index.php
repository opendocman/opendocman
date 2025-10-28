<?php

/*
 * 1. Check fo a config file
 *   a. If not exists don't include odm-init
 *   b. If exists, include odm-init
 */

require __DIR__ . '/../application/vendor/autoload.php';
// Create simple request object to replace Zend Diactoros
$request = (object) [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'uri' => $_SERVER['REQUEST_URI'] ?? '/',
    'attributes' => []
];

// Simple method to add attributes to request object
$request->withAttribute = function($key, $val) use ($request) {
    $request->attributes[$key] = $val;
    return $request;
};

$request->getAttribute = function($key) use ($request) {
    return $request->attributes[$key] ?? null;
};

set_include_path(get_include_path() . PATH_SEPARATOR .'../application/');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/controllers/helpers');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/models');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/includes/smarty/');

spl_autoload_register(function ($class) {
    include $class . '.class.php';
});

// @TODO: Re-enable aura router
// Temporarily disable Aura Router due to dependency issues
// $routerContainer = new Aura\Router\RouterContainer();
// $map = $routerContainer->getMap();

$configExists = true;
if (file_exists(__DIR__ . '/../application/configs/config.php')) {
    // In the case of root folder calls
    require('configs/config.php');
} elseif (file_exists(__DIR__ . '/../application/configs/docker-configs/config.php')) {
    // In case we are running from Docker
    require('configs/docker-configs/config.php');
} elseif (file_exists(__DIR__ . '/../../config.php')) {
    // In the case of subfolders
    require('../../configs/config.php');
} elseif (file_exists(__DIR__ . '/../../../configs/config.php')) {
    // In the case of plugins
    require('../../../configs/config.php');
} else {
    $configExists = false;
    if ( false === strpos( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
        header( 'Location: /install/setup-config');
        exit;
    }
}

if($configExists) {
    /*
    * Connect to Database to see if it exists yet (it should always exist if there is a config file)
    */
    $dsn = "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8";
    try {
        $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS);
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lets see if there is a table named *_odmsys so we can tell if the db installation is complete
    try {
        $table_count = $pdo->query("SHOW TABLES LIKE '%_odmsys%'")->fetch(PDO::FETCH_NUM);
    } catch (Exception $e) {
        // We got an exception == table not found so we must not have run the db installation yet
    }

    if (isset($table_count) && $table_count > 0) {
        // Database exists - now check if schema version matches required version
        // But only if we're not already accessing an install route
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = trim($path, '/');

        if (strpos($path, 'install/') !== 0) {
            // Not an install route - check database version
            require '../application/version.php';
            require '../application/models/Settings.class.php';

            $current_db_version = Settings::get_db_version($pdo);

            if ($current_db_version !== $GLOBALS['CONFIG']['required_db_version']) {
                // Database upgrade needed - redirect to installer
                header('Location: /install/index');
                exit;
            }
        }

        // Database version is current or we're accessing install routes - proceed normally
        require '../application/controllers/helpers/functions.php';
        require '../application/odm-init.php';
    } else {
        // Define a stub function to prevent fatal errors
        if (!function_exists('callPluginMethod')) {
            function callPluginMethod($method, $args = '') {
                // Stub function - do nothing if plugins aren't loaded
                return;
            }
        }
    }

}

// CSRF protector library removed - will be replaced with better implementation
require '../application/version.php';
require '../application/models/classHeaders.php';
require '../application/controllers/helpers/mimetypes.php';
require '../application/controllers/helpers/crumb.php';
require '../application/controllers/helpers/udf_functions.php';

// Temporarily use simple routing fallback instead of complex Aura Router setup
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Simple routing - extract the path without query string
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Default to index if path is empty
if (empty($path)) {
    $path = 'index';
}

// Simple controller mapping
$controllerFile = "../application/controllers/{$path}.php";
if (file_exists($controllerFile)) {
    include($controllerFile);
} else {
    // Handle special cases
    if (strpos($path, 'install/') === 0) {
        $installPath = str_replace('install/', '', $path);
        $installFile = "../application/controllers/install/{$installPath}.php";
        if (file_exists($installFile)) {
            include($installFile);
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    } else {
        echo '<h1>404 - Page Not Found</h1>';
    }
}

// Router code removed - using simple include-based routing above
// exit; statement already handled in the simple routing section above
