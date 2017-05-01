<?php
/*
init.php - bootloader to initialize variables
 * If the config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * config.php file.
 *
 * Will also search for config.php in the OpenDocMan parent
 * directory to allow the OpendocMan directory to remain
 * untouched.

Copyright (C) 2011 Stephen Lawrence Jr.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * Set up the various view objects needed
 * and add the templates/layouts
 */
$factory = new \Aura\Html\HelperLocatorFactory;
$helpers = $factory->newInstance();
$view_factory = new \Aura\View\ViewFactory;
$view = $view_factory->newInstance($helpers);
$view_registry = $view->getViewRegistry();
$view_registry->set('access_log',  __DIR__ . '/templates/views/access_log.php');

$layout_registry = $view->getLayoutRegistry();
$layout_registry->set('default', __DIR__ . '/templates/layouts/default.php');

/*
 * Connect to Database
 */

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
$GLOBALS['pdo'] = $pdo;

ob_start();
include('includes/FirePHPCore/fb.php');

/*
/*
 * Load the Settings class
 */
require_once('Settings_class.php');
$settings = new Settings($pdo);
$settings->load();

/*
 * Common functions
 */
require_once('functions.php');

/*
 * Load the allowed file types list
 */
require_once('FileTypes_class.php');
$filetypes = new FileTypes_class($pdo);
$filetypes->load();

// Set the revision directory. (relative to $dataDir)
$CONFIG['revisionDir'] = $GLOBALS['CONFIG']['dataDir'] . 'revisionDir/';

// Set the revision directory. (relative to $dataDir)
$CONFIG['archiveDir'] = $GLOBALS['CONFIG']['dataDir'] . 'archiveDir/';

$_GET = sanitizeme($_GET);
$_REQUEST = sanitizeme($_REQUEST);
$_POST = sanitizeme($_POST);
$_SERVER = sanitizeme($_SERVER);
$_FILES = sanitizeme($_FILES);
