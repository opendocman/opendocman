<?php
/*
upgrade_1257.php - Database upgrades for users upgrading from 1.2.5.7 or 1.2.6beta
Copyright (C) 2010-2011 Stephen Lawrence Jr.

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

echo 'Updating db version...<br />';
$result = mysql_query("UPDATE {$_SESSION['db_prefix']}odmsys SET sys_value='1.2.6' WHERE sys_name='version'")
        or die("<br>Could not update version number: " . mysql_error());

echo 'Adding the settings table...<br />';
 // Create the settings table
 $result = mysql_query("
        CREATE TABLE IF NOT EXISTS `{$_SESSION['db_prefix']}settings` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`validation` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
UNIQUE ( `name` )
)
        ") or die("<br>Could not create {$_SESSION['db_prefix']}settings table. Error was:" .  mysql_error());

$sql_operations = array(
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'title', 'Document Repository', 'This is the browser window title', 'maxsize=255');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'dataDir', '{$_SESSION['datadir']}', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'secureurl', 'True', 'Secure URL control: On or Off (case sensitive). When set to \"On\", all urls will be secured. When set to \"Off\", all urls are normal and readable', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'allow_signup', 'False', 'Should we display the sign-up link?', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'theme', 'tweeter', 'Which theme to use?', '');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req');",
"INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'base_url', '{$_SESSION['baseurl']}', 'Set this to the url of the site. No need for trailing \"/\" here', 'url');"
);

foreach($sql_operations as $query)
{
    $result = mysql_query($query) or die('Died while inserting to settings table. Are you already running a version greater than 1.2.5.7?: ' . mysql_error());
}

echo 'Adding the filetypes table...<br />';
$result = mysql_query("
CREATE  TABLE IF NOT EXISTS `{$_SESSION['db_prefix']}filetypes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` VARCHAR(255) NOT NULL ,
  `active` TINYINT(4) NOT NULL ,
  PRIMARY KEY (`id`) )
    ") or die("<br>Could not create {$_SESSION['db_prefix']}filetypes table. Error was:" .  mysql_error());

$sql_operations=array(
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/gif', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'text/html', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'text/plain', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/pdf', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/x-pdf', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/msword', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/jpeg', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/pjpeg', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/png', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/msexcel', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/msaccess', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'text/richtxt', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/mspowerpoint', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/octet-stream', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/x-zip-compressed', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/tiff', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NUll, 'image/tif', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.ms-powerpoint', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.ms-excel', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-master', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-template', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-web', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'text/csv', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'audio/mpeg', 0);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/x-dwg', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'image/x-dfx', 1);",
"INSERT INTO `{$_SESSION['db_prefix']}filetypes` VALUES(NULL, 'drawing/x-dwf', 1);"
        );

foreach($sql_operations as $query)
{
    $result = mysql_query($query) or die('Died while inserting to filetypes table: ' . mysql_error());
}

echo 'Update to 1.2.6 complete. Please edit your admin->settings and verify your dataDir and base_url values...<br />';