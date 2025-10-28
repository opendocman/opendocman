<?php
error_reporting(E_ALL);

$composer_autoload = dirname(__DIR__) . "/vendor/autoload.php";
if (! is_readable($composer_autoload)) {
    echo "Did not find 'vendor/autoload.php'. Run composer install" . PHP_EOL;
    exit(1);
}

require $composer_autoload;
