<?php
/*
upgrade.php - Main upgrade controller
Copyright (C) 2002-2011  Stephen Lawrence

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/*** This function calls the upgrade from odm 1.0 ***/
function do_upgrade10 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade10 error: Unable to select database.</font>");
    include("install/upgrade_10.php");
}
/*** This function calls the upgrade from odm 1.1rc1 ***/
function do_upgrade11rc1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade11rc1 error: Unable to select database.</font>");
    include("install/upgrade_11rc1.php");
}
/*** This function calls the upgrade from odm 1.1rc2 ***/
function do_upgrade11rc2 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade11rc2 error: Unable to select database.</font>");
    include("install/upgrade_11rc2.php");
}
/*** This function calls the upgrade from odm 1.1 ***/
function do_upgrade11 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade11 error: Unable to select database.</font>");
    include("install/upgrade_11.php");
}
/*** This function calls the upgrade from odm 1.2rc1 ***/
function do_upgrade12rc1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade12rc1 error: Unable to select database.</font>");
    include("install/upgrade_12rc1.php");
}
/*** This function calls the upgrade from odm 1.2p1 ***/
function do_upgrade12p1 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade12p1 error: Unable to select database.</font>");
    include("install/upgrade_12p1.php");
}
/*** This function calls the upgrade from odm 1.2p3 ***/
function do_upgrade12p3 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade12p3 error: Unable to select database.</font>");
    include("install/upgrade_12p3.php");
}
/*** This function calls the upgrade from odm 1.2.4 ***/
function do_upgrade124 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrad124 error: Unable to select database.</font>");
    include("install/upgrade_124.php");
}
/*** This function calls the upgrade from odm 1.2.5.2 ***/
function do_upgrade1252 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade1252 error: Unable to select database.</font>");
    include("install/upgrade_1252.php");
}
/*** This function calls the upgrade from odm 1.2.5.6 ***/
function do_upgrade1256 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade1256 error: Unable to select database.</font>");
    include("install/upgrade_1256.php");
}
/*** This function calls the upgrade from odm 1.2.5.7 or 1.2.6beta ***/
function do_upgrade1257 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade1257 error: Unable to select database.</font>");
    include("install/upgrade_1257.php");
}
/*** This function calls the upgrade from odm 1.2.6.1 ***/
function do_upgrade1261 ($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    global $dbconn;
    mysql_connect($dbhost, $dbuname, $dbpass);
    mysql_select_db("$dbname") or die ("<br><font class=\"pn-failed\">do_upgrade1261 error: Unable to select database.</font>");
    include("install/upgrade_1261.php");
}