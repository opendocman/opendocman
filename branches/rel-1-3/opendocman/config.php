<?php
// Eliminate multiple inclusion of config.php
if( !defined('config') )
{
  	define('config', 'true', false);

// config.php - useful variables/functions

// database parameters
include 'include/functions.php';
include 'include/classHeaders.php';
include 'include/mimetypes.php';
include 'include/crumb.php';

// Database Settings - Change these to match your database
$database = 'opendocman'; // Enter the name of the database here
$user = 'opendocman'; // Enter the username for the database
$pass = 'opendocman'; // Enter the password for the username
$hostname = 'localhost'; // Enter the hostname that is serving the database


global $CONFIG;      $CONFIG = array(
'debug' => '0',

// This setting is for a demo installation, where random people will be
// all loggging in as the same username/password like 'demo/demo'. This will
// keep users from removing files, users, etc.
'demo' => 'false', 

// This is useful if you have a web-based kerberos authenticatio site
// Set to either kerbauth or mysql
//'authen' => 'kerbauth',
'authen' => 'mysql',

// Set the number of files that show up on each page
'page_limit' => '100',

// Set the number of page links that show up on each page
'num_page_limit' => '10', 

// Set the maximum displayable length of text field
'displayable_len' => '15',

// Set this to the url of the site
'base_url' => 'http://mydomain/opendocman',

// This is the browser window title
'title' => 'Document Repository',

// This is the program version for window title (This should be set to the current version of the program)
'current_version' => ' OpenDocMan v1.3rc1',

// The email address of the administrator of this site
'site_mail' => 'admin@mydomain',

//This variable sets the root username.  The root user will be able to access
//all files and have authority for everything.
'root_username'  => 'admin',

// location of file repository
// this should ideally be outside the Web server root
// make sure the server has permissions to read/write files!
// Make sure to put an ending / !!
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
'file_expired_action' => '1', 

//Authorization control: On or Off (case sensitive)
//If set On, every document added or checked back must be reviewed by an admin
//before it can go public.  To disable this review queue, set this variable to Off.
//When set to Off, all newly added or checked back in documents will immediately go public
'authorization' => 'On',

//Secure URL control: On or Off (case sensitive)
//When set to 'On', all urls will be secured
//When set to 'Off', all urls are normal and readable
'secureurl' => 'On',

// Set the default language (english, spanish).
// Local users may override this setting
// check include/language folder for languages available
'language' => 'english', 

/* Author's default right to his/her right.  Below is the list from the right table.
   If yours is any different, please use your own ODM.right table's values
   +---------+-------------+
   | RightId | Description |
   +---------+-------------+
   |       0 | none        |
   |       1 | view        |
   |      -1 | forbidden   |
   |       2 | read        |
   |       3 | write       |
   |       4 | admin       |
   +---------+-------------+
   */
'owner_default_right' => '3',

/* HTTPS enforced login.  If this option is turned on, the login page will only take https connections.  If the user uses http, ODM will redirect itself to a HTTPS connection.  SSL must be enabled with your webserver for this feature to work
1)On
2)Off
*/
'SSL_enforced' => 'Off'

);

// List of allowed file types
// Pay attention to the "Last Message:" in the status bar if your file is being rejected
// because of its file type. It should display the proper MIME type there, and you can 
// then add that string to this list to allow it
$allowedFileTypes = array('image/gif', 'text/html', 'text/plain', 'application/pdf', 'application/x-pdf', 'application/x-lyx', 'application/msword', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/msexcel', 'application/msaccess', 'text/richtxt', 'application/mspowerpoint', 'application/octet-stream', 'application/x-zip-compressed');

// <----- No need to edit below here ---->
//
// Set the revision directory. (relative to $dataDir)
$GLOBALS['CONFIG']['revisionDir'] = $GLOBALS['CONFIG']['dataDir'] . 'revisionDir/';

// Set the revision directory. (relative to $dataDir)
$GLOBALS['CONFIG']['archiveDir'] = $GLOBALS['CONFIG']['dataDir'] . 'archiveDir/';

// Include the language info
include_once 'include/language/' . $GLOBALS['CONFIG']['language'] . '.php';

//global $site_mail; 
global $hostname;
global $database;
global $user;
global $pass;
global $connection;
global $allowedFileTypes; 

$connection = mysql_connect($GLOBALS['hostname'], $GLOBALS['user'], $GLOBALS['pass']) or die ("Unable to connect!  " . mysql_error());
$db = mysql_select_db($GLOBALS['database'], $GLOBALS['connection']);
// All functions are in functions.php
require_once 'secureurl.class.php';
include 'secureurl.php';
}
?>
