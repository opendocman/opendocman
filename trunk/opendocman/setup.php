<?php
/*
setup.php - Automated setup/upgrade script. Remove after installation
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>OpenDocMan Upgrade/Installation</title>
</head>

<body>
<center>
<img src="images/logo.gif"><br>
<?php


switch(@$_REQUEST['op']) {
    
    case "install":
         get_info();
         break;

    case "commitinstall":
         do_install();
         break;
    // User has version 1.0 and is upgrading
    case "update_10":
         do_update_10();
         do_update_11rc1();
         do_update_11rc2();
         do_update_11();
         do_update_12rc1();
         break;
   // User has version 11rc1 and is upgrading 
   case "update_11rc1":
         do_update_11rc1();
         do_update_11rc2();
         do_update_11();
         do_update_12rc1();
         break;

   // User has version 11rc2 and is upgrading 
   case "update_11rc2":
         do_update_11rc2();
         do_update_11();
         do_update_12rc1();
         break;

   // User has version 11 and is upgrading 
   case "update_11":
         do_update_11();
         do_update_12rc1();
         break;

   // User has version 12rc1 and is upgrading 
   case "update_12rc1":
         do_update_12rc1();
         break;

    default:
         print_intro();
         break;
}




function get_info()
{
?>
Please complete the following form to create your new database
<form name="newinstall" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
 <table align="center">
  <tr>
   <td>
    <input type="text" name="rootname" value="root"> Mysql Root User <br>
    <input type="password" name="rootpass"> Mysql Root Password <br>
    <input type="text" name="roothost" value="localhost"> Mysql Hostname <br>
    <input type="text" name="database" value="opendocman"> New Database Name <br>
    <input type="text" name="username" value="opendocman"> New Database User Name<br>
    <input type="password" name="password"> New Database Password<br>
    <input type="submit" name="op" value="commitinstall"><br>
   </td>
  </tr>
 </table>
</form>
    
<?php
}

function do_install()
{
        echo 'installing...<br>';


        mysql_connect($_REQUEST['roothost'], $_REQUEST['rootname'], $_REQUEST['rootpass']) or die ("Unable to connect!");

        // Create database
        $result = mysql_query("
        DROP DATABASE IF EXISTS $_REQUEST[database]
        ") or die("<br>Unable to Create Database - Error in query:" . mysql_error());

        $result = mysql_query("
        CREATE DATABASE $_REQUEST[database]
        ") or die("<br>Unable to Create Database - Error in query:" . mysql_error());

        echo 'Database Created<br>';

        mysql_select_db($_REQUEST['database']) or die (mysql_error() . "<br>Unable to select database.</font>");

        echo 'Database Selected<br>';

        // Grant privs
        $result = mysql_query("
	GRANT ALL ON $_REQUEST[database].* to $_POST[username]@$_POST[roothost] identified by '$_REQUEST[password]'") or die("<br>Could not set GRANT;
");
        echo 'Grant is set<br>';

        $result = mysql_query("
        FLUSH PRIVILEGES
        ") or die("<br>Unable to Create Database - Error in query:" . mysql_error());

        include("install/odm.php");
        include("config.php");
        echo 'All Done with installation! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login';
} // End Install


function do_update_10()
{
        echo 'Updating version 1.0<br>';        
        
        // Call each version, starting with th oldest. Upgrade from one to the next until done
        //include("install/upgrade_09.php");
        include("config.php");
        include("install/upgrade_10.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}
function do_update_11rc1()
{
        echo 'Updating version 1.1rc1<br>';        
        include("config.php");
        include("install/upgrade_11rc1.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
        
}

function do_update_11rc2()
{
        echo 'Updating version 1.1rc2<br>';        
        include("config.php");
        include("install/upgrade_11rc2.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_11()
{
        echo 'Updating version 1.1<br>';        
        include("config.php");
        include("install/upgrade_11.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_12rc1()
{
        echo 'Updating version 1.2rc1<br>';        
        include("config.php");
        include("install/upgrade_12rc1.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}


function print_intro()
{
?>
<h3>Welcome to the OpenDocMan Configuration Tool</h3>
</center>
<table align="center">
 <tr>
  <td><strong>Please BACKUP all data before proceeding!</strong><br><br></td>
 </tr>
 <tr>
  <td>Please choose one from the following based on your current version:<br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=install">New Installation (Will wipe any current data!)</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_10">Upgrade from version 1.0</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_11rc1">Upgrade from version 1.1rc1</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_11rc2">Upgrade from version 1.1rc2</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_11">Upgrade from version 1.1</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_12rc1">Upgrade from version 1.2rc1</a><br><br></td>
 </tr>
</table>
<hr>
<table>
 <tr>
  <td><a href="docs/opendocman.html" target="#main" >Installation Instructions (html)</a><br><br></td>
 </tr>
 <tr>
  <td><a href="docs/opendocman.pdf" target="#main" >Installation Instructions (pdf)</a><br><br></td>
 </tr>
</table>
<?php
}

?>
</body>
</html>
