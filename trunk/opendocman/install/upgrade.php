<?php
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


?>
