<?php
/*
upgrade_1256.php - Database upgrades for users upgrading from 1.2.5.6
Copyright (C) 2007-2010  Stephen Lawrence

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

echo 'Fixing any broken revision numbers...<br />';
$sql = "SELECT id, revision from {$GLOBALS['CONFIG']['db_prefix']}log WHERE revision LIKE '%(%'";
$result = mysql_query($sql);
while(list($id,$revision) = mysql_fetch_row($result))
{
    $rev_array = explode("-", $revision);
    $rev_left = ltrim($rev_array[0], "(");
    $rev_right = rtrim($rev_array[1], ")");
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision=" . intval($rev_left-$rev_right) . " WHERE id='$id' AND revision='$revision'";
    $result2 = mysql_query($query);
}

echo 'Updating db version...<br />';
$result = mysql_query("UPDATE {$GLOBALS['CONFIG']['db_prefix']}odmsys SET sys_value='1.2.5.7' WHERE sys_name='version'")
        or die("<br>Could not update version number" . mysql_error());

echo 'Updating UDF Table Names...<br />';
$query = "SELECT table_name from {$GLOBALS['CONFIG']['db_prefix']}udf";
$result = mysql_query($query) or die("<br>Could not select UDF table names" . mysql_error());
while(list($table_name) = mysql_fetch_row($result))
{
    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data CHANGE $table_name {$GLOBALS['CONFIG']['db_prefix']}udftbl_$table_name int(11)";
    $result1 = mysql_query($query) or die ("<br>Could not change UDF table names from data table" . mysql_error());

    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}udf SET table_name = '{$GLOBALS['CONFIG']['db_prefix']}udftbl_$table_name' WHERE table_name = '$table_name'";
    $result2 = mysql_query($query) or die ("<br>Could not update UDF table names in udf table " . mysql_error());

    $query = "ALTER TABLE $table_name RENAME {$GLOBALS['CONFIG']['db_prefix']}udftbl_$table_name";
    $result3 = mysql_query($query) or die("<br>Could rename table " . mysql_error());
}