<?php
/*
config.php - OpenDocMan main config file
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

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

// Eliminate multiple inclusion of config.php
if( !defined('config') )
{
  	define('config', 'true', false);

// config.php - useful variables/functions

// Database Settings - Change these to match your database
$GLOBALS['database'] = 'opendocman'; // Enter the name of the database here
$GLOBALS['user'] = 'opendocman'; // Enter the username for the database
$GLOBALS['pass'] = 'opendocman'; // Enter the password for the username
$GLOBALS['hostname'] = 'localhost'; // Enter the hostname that is serving the database

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
'page_limit' => '15',

// Set the number of page links that show up on each page
'num_page_limit' => '10', 

// Set the maximum displayable length of text field
'displayable_len' => '15',

// Set this to the url of the site
// No need for trailing "/" here
'base_url' => 'http://www.example.com/opendocman-1.2',

// This is the browser window title
'title' => 'Document Repository',

// This is the program version for window title (This should be set to the current version of the program)
'current_version' => ' OpenDocMan v1.2p1',

// The email address of the administrator of this site
'site_mail' => 'admin@example.com',

//This variable sets the root username.  The root user will be able to access
//all files and have authority for everything.
'root_username'  => 'admin',

// location of file repository
// this should ideally be outside the Web server root
// make sure the server has permissions to read/write files!
// Don't forget the trailing "/" 
'dataDir' => '/var/www/document_repository/',

// Set the maximum file upload size
'max_filesize' => '5000000',

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

// should we display document listings in the normal way or in a tree view
// this must be 'ON' to change the display
'treeview' => 'On',

// should we display the signup link?
'allow_signup' => 'On'

);

// List of allowed file types
// Pay attention to the "Last Message:" in the status bar if your file is being rejected
// because of its file type. It should display the proper MIME type there, and you can 
// then add that string to this list to allow it
$GLOBALS['allowedFileTypes'] = array('image/gif', 'text/html', 'text/plain', 'application/pdf', 'application/x-pdf', 'application/x-lyx', 'application/msword', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/msexcel', 'application/msaccess', 'text/richtxt', 'application/mspowerpoint', 'application/octet-stream', 'application/x-zip-compressed');

// <----- No need to edit below here ---->
//
// Set the revision directory. (relative to $dataDir)
$CONFIG['revisionDir'] = $GLOBALS['CONFIG']['dataDir'] . 'revisionDir/';

// Set the revision directory. (relative to $dataDir)
$CONFIG['archiveDir'] = $GLOBALS['CONFIG']['dataDir'] . 'archiveDir/';

$GLOBALS['connection'] = mysql_connect($GLOBALS['hostname'], $GLOBALS['user'], $GLOBALS['pass']) or die ("Unable to connect: " . mysql_error());
$db = mysql_select_db($GLOBALS['database'], $GLOBALS['connection']);

// All functions and includes are in functions.php
include_once('functions.php');
}
?>
