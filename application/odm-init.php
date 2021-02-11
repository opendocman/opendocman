<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
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

// Bootloader to initialize variables
// If the configs/config.php file is not found then an error
// will be displayed asking the visitor to set up the
// configs/config.php file.
//
// Will also search for configs/config.php in the OpenDocMan parent
// directory to allow the OpenDocMan directory to remain
// untouched.

//echo "running odm-init.php in dir:" . __DIR__ . '<br>';
//echo get_include_path() . "</br>";
//require __DIR__ . '/vendor/autoload.php';

/**
 * Set up the various view objects needed
 * and add the templates/layouts
 */
$factory = new \Aura\Html\HelperLocatorFactory;
$helpers = $factory->newInstance();
$view_factory = new \Aura\View\ViewFactory;
$view = $view_factory->newInstance($helpers);
$view_registry = $view->getViewRegistry();
$view_registry->set('access_log',  __DIR__ . '/views/access_log.php');

$layout_registry = $view->getLayoutRegistry();
$layout_registry->set('default', __DIR__ . '/layouts/default.php');

/*
 * Connect to Database
 */

$dsn = "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8";
try {
    $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS);
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//$GLOBALS['pdo'] = $pdo;

ob_start();

/*
/*
 * Load the Settings class
 */
//require_once('Settings_class.php');
$settings = new Settings($pdo);
$settings->load();

$plugin = new Plugin();

// Set the Smarty variables
//require_once('../../../includes/smarty/Smarty.class.php');
$GLOBALS['smarty'] = new Smarty();
$GLOBALS['smarty']->template_dir = dirname(__FILE__) . '/views/' . $GLOBALS['CONFIG']['theme'] . '/';
$GLOBALS['smarty']->compile_dir = dirname(__FILE__) . '/templates_c/';

$GLOBALS['CONFIG']['base_url'] = base_url();

/**** SET g_ vars from Global Config arr ***/
foreach ($GLOBALS['CONFIG'] as $key => $value) {
    $GLOBALS['smarty']->assign('g_' . $key, $value);
}

include 'includes/language/' . $GLOBALS['CONFIG']['language'] . '.php';

/* Set language  vars */
foreach ($GLOBALS['lang'] as $key => $value) {
    $GLOBALS['smarty']->assign('g_lang_' . $key, msg($key));
}



csrfProtector::init();

/* Set language  vars */


// Check if dataDir is working
if (!is_dir($GLOBALS['CONFIG']['dataDir'])) {
    echo $GLOBALS['lang']['message_datadir_problem_exists'] . ' <a href="settings?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
} elseif (!is_writable($GLOBALS['CONFIG']['dataDir'])) {
    echo $GLOBALS['lang']['message_datadir_problem_writable'] . ' <a href="settings?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
}

/*
 * Load the allowed file types list
 */

$filetypes = new FileTypes($pdo);
$filetypes->load();

// Set the revision directory. (relative to $dataDir)
$GLOBALS['CONFIG']['revisionDir'] = $GLOBALS['CONFIG']['dataDir'] . 'revisionDir/';

// Set the revision directory. (relative to $dataDir)
$GLOBALS['CONFIG']['archiveDir'] = $GLOBALS['CONFIG']['dataDir'] . 'archiveDir/';

$_GET = sanitizeme($_GET);
$_REQUEST = sanitizeme($_REQUEST);
$_POST = sanitizeme($_POST);
$_SERVER = sanitizeme($_SERVER);
$_FILES = sanitizeme($_FILES);
