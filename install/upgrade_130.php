<?php
/*
upgrade_130.php - For users upgrading from DB version 1.3.0 to 1.3.4
Copyright (C) 2015 Ap.Muthu.

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

global $pdo;

echo 'Updating admin table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}admin` 
    CHANGE `id` `id` INT(11) UNSIGNED   NOT NULL DEFAULT 0 FIRST, 
    CHANGE `admin` `admin` TINYINT(1)   NULL AFTER `id`, 
    ADD PRIMARY KEY(`id`);";
// Original Field format of 'admin.id' was INT(11) UNSIGNED DEFAULT NULL. No data modifications needed!
// Original Field format of 'admin.admin' was TINYINT(4) DEFAULT NULL. No data modifications needed!
// Added Primary Key
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating data table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}data` 
    CHANGE `default_rights` `default_rights` TINYINT(1)   NULL AFTER `department`, 
    CHANGE `publishable` `publishable` TINYINT(1)   NULL AFTER `default_rights`, 
    DROP KEY `id`, 
    DROP KEY `id_2`;";
// Original Field format of 'data.default_rights' was TINYINT(4) DEFAULT NULL. No data modifications needed!
// Original Field format of 'data.publishable' was TINYINT(4) DEFAULT NULL. No data modifications needed!
// Dropped redundant indices / keys
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating dept_perms table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}dept_perms` 
    CHANGE `rights` `rights` TINYINT(1)   NOT NULL DEFAULT 0 AFTER `dept_id`;";
// Original Field format of 'dept_perms.rights' was TINYINT(4) NOT NULL DEFAULT '0'. No data modifications needed!
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating dept_reviewer table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}dept_reviewer` 
    CHANGE `dept_id` `dept_id` INT(11) UNSIGNED   NOT NULL DEFAULT 0 FIRST, 
    CHANGE `user_id` `user_id` INT(11) UNSIGNED   NOT NULL DEFAULT 0 AFTER `dept_id`, 
    ADD PRIMARY KEY(`dept_id`,`user_id`);";
// Original Field format of 'dept_reviewer.dept_id' was INT(11) UNSIGNED DEFAULT NULL. No data modifications needed!
// Original Field format of 'dept_reviewer.active' was INT(11) UNSIGNED DEFAULT NULL. No data modifications needed!
// Added Primary Key
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating filetypes table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}filetypes` 
    CHANGE `id` `id` TINYINT(2) UNSIGNED   NOT NULL AUTO_INCREMENT FIRST, 
    CHANGE `active` `active` TINYINT(1)   NOT NULL after `type`;";
// Original Field format of 'filetypes.id' was INT(10) UNSIGNED NOT NULL AUTO_INCREMENT. No data modifications needed!
// Original Field format of 'filetypes.active' was TINYINT(4) NOT NULL. No data modifications needed!
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating odmsys table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}odmsys` 
    CHANGE `id` `id` TINYINT(2)   NOT NULL AUTO_INCREMENT FIRST, 
    DROP KEY `id`, 
    ADD PRIMARY KEY(`id`);";
// Original Field format of 'odmsys.id' was INT(11) AUTO_INCREMENT UNIQUE. No data modifications needed!
// Added Primary Key
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating rights table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}rights` 
    CHANGE `RightId` `RightId` TINYINT(1)   NULL FIRST;";
// Original Field format of 'rights.RightId' was TINYINT(4) DEFAULT NULL. No data modifications needed!
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating settings table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}settings` 
    CHANGE `id` `id` TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT FIRST;";
// Original Field format of 'settings.id' was INT UNSIGNED NOT NULL AUTO_INCREMENT. No data modifications needed!
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating udf table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}udf` 
    CHANGE `id` `id` TINYINT(2)   NOT NULL AUTO_INCREMENT FIRST, 
    CHANGE `field_type` `field_type` TINYINT(1)   NULL AFTER `display_name`, 
    DROP KEY `id`, 
    ADD PRIMARY KEY(`id`);";
// Original Field format of 'udf.id' was INT(11) AUTO_INCREMENT UNIQUE. No data modifications needed!
// Original Field format of 'udf.field_type' was INT. No data modifications needed!
// Added Primary Key
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating user_perms table...<br />';
$query = "ALTER TABLE `{$_SESSION['db_prefix']}user_perms` 
    CHANGE `fid` `fid` INT(11) UNSIGNED   NOT NULL DEFAULT 0 FIRST, 
    CHANGE `rights` `rights` TINYINT(1)   NOT NULL DEFAULT 0 AFTER `uid`, 
    DROP KEY `fid`, 
    ADD PRIMARY KEY(`fid`,`uid`,`rights`), 
    DROP KEY `user_perms_idx`;";
// Original Field format of 'user_perms.fid' was INT(11) UNSIGNED DEFAULT NULL. No data modifications needed!
// Original Field format of 'user_perms.rights' was TINYINT(4) NOT NULL DEFAULT '0'. No data modifications needed!
// Dropped redundant indices / keys
// Added Primary Key
$stmt = $pdo->prepare($query);
$stmt->execute();

$sql_operations = array(
"INSERT IGNORE INTO `{$_SESSION['db_prefix']}odm_filetypes` VALUES(NULL, 'video/3gpp', 1);",
"INSERT IGNORE INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL,'show_footer', 'True', 'Set this to True to display the footer.', 'bool');"
);

foreach ($sql_operations as $query) {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}

echo 'Updating db version...<br />';
$query = "UPDATE {$_SESSION['db_prefix']}odmsys SET sys_value='1.3.4' WHERE sys_name='version'";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Database update 1.3.4 complete. Please edit your admin->settings and verify your dataDir and base_url values...<br />';
