<?php
/*
upgrade_12p1.php - Database upgrades for users upgrading from 1.2p1
Copyright (C) 2002, 2003, 2004  Stephen Lawrence

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

$result = mysql_query("
RENAME TABLE 
    admin TO $GLOBALS[CONFIG][table_prefix]admin, 
    category TO $GLOBALS[CONFIG][table_prefix]category,
    data TO $GLOBALS[CONFIG][table_prefix]data,
    department TO $GLOBALS[CONFIG][table_prefix]department,
    dept_perms TO $GLOBALS[CONFIG][table_prefix]dept_perms,
    dept_reviewer TO $GLOBALS[CONFIG][table_prefix]dept_reviewer,
    log TO $GLOBALS[CONFIG][table_prefix]log,
    rights TO $GLOBALS[CONFIG][table_prefix]rights,
    user TO $GLOBALS[CONFIG][table_prefix]user,
    user_perms TO $GLOBALS[CONFIG][table_prefix]user_perms
") or die("<br>Could not rename tables" . mysql_error());

$result = mysql_query("
ALTER TABLE $GLOBALS[CONFIG][table_prefix]data ADD anonymous tinyint(4) NULL DEFAULT '0' AFTER reviewer_comments;
") or die("<br>Could not update data table- " . mysql_error());
?>
