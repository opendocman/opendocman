<?php
/*
upgrade_1256.php - Database upgrades for users upgrading from 1.2.5.6
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

echo 'Fixing any broken revision numbers...<br />';
$sql = "SELECT id, revision from {$GLOBALS['CONFIG']['db_prefix']}log WHERE revision LIKE '%(%'";
$result = mysql_query($sql);
while(list($id,$revision) = mysql_fetch_row($result)) {
    $rev_array = split("-", $revision);
    $rev_left = ltrim($rev_array[0], "(");
    $rev_right = rtrim($rev_array[1], ")");
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision=" . intval($rev_left-$rev_right) . " WHERE id='$id' AND revision='$revision'";
    $result2 = mysql_query($query);
};
?>
