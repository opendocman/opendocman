<?php
/*
upgrade_124.php - Database upgrades for users upgrading from 1.2.4
Copyright (C) 2007  Stephen Lawrence

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

echo 'Renaming Table Names to include dbprefix...<br />';

$result = mysql_query("
ALTER TABLE  admin RENAME AS  odm_admin
") or die("<br>Could not rename admin table" . mysql_error());

$result = mysql_query("
ALTER TABLE category RENAME AS  odm_category
") or die("<br>Could not rename category table" . mysql_error());

$result = mysql_query("
ALTER TABLE data RENAME AS  odm_data
") or die("<br>Could not rename data table" . mysql_error());

$result = mysql_query("
ALTER TABLE  department RENAME AS  odm_department
") or die("<br>Could not rename department table" . mysql_error());

$result = mysql_query("
ALTER TABLE  dept_perms RENAME AS  odm_dept_perms
") or die("<br>Could not rename dept_perms table" . mysql_error());

$result = mysql_query("
ALTER TABLE  dept_reviewer RENAME AS  odm_dept_reviewer
") or die("<br>Could not rename dept_reviewer table" . mysql_error());

$result = mysql_query("
ALTER TABLE log RENAME AS  odm_log
") or die("<br>Could not rename log table" . mysql_error());

$result = mysql_query("
ALTER TABLE rights RENAME AS  odm_rights
") or die("<br>Could not rename rights table" . mysql_error());

$result = mysql_query("
ALTER TABLE user RENAME AS  odm_user
") or die("<br>Could not rename user table" . mysql_error());

$result = mysql_query("
ALTER TABLE user_perms RENAME AS  odm_user_perms
") or die("<br>Could not rename user_perms table" . mysql_error());

$result = mysql_query("
ALTER TABLE udf RENAME AS  odm_udf
") or die("<br>Could not rename udf table" . mysql_error());
?>
