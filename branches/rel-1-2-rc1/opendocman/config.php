<?php
// Eliminate multiple inclusion of config.php
if( !defined('config') )
{
  	define('config', 'true', false);

// config.php - useful variables/functions

// database parameters
include 'functions.php';
include 'ldap.inc';
include 'classHeaders.php';
include 'mimetypes.php';

// Database Settings - Change these to match your database
$database = 'vault'; // Enter the name of the database here
$user = 'vault1'; // Enter the username for the database
$pass = 'vault1'; // Enter the password for the username
$hostname = 'musa'; // Enter the hostname that is serving the database


global $CONFIG;      $CONFIG = array(
'debug' => '0',

// This is useful if you have a web-based kerberos authenticatio site
// Set to either kerbauth or mysql
//'authen' => 'kerbauth',
'authen' => 'mysql',

// Not Working
//Should we use ldap for user info lookup?
// 'ldaplookup' => 'false',

// Not Working
//LDAP server (only needed if using ldaplookup
//'ldap_server' => 'ldap.ucdavis.edu',
//'ldap_basedn' => 'ou=University of California Davis,o=University of California,c=US',

// Set the number of files that show up on each page
'page_limit' => '20',

// Set the number of page links that show up on each age
'num_page_limit' => '10', 

// Set the maximum displayable length of text field
'displayable_len' => '15',

// Set this to the url of the site
'base_url' => 'http://cahfs.ucdavis.edu/~knguyen/cvs/opendocman1.2',

// This is the browser window title
'title' => 'Document Repository',

// This is the program version for window title
'current_version' => ' OpenDocMan v1.1rc1  ',

// The email address of the administrator of this site
'site_mail' => 'admin@yourdomainaa.com',

//This variable sets the root username.  The root user will be able to access
//all files and have authority for everything.
'root_username'  => 'kdng',

// location of file repository
// this should ideally be outside the Web server root
// make sure the server has permissions to read/write files!
'dataDir' => '/usr/home/httpd/document_repository/', 
);

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

// list of allowed file types
$allowedFileTypes = array('image/gif', 'text/html', 'text/plain', 'application/x-pdf', 'application/x-lyx', 'application/msword', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/msexcel', 'application/msaccess', 'text/richtxt', 'application/mspowerpoint', 'application/octet-stream', 'application/x-zip-compressed');
// All functions are in functions.php
}
?>
