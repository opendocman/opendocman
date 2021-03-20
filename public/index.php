<?php

/*
 * 1. Check fo a config file
 *   a. If not exists don't include odm-init
 *   b. If exists, include odm-init
 */

require __DIR__ . '/../application/vendor/autoload.php';
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

set_include_path(get_include_path() . PATH_SEPARATOR .'../application/');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/controllers/helpers');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/models');
set_include_path(get_include_path() . PATH_SEPARATOR .'../application/includes/smarty/');

spl_autoload_register(function ($class) {
    include $class . '.class.php';
});

$routerContainer = new Aura\Router\RouterContainer();

$map = $routerContainer->getMap();

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
        require '../application/controllers/helpers/functions.php';
        require '../application/odm-init.php';
    }

}

require '../application/vendor/owasp/csrf-protector-php/libs/csrf/csrfprotector.php';
require '../application/version.php';
require '../application/models/classHeaders.php';
require '../application/controllers/helpers/mimetypes.php';
require '../application/controllers/helpers/crumb.php';
require '../application/controllers/helpers/udf_functions.php';

$map->get("access_log.read", "/access_log", function($request) {include("../application/controllers/access_log.php");});
$map->get("add.read", "/add", function($request) {include("../application/controllers/add.php");});
$map->post("add.write", "/add", function($request) {include("../application/controllers/add.php");});
$map->get("admin.read", "/admin", function($request) {include("../application/controllers/admin.php");});
$map->get("ajax_udf.read", "/ajax_udf", function($request) {include("../application/controllers/ajax_udf.php");});
$map->get("category.read", "/category", function($request) {include("../application/controllers/category.php");});
$map->post("category.write", "/category", function($request) {include("../application/controllers/category.php");});
$map->get("check-in.read", "/check-in", function($request) {include("../application/controllers/check-in.php");});
$map->post("check-in.write", "/check-in", function($request) {include("../application/controllers/check-in.php");});
$map->get("check-out.read", "/check-out", function($request) {include("../application/controllers/check-out.php");});
$map->post("check-out.write", "/check-out", function($request) {include("../application/controllers/check-out.php");});
$map->get("check_exp.read", "/check_exp", function($request) {include("../application/controllers/check_exp.php");});
$map->post("check_exp.write", "/check_exp", function($request) {include("../application/controllers/check_exp.php");});
$map->get("delete.read", "/delete", function($request) {include("../application/controllers/delete.php");});
$map->post("delete.write", "/delete", function($request) {include("../application/controllers/delete.php");});
$map->get("department.read", "/department", function($request) {include("../application/controllers/department.php");});
$map->post("department.write", "/department", function($request) {include("../application/controllers/department.php");});
$map->get("details.read", "/details", function($request) {include("../application/controllers/details.php");});
$map->get("edit.read", "/edit", function($request) {include("../application/controllers/edit.php");});
$map->post("edit.write", "/edit", function($request) {include("../application/controllers/edit.php");});
$map->get("error.read", "/error", function($request) {include("../application/controllers/error.php");});
$map->get("file_list_report.read", "/file_list_report", function($request) {include("../application/controllers/file_list_report.php");});
$map->get("file_ops.read", "/file_ops", function($request) {include("../application/controllers/file_ops.php");});
$map->post("file_ops.write", "/file_ops", function($request) {include("../application/controllers/file_ops.php");});
$map->get("filetypes.read", "/filetypes", function($request) {include("../application/controllers/filetypes.php");});
$map->post("filetypes.write", "/filetypes", function($request) {include("../application/controllers/filetypes.php");});
$map->get("forgot_password.read", "/forgot_password", function($request) {include("../application/controllers/forgot_password.php");});
$map->post("forgot_password.write", "/forgot_password", function($request) {include("../application/controllers/forgot_password.php");});
$map->get("history.read", "/history", function($request) {include("../application/controllers/history.php");});
$map->get("in.read", "/in", function($request) {include("../application/controllers/in.php");});
$map->get("index.old", "/index.php/{page}", function($request) {
    $page = (string) $request->getAttribute('page');
    header('Location: /' . htmlentities($page, ENT_QUOTES));
    exit;
});
$map->get("index.read", "/", function($request) {include("../application/controllers/index.php");});
$map->post("index.write", "/", function($request) {include("../application/controllers/index.php");});
$map->get("index.read-full", "/index", function($request) {include("../application/controllers/index.php");});
$map->post("index.write-full", "/index", function($request) {include("../application/controllers/index.php");});
$map->get("install-index.read", "/install/index", function($request) {
    include("../application/controllers/install/index.php");});
$map->post("install-index.write", "/install/index", function($request) {
    include("../application/controllers/install/index.php");});
$map->get("install-setupconfig.read", "/install/setup-config", function($request) {
    include("../application/controllers/install/setup-config.php");});
$map->post("install-setupconfig.write", "/install/setup-config", function($request) {
    include("../application/controllers/install/setup-config.php");});
$map->get("logout.read", "/logout", function($request) {include("../application/controllers/logout.php");});
$map->get("out.read", "/out", function($request) {include("../application/controllers/out.php");});
$map->get("profile.read", "/profile", function($request) {include("../application/controllers/profile.php");});
$map->get("rejects.read", "/rejects", function($request) {include("../application/controllers/rejects.php");});
$map->post("rejects.write", "/rejects", function($request) {include("../application/controllers/rejects.php");});
$map->get("search.read", "/search", function($request) {include("../application/controllers/search.php");});
$map->post("search.write", "/search", function($request) {include("../application/controllers/search.php");});
$map->get("settings.read", "/settings", function($request) {include("../application/controllers/settings.php");});
$map->post("settings.write", "/settings", function($request) {include("../application/controllers/settings.php");});
$map->get("signup.read", "/signup", function($request) {include("../application/controllers/signup.php");});
$map->post("signup.write", "/signup", function($request) {include("../application/controllers/signup.php");});
$map->get("udf.read", "/udf", function($request) {include("../application/controllers/udf.php");});
$map->post("udf.write", "/udf", function($request) {include("../application/controllers/udf.php");});
$map->get("user.read", "/user", function($request) {include("../application/controllers/user.php");});
$map->post("user.write", "/user", function($request) {include("../application/controllers/user.php");});
$map->get("toBePublished.read", "/toBePublished", function($request) {include("../application/controllers/toBePublished.php");});
$map->post("toBePublished.write", "/toBePublished", function($request) {include("../application/controllers/toBePublished.php");});
$map->get("view.read", "/view", function($request) {include("../application/controllers/view.php");});
$map->get("view_file.read", "/view_file", function($request) {include("../application/controllers/view_file.php");});

$matcher = $routerContainer->getMatcher();

$route = $matcher->match($request);

if (! $route) {
    // get the first of the best-available non-matched routes
    $failedRoute = $matcher->getFailedRoute();

    // which matching rule failed?
    switch ($failedRoute->failedRule) {
        case 'Aura\Router\Rule\Allows':
            // 405 METHOD NOT ALLOWED
            // Send the $failedRoute->allows as 'Allow:'
            echo '405';
            break;
        case 'Aura\Router\Rule\Accepts':
            // 406 NOT ACCEPTABLE
            echo '406';
            break;
        default:
            // 404 NOT FOUND
            echo '<h1>404</h1>';
            break;
    }
    exit;
}

foreach ($route->attributes as $key => $val) {
    $request = $request->withAttribute($key, $val);
}

$callable = $route->handler;
$response = $callable($request);