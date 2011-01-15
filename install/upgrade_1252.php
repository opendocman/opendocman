<?php
/*
upgrade_125.php - Database upgrades for users upgrading from 1.2.5
through 1.2.5.2

Copyright (C) 2007-2011 Stephen Lawrence Jr.

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

echo 'Renaming Table Names to include dbprefix...<br />';

$result = mysql_query("
ALTER TABLE  admin RENAME AS {$GLOBALS['CONFIG']['db_prefix']}admin
        ") or die("<br>Could not rename admin table" . mysql_error());

$result = mysql_query("
ALTER TABLE category RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}category
        ") or die("<br>Could not rename category table" . mysql_error());

$result = mysql_query("
ALTER TABLE data RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}data
        ") or die("<br>Could not rename data table" . mysql_error());

$result = mysql_query("
ALTER TABLE department RENAME AS {$GLOBALS['CONFIG']['db_prefix']}department
        ") or die("<br>Could not rename department table" . mysql_error());

$result = mysql_query("
ALTER TABLE  dept_perms RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}dept_perms
        ") or die("<br>Could not rename dept_perms table" . mysql_error());

$result = mysql_query("
ALTER TABLE  dept_reviewer RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer
        ") or die("<br>Could not rename dept_reviewer table" . mysql_error());

$result = mysql_query("
ALTER TABLE  log RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}log
        ") or die("<br>Could not rename log table" . mysql_error());

$result = mysql_query("
ALTER TABLE rights RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}rights
        ") or die("<br>Could not rename rights table" . mysql_error());

$result = mysql_query("
ALTER TABLE user RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}user
        ") or die("<br>Could not rename user table" . mysql_error());

$result = mysql_query("
ALTER TABLE user_perms RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}user_perms
        ") or die("<br>Could not rename user_perms table" . mysql_error());

$result = mysql_query("
ALTER TABLE udf RENAME AS  {$GLOBALS['CONFIG']['db_prefix']}udf
        ") or die("<br>Could not rename udf table" . mysql_error());

echo 'Creating odmsys table<br />';
$result = mysql_query("
CREATE TABLE IF NOT EXISTS {$GLOBALS['CONFIG']['db_prefix']}odmsys
(
    id  int(11) auto_increment unique,
    sys_name  varchar(16),
    sys_value    varchar(255)
)
        ") or die("<br>Could not update" . mysql_error());

// Create version number in db
$result = mysql_query("
INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}odmsys VALUES (NULL,'version','1.2.6')
        ") or die("<br>Could insert new version into db user");