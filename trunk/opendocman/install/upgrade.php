<?php
/*
upgrade.php - Main upgrade controller
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

/*** This function calls the upgrade from odm 1.0 ***/
function do_upgrade10 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_10.php");
}
/*** This function calls the upgrade from odm 1.1rc1 ***/
function do_upgrade11rc1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_11rc1.php");
}
/*** This function calls the upgrade from odm 1.1rc2 ***/
function do_upgrade11rc2 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_11rc2.php");
}

/*** This function calls the upgrade from odm 1.1 ***/
function do_upgrade11 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_11.php");
}

/*** This function calls the upgrade from odm 1.2rc1 ***/
function do_upgrade12rc1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_12rc1.php");
}

/*** This function calls the upgrade from odm 1.2p1 ***/
function do_upgrade12p1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype) {
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">Unable to select database.</font>");
    include("install/upgrade_12p1.php");
}



?>
