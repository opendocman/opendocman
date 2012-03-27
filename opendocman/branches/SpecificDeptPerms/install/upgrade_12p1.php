<?php
/*
upgrade_12p1.php - Database upgrades for users upgrading from 1.2p1
Copyright (C) 2002-2010 Stephen Lawrence Jr.

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

echo 'Updating admin table<br />';
$result = mysql_query("
ALTER TABLE admin MODIFY id int(11) unsigned NOT NULL 
") or die("<br>Could not update" . mysql_error());
// Fieldformat of 'admin.id' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

echo 'Updating category table<br />';
$result = mysql_query("
ALTER TABLE category MODIFY id int(11) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'category.id' changed from 'smallint(5) unsigned NOT NULL DEFAULT '' COMMENT '' auto_increment to int(11) unsigned NOT NULL DEFAULT 0 COMMENT '' auto_increment. Possibly data modifications needed!

echo 'Updating data table<br />';
$result = mysql_query("
ALTER TABLE data MODIFY id int(11) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'data.id' changed from 'smallint(5) unsigned NOT NULL DEFAULT '' COMMENT '' auto_increment to int(11) unsigned NOT NULL DEFAULT 0 COMMENT '' auto_increment. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE data MODIFY category int(11) unsigned NOT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'data.category' changed from 'tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '' to int(11) unsigned NOT NULL DEFAULT '0' COMMENT ''. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE data MODIFY owner int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'data.owner' changed from 'smallint(6) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE data MODIFY reviewer int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'data.reviewer' changed from 'smallint(6) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

echo 'Updating department table<br />';
$result = mysql_query("
ALTER TABLE department MODIFY id int(11) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'department.id' changed from 'smallint(5) unsigned NOT NULL DEFAULT '' COMMENT '' auto_increment to int(11) unsigned NOT NULL DEFAULT 0 COMMENT '' auto_increment. Possibly data modifications needed!

echo 'Updating dept_perms table<br />';
$result = mysql_query("
ALTER TABLE dept_perms MODIFY fid int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'dept_perms.fid' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE dept_perms MODIFY dept_id int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'dept_perms.dept_id' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

echo 'Updating dept_reviewer table<br />';
$result = mysql_query("
ALTER TABLE dept_reviewer MODIFY dept_id int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'dept_reviewer.dept_id' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE dept_reviewer MODIFY user_id int(11) unsigned NULL DEFAULT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'dept_reviewer.user_id' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

echo 'Updating log table<br />';
$result = mysql_query("
ALTER TABLE log MODIFY id int(11) unsigned NOT NULL 
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'log.id' changed from 'int(10) unsigned NOT NULL DEFAULT '0' COMMENT '' to int(11) unsigned NOT NULL DEFAULT '0' COMMENT ''. Possibly data modifications needed!

echo 'Updating user table<br />';
$result = mysql_query("
ALTER TABLE user MODIFY id int(11) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'user.id' changed from 'smallint(5) unsigned NOT NULL DEFAULT '' COMMENT '' auto_increment to int(11) unsigned NOT NULL DEFAULT 0 COMMENT '' auto_increment. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE user MODIFY department int(11) unsigned NULL DEFAULT NULL
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'user.department' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

echo 'Updating user_perms table<br />';
$result = mysql_query("
ALTER TABLE user_perms MODIFY fid int(11) unsigned NULL DEFAULT NULL
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'user_perms.fid' changed from 'smallint(5) unsigned NULL DEFAULT NULL COMMENT '' to int(11) unsigned NULL DEFAULT NULL COMMENT ''. Possibly data modifications needed!

$result = mysql_query("
ALTER TABLE user_perms MODIFY uid int(11) unsigned NOT NULL
") or die("<br>Could not update" . mysql_error());
//  Fieldformat of 'user_perms.uid' changed from 'smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '' to int(11) unsigned NOT NULL DEFAULT '0' COMMENT ''. Possibly data modifications needed!