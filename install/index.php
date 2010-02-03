<?php
/*
setup.php - Automated setup/upgrade script. Remove after installation
Copyright (C) 2002-2007  Stephen Lawrence

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
<img src="../images/logo.gif"><br>
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
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;
   // User has version 11rc1 and is upgrading 
   case "update_11rc1":
         do_update_11rc1();
         do_update_11rc2();
         do_update_11();
         do_update_12rc1();
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 11rc2 and is upgrading 
   case "update_11rc2":
         do_update_11rc2();
         do_update_11();
         do_update_12rc1();
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 11 and is upgrading 
   case "update_11":
         do_update_11();
         do_update_12rc1();
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 12rc1 and is upgrading 
   case "update_12rc1":
         do_update_12rc1();
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 12p1 and is upgrading 
   case "update_12p1":
         do_update_12p1();
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 12p3 and is upgrading 
   case "update_12p3":
         do_update_12p3();
         do_update_124();
         do_update_125();
         break;

   // User has version 124 and is upgrading 
   case "update_124":
         do_update_124();
         do_update_125();
         break;

   // User has version 125 and is upgrading 
   case "update_125":
         do_update_125();
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
        echo 'Checking that templates_c folder is writeable... ';
        if(!is_writeable('../templates_c'))
        {
            echo 'Not writeable - Fix and go <a href="javascript: history.back()">Back</a>';
            exit;
        }
        else
        {
            echo 'OK';
        }
       echo '<br />installing...<br>';


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

        include("../config.php");
        include("odm.php");
        echo 'All Done with installation! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login';
} // End Install


function do_update_10()
{
        echo 'Updating version 1.0<br>';        
        
        // Call each version, starting with th oldest. Upgrade from one to the next until done
        //include("install/upgrade_09.php");
        include("../config.php");
        include("upgrade_10.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}
function do_update_11rc1()
{
        echo 'Updating version 1.1rc1<br>';        
        include("../config.php");
        include("upgrade_11rc1.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
        
}

function do_update_11rc2()
{
        echo 'Updating version 1.1rc2<br>';        
        include("../config.php");
        include("upgrade_11rc2.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_11()
{
        echo 'Updating version 1.1<br>';        
        include("../config.php");
        include("upgrade_11.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_12rc1()
{
        echo 'Updating version 1.2rc1<br>';        
        include("../config.php");
        include("upgrade_12rc1.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_12p1()
{
        echo 'Updating from version 1.2p1 to 1.2p2<br>';        
        include("../config.php");
        include("upgrade_12p1.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_12p3()
{
        echo 'Updating from version 1.2p3 to 1.2.4<br>';        
        include("../config.php");
        include("upgrade_12p3.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_124()
{
        echo 'Updating from version 1.2.4 to 1.2.5<br>';        
        include("../config.php");
        include("upgrade_124.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function do_update_125()
{
        echo 'Updating from version 1.2.5 to 1.2.6<br>';        
        include("../config.php");
        include("upgrade_125.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
}

function print_intro()
{

    include_once('../version.php');
?>
<h3>Welcome to the OpenDocMan Configuration Tool</h3>
</center>
<hr>
<table>
 <tr>
  <td><a href="../docs/opendocman.txt" target="#main" >Installation Instructions (text)</a><br><br></td>
 </tr>
</table>

<table align="center">
 <tr>
  <td><strong>Please BACKUP all data before proceeding!</strong><br><br></td>
 </tr>
 <tr>
    <td>Please choose one from the following based on your current version <?php echo $GLOBALS['CONFIG']['current_version']; ?> (look in your config.php for your version prior to 1.2.5). <br />After 1.2.4 check in the file "version.php":<br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=install">New installation of the v<?php echo $GLOBALS['CONFIG']['current_version']; ?> release of OpenDocMan (Will wipe any current data!)</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_125">Upgrade from version 1.2.5</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_124">Upgrade from version 1.2.4</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_12p3">Upgrade from version 1.2p3</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_12p1">Upgrade from version 1.2p1</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_12rc1">Upgrade from version 1.2rc(x)</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_11">Upgrade from version 1.1</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_11rc2">Upgrade from version 1.1rc2</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_11rc1">Upgrade from version 1.1rc1</a><br><br></td>
 </tr>
 <tr>
  <td><a href="index.php?op=update_10">Upgrade from version 1.0</a><br><br></td>
 </tr>
</table>
<?php
}

?>
</body>
</html>
