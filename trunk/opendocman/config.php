<?php
// Eliminate multiple inclusion of config.php
if( !defined('config') )
{
  	define('config', 'true', false);

// config.php - useful variables/functions

// database parameters
include 'functions.php';
include 'classHeaders.php';
include 'mimetypes.php';
require_once('crumb.php');

// Database Settings - Change these to match your database
$database = 'opendocman'; // Enter the name of the database here
$user = 'opendocman'; // Enter the username for the database
$pass = 'opendocman'; // Enter the password for the username
$hostname = 'localhost'; // Enter the hostname that is serving the database


global $CONFIG;      $CONFIG = array(
'debug' => '0',

// This setting is for a demo installation, where random people will be
// all loggging in as the same username/password like 'demo/demo'.
'demo' => 'false', 

// This is useful if you have a web-based kerberos authenticatio site
// Set to either kerbauth or mysql
//'authen' => 'kerbauth',
'authen' => 'mysql',

// Set the number of files that show up on each page
'page_limit' => '15',

// Set the number of page links that show up on each age
'num_page_limit' => '10', 

// Set the maximum displayable length of text field
'displayable_len' => '15',

// Set this to the url of the site
'base_url' => 'http://mydomain/opendocman',

// This is the browser window title
'title' => 'Document Repository',

// This is the program version for window title (This should be set to the current version of the program)
'current_version' => ' OpenDocMan v1.2rc1',

// The email address of the administrator of this site
'site_mail' => 'admin@mydomain',

//This variable sets the root username.  The root user will be able to access
//all files and have authority for everything.
'root_username'  => 'admin',

// location of file repository
// this should ideally be outside the Web server root
// make sure the server has permissions to read/write files!
'dataDir' => '/var/www/document_repository/', 

//This var sets the amount of days until each file needs to be revised, 
//assuming that there are 30 days in a month for all months.
'revision_expiration' => '90',

/* Choose an action option when a file is found to be expired
The first two options also result in sending email to reviewer
 	(1) Remove from file list until renewed
	(2) Show in file list but non-checkoutable
	(3) Send email to reviewer only
	(4) Do Nothing
*/
'file_expired_action' => '1'
);

// List of allowed file types
// Pay attention to the "Last Message:" in the status bar if your file is being rejected
// because of its file type. It should display the proper MIME type there, and you can 
// then add that string to this list to allow it
$allowedFileTypes = array('image/gif', 'text/html', 'text/plain', 'application/pdf', 'application/x-pdf', 'application/x-lyx', 'application/msword', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/msexcel', 'application/msaccess', 'text/richtxt', 'application/mspowerpoint', 'application/octet-stream', 'application/x-zip-compressed');

// <----- No need to edit below here ---->
//
// Set the revision directory. (relative to $dataDir)
$CONFIG['revisionDir'] = $GLOBALS['CONFIG']['dataDir'] . 'revisionDir/';

// Set the revision directory. (relative to $dataDir)
$CONFIG['archiveDir'] = $GLOBALS['CONFIG']['dataDir'] . 'archiveDir/';

//global $site_mail; 
global $hostname;
global $database;
global $user;
global $pass;
global $connection;
global $allowedFileTypes; 

$connection = mysql_connect($GLOBALS['hostname'], $GLOBALS['user'], $GLOBALS['pass']) or die ("Unable to connect!");
$db = mysql_select_db($GLOBALS['database'], $GLOBALS['connection']);
// All functions are in functions.php
}
?>
