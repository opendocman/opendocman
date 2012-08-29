<?php
/*
odm.php - main file for creating a fresh installation
Copyright (C) 2002-2012  Stephen Lawrence

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// For ODM fresh install
// Admin table

// Added for automated script installers
$dbprefix = isset($GLOBALS['CONFIG']['db_prefix']) ? $GLOBALS['CONFIG']['db_prefix'] : $_SESSION['db_prefix'];
if(!isset($_SESSION['adminpass'])) 
{
    echo 'No Admin Pass!';
    exit;
}
$adminpass = $_SESSION['adminpass'];

// Access Log Table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}access_log
        ") or die("<br>Could not create {$dbprefix}access_log table. Error was:" .  mysql_error());
        
$result = mysql_query("
CREATE TABLE `{$dbprefix}access_log` (
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL
)
    ") or die("<br>Could not create {$dbprefix}access_log table. Error was:" .  mysql_error());

// Admin table    
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}admin
        ") or die("<br>Could not create {$dbprefix}admin table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}admin (
  id int(11) unsigned default NULL,
  admin tinyint(4) default NULL
)
        ") or die("<br>Could not create {$dbprefix}admin table. Error was:" .  mysql_error());

// Admin user
$result = mysql_query("
INSERT INTO {$dbprefix}admin VALUES (1,1)
        ") or die("<br>Could not create {$dbprefix}admin user. Error was:" .  mysql_error());

// Category table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}category
        ") or die("<br>Could not create {$dbprefix}category table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}category (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
)
        ") or die("<br>Could not create {$dbprefix}category table. Error was:" .  mysql_error());

$result = mysql_query("
INSERT INTO {$dbprefix}category VALUES (NULL,'SOP')
        ") or die("<br>Could not create {$dbprefix}category. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}category VALUES (NULL,'Training Manual')
        ") or die("<br>Could not create {$dbprefix}category. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}category VALUES (NULL,'Letter')
        ") or die("<br>Could not create {$dbprefix}category. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}category VALUES (NULL,'Presentation')
        ") or die("<br>Could not create {$dbprefix}category. Error was:" .  mysql_error());

// Data table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}data
        ") or die("<br>Could not create {$dbprefix}data table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}data (
  id int(11) unsigned NOT NULL auto_increment,
  category int(11) unsigned NOT NULL default '0',
  owner int(11) unsigned default NULL,
  realname varchar(255) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  description varchar(255) default NULL,
  comment varchar(255) default '',
  status smallint(6) default NULL,
  department smallint(6) unsigned default NULL,
  default_rights tinyint(4) default NULL,
  publishable tinyint(4) default NULL,
  reviewer int(11) unsigned default NULL,
  reviewer_comments varchar(255) default NULL,
  PRIMARY KEY  (id),
  KEY data_idx (id,owner),
  KEY id (id),
  KEY id_2 (id),
  KEY publishable (publishable),
  KEY description (description)
)
        ") or die("<br>Could not create {$dbprefix}data table. Error was:" .  mysql_error());

// Department Table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}department
        ") or die("<br>Could not create {$dbprefix}department table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}department (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
)
        ") or die("<br>Could not create {$dbprefix}department table. Error was:" .  mysql_error());

$result = mysql_query("
INSERT INTO {$dbprefix}department VALUES (NULL,'Information Systems')
        ") or die("<br>Could not {$dbprefix}add department. Error was:" .  mysql_error());

// Department Permissions table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}dept_perms
        ") or die("<br>Could not create {$dbprefix}dept_perms table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}dept_perms (
  fid int(11) unsigned default NULL,
  dept_id int(11) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0',
  KEY rights (rights),
  KEY dept_id (dept_id),
  KEY fid (fid)
)
        ") or die("<br>Could not create {$dbprefix}dept_perms table. Error was:" .  mysql_error());

// Department Reviewer table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}dept_reviewer
        ") or die("<br>Could not create {$dbprefix}dept_reviewer table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}dept_reviewer (
  dept_id int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL
)
        ") or die("<br>Could not create {$dbprefix}dept_reviewer table. Error was:" .  mysql_error());

// data for table 'dept_reviewer'
$result = mysql_query("
INSERT INTO {$dbprefix}dept_reviewer VALUES (1,1)
        ") or die("<br>Could add to {$dbprefix}dept_reviewer table. Error was:" .  mysql_error());

// Log table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}log
        ") or die("<br>Could not create {$dbprefix}log table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}log (
  id int(11) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL,
  KEY id (id),
  KEY modified_on (modified_on)
)
        ") or die("<br>Could not create {$dbprefix}log table. Error was:" .  mysql_error());

// Rights table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}rights
        ") or die("<br>Could not create {$dbprefix}rights table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
)
        ") or die("<br>Could not create {$dbprefix}rights table. Error was:" .  mysql_error());

// Rights values
$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (0,'none')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (1,'view')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (-1,'forbidden')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (2,'read')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (3,'write')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

$result = mysql_query("
 INSERT INTO {$dbprefix}rights VALUES (4,'admin')
        ") or die("<br>Could not add {$dbprefix}rights entry. Error was:" .  mysql_error());

// User table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}user
        ") or die("<br>Could not create {$dbprefix}user table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE {$dbprefix}user (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(25) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  department int(11) unsigned default NULL,
  phone varchar(20) default NULL,
  Email varchar(50) default NULL,
  last_name varchar(255) default NULL,
  first_name varchar(255) default NULL,
  pw_reset_code char(32) default NULL,
  PRIMARY KEY  (id)
)
        ") or die("<br>Could not create {$dbprefix}user table. Error was:" .  mysql_error());

// Create admin user
$result = mysql_query("
INSERT INTO {$dbprefix}user VALUES (NULL,'admin',md5('{$adminpass}'),'1','5555551212','admin@example.com','User','Admin','')
        ") or die("<br>Could not add user. Error was:" .  mysql_error());

// User permissions table
$result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}user_perms
        ") or die("<br>Could not create {$dbprefix}user_perms table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE IF NOT EXISTS {$dbprefix}user_perms (
  fid int(11) unsigned default NULL,
  uid int(11) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights),
  KEY fid (fid),
  KEY uid (uid),
  KEY rights (rights)
)
        ") or die("<br>Could not create {$dbprefix}user_perms table. Error was:" .  mysql_error());

        $result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}udf
        ") or die("<br>Could not drop {$dbprefix}udf table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE IF NOT EXISTS {$dbprefix}udf (
    id  int auto_increment unique,
    table_name varchar(16),
    display_name varchar(16),
    field_type int
)
        ") or die("<br>Could not create {$dbprefix}udf table. Error was:" .  mysql_error());

        $result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}odmsys
        ") or die("<br>Could not drop {$dbprefix}odmsys table. Error was:" .  mysql_error());

$result = mysql_query("
CREATE TABLE IF NOT EXISTS {$dbprefix}odmsys
(
    id  int(11) auto_increment unique,
    sys_name  varchar(16),
    sys_value    varchar(255)
)
        ") or die("<br>Could not create {$dbprefix}odmsys table. Error was:" .  mysql_error());

// Create version number in db
$result = mysql_query("
INSERT INTO {$dbprefix}odmsys VALUES (NULL,'version','1.2.6')
        ") or die("<br>Could not insert new version into {$dbprefix}odmsys. Error was:" .  mysql_error());

                $result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}settings
        ") or die("<br>Could not drop {$dbprefix}settings table. Error was:" .  mysql_error());


// Create the settings table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS `{$dbprefix}settings` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`validation` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
UNIQUE ( `name` )
)
        ") or die("<br>Could not create {$dbprefix}settings table. Error was:" .  mysql_error());

// Populate the setttings table with default values
$sql_operations = array(
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'title', 'Document Repository', 'This is the browser window title', 'maxsize=255');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'dataDir', '{$_SESSION['datadir']}', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'secureurl', 'True', 'Secure URL control: On or Off (case sensitive). When set to ''On'', all urls will be secured. When set to \"Off\", all urls are normal and readable', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'allow_signup', 'False', 'Should we display the sign-up link?', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'theme', 'tweeter', 'Which theme to use?', '');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req');",
"INSERT INTO `{$dbprefix}settings` VALUES(NULL, 'base_url', '{$_SESSION['baseurl']}', 'Set this to the url of the site. No need for trailing \"/\" here', 'url');"
);

foreach($sql_operations as $query)
{
    $result = mysql_query($query) or die('Died while inserting into settings table: ' . $query . ' ' . mysql_error());
}

        $result = mysql_query("
DROP TABLE IF EXISTS {$dbprefix}filetypes
        ") or die("<br>Could not drop {$dbprefix}filetypes table. Error was:" .  mysql_error());

// Create the filetypes table
$result = mysql_query("
CREATE  TABLE IF NOT EXISTS `{$dbprefix}filetypes` (
`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`type` VARCHAR(255) NOT NULL ,
`active` TINYINT(4) NOT NULL ,
PRIMARY KEY (`id`) )
    ") or die("<br>Could not create {$dbprefix}filetypes table. Error was:" .  mysql_error());

// Create the filetypes data
$sql_operations=array(
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/gif', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'text/html', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'text/plain', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/pdf', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/x-pdf', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/msword', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/jpeg', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/pjpeg', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/png', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/msexcel', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/msaccess', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'text/richtxt', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/mspowerpoint', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/octet-stream', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/x-zip-compressed', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/tiff', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NUll, 'image/tif', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.ms-powerpoint', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.ms-excel', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-master', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-template', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-web', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'text/csv', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'audio/mpeg', 0);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/x-dwg', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'image/x-dfx', 1);",
"INSERT INTO `{$dbprefix}filetypes` VALUES(NULL, 'drawing/x-dwf', 1);"
        );
foreach($sql_operations as $query)
{
    $result = mysql_query($query) or die('Died while inserting to filetypes table: ' . mysql_error());
}