<?php
/*
install/index.php - Automated setup/upgrade script. Remove after installation
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
// Sanity check.
if ( false ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Error: PHP is not running</title>
</head>
<body>
	<h1 id="logo"><img alt="OpenDocMan" src="../images/logo.gif" /></h1>
	<h2>Error: PHP is not running</h2>
	<p>OpenDocMan requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
</body>
</html>
<?php
exit;
}

session_start();

if ( file_exists('../config.php') && (!isset($_SESSION['datadir']) ) )
{
    echo "<p>Looks like the file 'config.php' already exists. If you need to re-install, please delete it or rename it first. You may then <a href='./'>try again</a>.</p>";
    exit;
}
// Search for the config file in parent folder
// If not found, redirect to index for install routine
if(file_exists('../config.php'))
{
    include('../config.php');
}
else
{
    Header('Location: ../index.php');
}

// Lets get a connection going
$GLOBALS['connection'] = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die ("Unable to connect to database! Are you sure that you entered the database information correctly? " . mysql_error());
$db = mysql_select_db(DB_NAME, $GLOBALS['connection']);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>OpenDocMan Installer</title>
        <link rel="stylesheet" href="../templates/common/css/install.css" type="text/css" />
    </head>

    <body>
        <div id="content">
            <img src="../images/logo.gif"><br>
            <?php
            
            if(!isset($_REQUEST['op']))
            {
                $_REQUEST['op'] = '';
            }

            switch($_REQUEST['op'])
            {
                case "install":
                    do_install();
                    break;

                case "commitinstall":

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
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
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
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 11rc2 and is upgrading
                case "update_11rc2":
                    do_update_11rc2();
                    do_update_11();
                    do_update_12rc1();
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 11 and is upgrading
                case "update_11":
                    do_update_11();
                    do_update_12rc1();
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 12rc1 and is upgrading
                case "update_12rc1":
                    do_update_12rc1();
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 12p1 and is upgrading
                case "update_12p1":
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 12p3 and is upgrading
                case "update_12p3":
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 124 and is upgrading
                case "update_124":
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 1252 and is upgrading
                case "update_125":
                    do_update_1252();
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 1256 and is upgrading
                case "update_1256":
                    do_update_1256();
                    do_update_1257();
                    do_update_1261();
                    break;

                // User has version 1257 or 126beta and is upgrading
                case "update_1257":
                    do_update_1257();
                    do_update_1261();
                    break;
      
                // User has version 1261 and is upgrading
                case "update_1261":
                    do_update_1261();
                    break;
                
                default:
                    print_intro();
                    break;
            }
            
            function do_install()
            {
                define('ODM_INSTALLING', 'true');
                echo 'Checking that templates_c folder is writeable... ';
                if(!is_writeable('../templates_c'))
                {
                    echo 'templates_c folder is <strong>Not writeable</strong> - Fix and go <a href="javascript: history.back()" class="button">Back</a>';
                    exit;
                }
                else
                {
                    echo 'OK';
                }
                echo '<br />installing...<br>';

                // Create database
                $result = mysql_query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`")
                        or die("<br>Unable to Create Database - Error in query:" . mysql_error());

                echo 'Database Created<br />';
                mysql_select_db(DB_NAME) or die (mysql_error() . "<br>Unable to select database.</font>");

                echo 'Database Selected<br />';
                include('../config.php');
                include_once("odm.php");
                echo 'All Done with installation! <p><strong>Username: admin</strong></p><p><strong>Password (WRITE IT DOWN): ' . $_SESSION['adminpass'] . '</strong></p></br />Click <a href="../settings.php?submit=update">HERE</a> to edit your site settings';
            } // End Install

            function do_update_10()
            {
                echo 'Updating version 1.0<br>';

                // Call each version, starting with the oldest. Upgrade from one to the next until done
                //include("install/upgrade_09.php");
                include("../config.php");
                include("upgrade_10.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }
            function do_update_11rc1()
            {
                echo 'Updating version 1.1rc1<br>';
                include("../config.php");
                include("upgrade_11rc1.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_11rc2()
            {
                echo 'Updating version 1.1rc2<br>';
                include("../config.php");
                include("upgrade_11rc2.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_11()
            {
                echo 'Updating version 1.1<br>';
                include("../config.php");
                include("upgrade_11.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_12rc1()
            {
                echo 'Updating version 1.2rc1<br>';
                include("../config.php");
                include("upgrade_12rc1.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_12p1()
            {
                echo 'Updating from version 1.2p1 to 1.2p2<br>';
                include("../config.php");
                include("upgrade_12p1.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_12p3()
            {
                echo 'Updating from version 1.2p3 to 1.2.4<br>';
                include("../config.php");
                include("upgrade_12p3.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_124()
            {
                echo 'Updating from version 1.2.4 to 1.2.5<br>';
                include("../config.php");
                include("upgrade_124.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_125()
            {
                echo 'Updating from version 1.2.5.2 to 1.2.5.3<br>';
                include("../config.php");
                include("upgrade_125.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_1256()
            {
                echo 'Updating from version 1.2.5.6 to 1.2.5.7...<br />';
                include("../config.php");
                include("upgrade_1256.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }

            function do_update_1257()
            {
                echo 'Updating from version 1.2.5.7...<br />';
                include("../config.php");
                include("upgrade_1257.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }
            function do_update_1261()
            {
                echo 'Updating from version 1.2.6.1...<br />';
                include("../config.php");
                include("upgrade_1261.php");
                echo 'All Done with update! Click <a href="../index.php">HERE</a> to login<br>';
            }            
            function print_intro()
            {

                include_once('../version.php');
    ?>
            <h3>Welcome to the OpenDocMan Installer Tool</h3>
        </div>
        <hr>
        <table>
            <tr>
                <td><a href="../docs/opendocman.txt" target="#main" >Installation Instructions (text)</a><br><br></td>
            </tr>
        </table>

        <table align="center">
            <tr>
                <td><strong>Please BACKUP all data and files before proceeding!</strong><br><br></td>
            </tr>
            <tr>
                <td>Please choose one from the following based on your current version.<br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=install" onclick="javascript:return confirm('are you sure? This will modify the database you have configured in config.php. Only use this option for a FRESH INSTALL.');">New installation of the v<?php echo $GLOBALS['CONFIG']['current_version']; ?> release of OpenDocMan (Will wipe any current data!)</a><br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=update_1261">Upgrade from version 1.2.6.1</a><br><br></td>
            </tr>            
            <tr>
                <td><a href="index.php?op=update_1257">Upgrade from versions 1.2.5.7 - 1.2.6beta</a><br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=update_1256">Upgrade from version 1.2.5.3</a><br><br></td>
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
