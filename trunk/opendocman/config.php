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
'page_limit' => '10',

// Set the maximum displayable length of text field
'displayable_len' => '15',

// Set this to the url of the site
'base_url' => 'http://www.yourdomainaa.com/opendocman',

// This is the browser window title
'title' => 'Document Repository',

// This is the program version for window title (This should be set to the current version of the program)
'current_version' => ' OpenDocMan v1.2rc1',

// The email address of the administrator of this site
'site_mail' => 'admin@yourdomainaa.com',

//This variable sets the root username.  The root user will be able to access
//all files and have authority for everything.
'root_username'  => 'admin',

// location of file repository
// this should ideally be outside the Web server root
// make sure the server has permissions to read/write files!
'dataDir' => '/var/www/document_repository/'
);

// list of allowed file types 
$allowedFileTypes = array('image/gif', 'text/html', 'text/plain', 'application/pdf', 'application/x-pdf', 'application/x-lyx', 'application/msword', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/msexcel', 'application/msaccess', 'text/richtxt', 'application/mspowerpoint', 'application/octet-stream', 'application/x-zip-compressed');

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
