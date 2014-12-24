<?php
/*
upgrade_1262.php - Database upgrades for users upgrading from versions 1.2.6.3 - 1.2.7.3 up to 1.2.8
Copyright (C) 2014 Stephen Lawrence Jr.

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

echo 'Altering the user table...<br />';

$query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}user ADD COLUMN can_add tinyint(1) NULL DEFAULT 1";
$stmt = $pdo->prepare($query);
$stmt->execute();

$query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}user ADD COLUMN can_checkin tinyint(1) NULL DEFAULT 1";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Updating db version...<br />';
$query = "UPDATE {$_SESSION['db_prefix']}odmsys SET sys_value='1.2.8' WHERE sys_name='version'";
$stmt = $pdo->prepare($query);
$stmt->execute();

echo 'Database update 1.2.8 complete. Please edit your admin->settings and verify your dataDir and base_url values...<br />';
