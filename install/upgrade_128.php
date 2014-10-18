<?php
/*
upgrade_128.php - For users upgrading from DB version 1.2.8 to 1.2.9
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

echo 'Altering the settings table...<br />';
$query = "INSERT INTO `{$_SESSION['db_prefix']}settings` VALUES(NULL, 'max_query', '500', 'Set this to the maximum number of rows you want to be returned in a file listing. If your file list is slow decrease this value.', 'num')";
$result = mysql_query($query) or die("<br>Could not update {$_SESSION['db_prefix']}settings table. Error was:" .  mysql_error());
            
echo 'Updating db version...<br />';
$result = mysql_query("UPDATE {$_SESSION['db_prefix']}odmsys SET sys_value='1.2.9' WHERE sys_name='version'")
        or die("<br>Could not update version number: " . mysql_error());

echo 'Database update 1.2.9 complete. Please edit your admin->settings and verify your dataDir and base_url values...<br />';
