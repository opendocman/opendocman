<?php
/*
setup.php - Automated setup/upgrade script. Remove after installation
Copyright (C) 2002-2010  Stephen Lawrence

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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>OpenDocMan Installer</title>
    </head>

    <body>
        <center>
            <img src="../images/logo.gif"><br>
            <?php


            switch(@$_REQUEST['op'])
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
                    do_update_1256();
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
                    do_update_1256();
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
                    do_update_1256();
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
                    do_update_1256();
                    break;

                // User has version 12rc1 and is upgrading
                case "update_12rc1":
                    do_update_12rc1();
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1256();
                    break;

                // User has version 12p1 and is upgrading
                case "update_12p1":
                    do_update_12p1();
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1256();
                    break;

                // User has version 12p3 and is upgrading
                case "update_12p3":
                    do_update_12p3();
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1256();
                    break;

                // User has version 124 and is upgrading
                case "update_124":
                    do_update_124();
                    do_update_1252();
                    do_update_1256();
                    do_update_1256();
                    break;

                // User has version 1252 and is upgrading
                case "update_125":
                    do_update_1252();
                    do_update_1256();
                    do_update_1256();
                    break;

                // User has version 1256 and is upgrading
                case "update_1256":
                    do_update_1256();
                    do_update_126();
                    break;

                // User has version 1257 and is upgrading
                case "update_126":
                    do_update_126();
                    break;

                default:
                    print_intro();
                    break;
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


                include_once("../config.php");

                mysql_connect($GLOBALS['hostname'], $GLOBALS['user'], $GLOBALS['pass']) or die ("Unable to connect!");

                // Create database
                $result = mysql_query("DROP DATABASE IF EXISTS {$GLOBALS['database']}")
                        or die("<br>Unable to Create Database - Error in query:" . mysql_error());

                $result = mysql_query("CREATE DATABASE {$GLOBALS['database']}")
                        or die("<br>Unable to Create Database - Error in query:" . mysql_error());

                echo 'Database Created<br />';

                mysql_select_db($GLOBALS['database']) or die (mysql_error() . "<br>Unable to select database.</font>");

                echo 'Database Selected<br />';

                include_once("odm.php");
                echo 'All Done with installation! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login';
            } // End Install

            function do_update_10()
            {
                echo 'Updating version 1.0<br>';

                // Call each version, starting with the oldest. Upgrade from one to the next until done
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
                echo 'Updating from version 1.2.5.2 to 1.2.5.3<br>';
                include("../config.php");
                include("upgrade_125.php");
                echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
            }

            function do_update_1256()
            {
                echo 'Updating from version 1.2.5.6 to 1.2.5.7<br>';
                include("../config.php");
                include("upgrade_1256.php");
                echo 'All Done with update! Click <a href="' . $GLOBALS['CONFIG']['base_url'] . '">HERE</a> to login<br>';
            }

            function do_update_126()
            {
                echo 'Updating from version 1.2.5.7 to 1.2.6<br>';
                include("../config.php");
                include("upgrade_126.php");
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
                <td><strong>Please BACKUP all data and files before proceeding!</strong><br><br></td>
            </tr>
            <tr>
                <td>Please choose one from the following based on your current version <?php echo $GLOBALS['CONFIG']['current_version']; ?> (look in your config.php for your version prior to 1.2.5). <br />After 1.2.4 check in the file "version.php":<br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=install" onClick="javascript: alert('are you sure? This will wipe out the database you have configured in config.php. Only use this option for a FRESH INSTALL.');">New installation of the v<?php echo $GLOBALS['CONFIG']['current_version']; ?> release of OpenDocMan (Will wipe any current data!)</a><br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=update_1257">Upgrade versions 1.2.5.7 - 1.2.6</a><br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=update_1256">Upgrade versions 1.2.5.3 - 1.2.5.6</a><br><br></td>
            </tr>
            <tr>
                <td><a href="index.php?op=update_125">Upgrade versions 1.2.5 - 1.2.5.2</a><br><br></td>
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
