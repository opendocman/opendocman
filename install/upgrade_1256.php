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

global $pdo;

echo 'Fixing any broken revision numbers...<br />';
$query = "SELECT id, revision from {$GLOBALS['CONFIG']['db_prefix']}log WHERE revision LIKE '%(%'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll();
foreach ($result as $row) {
    $rev_array = explode("-", $row['revision']);
    $rev_left = ltrim($rev_array[0], "(");
    $rev_right = rtrim($rev_array[1], ")");
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = :new_revision WHERE id = :row_id AND revision= :revision";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':new_revision' => intval($rev_left-$rev_right),
        ':row_id' => $row['id'],
        ':revision' => $row['revision']
    ));
}

echo 'Updating db version...<br />';
$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}odmsys SET sys_value='1.2.5.7' WHERE sys_name='version'";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating UDF Table Names...<br />';
$query = "SELECT table_name from {$GLOBALS['CONFIG']['db_prefix']}udf";
$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll();

foreach ($result as $row) {
    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data CHANGE {$row['table_name']} {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$row['table_name']} int(11)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}udf SET table_name = '{$GLOBALS['CONFIG']['db_prefix']}udftbl_{$row['table_name']}' WHERE table_name = '{$row['table_name']}'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $query = "ALTER TABLE $table_name RENAME {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$row['table_name']}";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}
