
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
         break;
   // User has version 11rc1 and is upgrading 
/*    case "update_11rc1":
         do_update_10();
         do_update_11rc1();
         break;
*/
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
    <input type="text" name="rootname"> Mysql Root User <br>
    <input type="password" name="rootpass"> Mysql Root Password <br>
    <input type="text" name="roothost"> Mysql Hostname <br>
    <input type="text" name="database"> New Database Name <br>
    <input type="text" name="username"> New Database User Name<br>
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
        $result = mysql_query("CREATE DATABASE $_REQUEST[database]") or die("<br>Unable to Create Database - Error in query:" . mysql_error());

        mysql_select_db($_REQUEST['database']) or die (mysql_error() . "<br>Unable to select database.</font>");

        // Grant privs
        $result = mysql_query("GRANT ALL ON $_REQUEST[database].* to $_REQUEST[username] identified by '$_REQUEST[password]'") or die("<br>Could not set GRANT");

        include("install/odm.php");
        include("config.php");
        echo 'All Done with installation! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login';
} // End Install


function do_update_10()
{
        echo 'Updating';        
        mysql_connect($GLOBALS['hostname'], $GLOBALS['user'], $GLOBALS['pass']) or die ("Unable to connect!"); 
        mysql_select_db($GLOBALS['database']) or die (mysql_error() . "<br><font class=\"pn-failed\">Unable to select database.</font>");
        
        // Call each version, starting with th oldest. Upgrade from one to the next until done
        //include("install/upgrade_09.php");
        include("install/upgrade_10.php");
        echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login';
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
  <td>Please choose one from the following:<br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=install">New Installation</a><br><br></td>
 </tr>
 <tr>
  <td><a href="setup.php?op=update_10">Upgrade from version 1.0</a><br><br></td>
 </tr>
</table>
<?php
}

?>
</body>
</html>
