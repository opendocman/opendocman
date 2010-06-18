<?php
/*
upgrade_126.php - Database upgrades for users upgrading from 1.2.5.7
Copyright (C) 2010 Stephen Lawrence Jr.

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
$result = mysql_query("UPDATE {$GLOBALS['CONFIG']['db_prefix']}odmsys SET sys_value='1.2.6' WHERE sys_name='version'")
        or die("<br>Could not update version number" . mysql_error());