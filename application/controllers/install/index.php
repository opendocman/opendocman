<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Automated setup/upgrade script
// Sanity check.
if (false) {
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Error: PHP is not running</title>
    </head>
    <body>
    <h1 id="logo"><img alt="OpenDocMan" src="../images/logo.gif"/></h1>

    <h2>Error: PHP is not running</h2>

    <p>OpenDocMan requires that your web server is running PHP. Your server does not have PHP installed, or PHP is
        turned off.</p>
    </body>
    </html>
    <?php
    exit;
}

session_start();

// Include version file to get required_db_version
require_once(__DIR__ . '/../../version.php');

// Search for the config file in parent folder and the docker-compose configs mount directory
// If not found, redirect to index for install routine
if (file_exists(dirname(__FILE__) . '/../../configs/config.php')) {
    include(dirname(__FILE__) . '/../../configs/config.php');
} elseif (file_exists(dirname(__FILE__) . '/../../configs/docker-configs/config.php'))  {
    include(dirname(__FILE__) . '/../../configs/docker-configs/config.php');
} else {
    Header('Location: ../../index');
}

use Aura\Html\Escaper as e;

// Lets get a connection going
$dsn = "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8";
try {
    $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS);
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . " - Please make sure you have created a database<br/>";
    die();
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_SESSION['db_prefix'] = !empty($_SESSION['db_prefix']) ? $_SESSION['db_prefix'] : $GLOBALS['CONFIG']['db_prefix'];


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>OpenDocMan Installer</title>
    <link rel="stylesheet" href="../../css/install.css" type="text/css"/>
</head>

<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <?php

    if (!isset($_REQUEST['op'])) {
        $_REQUEST['op'] = '';
    }

    switch ($_REQUEST['op']) {
        case "install":
            do_install($pdo);
            break;

        // User has version 1.0 and is upgrading
        case "update_10":
            do_update_10();
            do_update_11rc2();
            do_update_11();
            do_update_12p1();
            do_update_12p3();
            do_update_124();
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 11rc2 and is upgrading
        case "update_11rc2":
            do_update_11rc2();
            do_update_11();
            do_update_12p1();
            do_update_12p3();
            do_update_124();
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 11 and is upgrading
        case "update_11":
            do_update_11();
            do_update_12p1();
            do_update_12p3();
            do_update_124();
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
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
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 12p3 and is upgrading
        case "update_12p3":
            do_update_12p3();
            do_update_124();
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 124 and is upgrading
        case "update_124":
            do_update_124();
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1252 and is upgrading
        case "update_1252":
            do_update_1252();
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1256 and is upgrading
        case "update_1256":
            do_update_1256();
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1257 or 126beta and is upgrading
        case "update_1257":
            do_update_1257();
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1261 and is upgrading
        case "update_1261":
            do_update_1261();
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1262 and is upgrading
        case "update_1262":
            do_update_1262();
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has version 1262 and is upgrading
        case "update_1263":
            do_update_1263();
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has DB version 128 and is upgrading
        case "update_128":
            do_update_128();
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has DB version 129 and is upgrading
        case "update_129":
            do_update_129();
            do_update_130();
            do_update_136();
            break;

        // User has DB version 130 and is upgrading
        case "update_130":
            do_update_130();
            do_update_136();
            break;

        // User has DB version 136 and is upgrading
        case "update_136":
            do_update_136();
            break;

        default:
            print_intro($pdo);
            break;
    }

    function do_install(PDO $pdo)
    {
        // Check if this is a forced fresh installation
        $force_fresh = isset($_GET['force_fresh']) && $_GET['force_fresh'] == '1';

        if ($force_fresh) {
            // Drop all existing tables for fresh installation
            $prefix = !empty($_SESSION['db_prefix']) ? $_SESSION['db_prefix'] : $GLOBALS['CONFIG']['db_prefix'];

            try {
                // Get list of tables with our prefix
                $stmt = $pdo->prepare("SHOW TABLES LIKE :prefix");
                $stmt->execute([':prefix' => $prefix . '%']);
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Drop each table
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                }

                echo "<p style='color: green;'>Existing tables dropped successfully. Proceeding with fresh installation...</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>Note: Some tables may not have existed. Proceeding with installation...</p>";
            }
        }

        // Continue with normal installation process
        define('ODM_INSTALLING', 'true');
        echo 'Checking that templates_c folder is writable...<br />';
        if (!is_writable(ABSPATH . 'templates_c')) {
            echo 'templates_c folder is <strong>Not writable</strong> - Fix and go <a href="javascript: history.back()" class="button">Back</a><br />';
            exit;
        } else {
            echo 'OK<br />';
        }
        echo '<br />installing...<br>';
        // Create database
        $query = "CREATE DATABASE IF NOT EXISTS `" . APP_DB_NAME . "`";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        echo 'Database Created<br />';

        include_once("odm.php");

        // Helper function to get environment variables with fallbacks
        function getEnvVar($name, $default = null) {
            if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
                return $_ENV[$name];
            }
            $value = getenv($name);
            if ($value !== false && $value !== '') {
                return $value;
            }
            if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
                return $_SERVER[$name];
            }
            return $default;
        }

        // Enhanced completion message for Docker environments
        echo '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3 style="color: #155724; margin-top: 0;">🎉 Installation Complete!</h3>';

        if (getEnvVar('IS_DOCKER') === 'true') {
            echo '<p><strong>🐳 Docker Environment Detected</strong></p>';
            $http_port = getEnvVar('HTTP_PORT', '8080');
            $hostname = getEnvVar('ODM_HOSTNAME', 'localhost');
            echo '<p><strong>Access URL:</strong> <a href="http://' . $hostname . ':' . $http_port . '" target="_blank">http://' . $hostname . ':' . $http_port . '</a></p>';
        }

        echo '<p><strong>Login Credentials:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Username:</strong> admin</li>';
        echo '<li><strong>Password:</strong> <code style="background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px;">' . $_SESSION['adminpass'] . '</code>';

        if (getEnvVar('IS_DOCKER') === 'true') {
            echo ' <em>(from environment)</em>';
        } else {
            echo ' <em>(WRITE THIS DOWN!)</em>';
        }

        echo '</li>';
        echo '</ul>';

        if (getEnvVar('IS_DOCKER') === 'true') {
            echo '<p><strong>💡 Next Steps:</strong></p>';
            echo '<ul>';
            echo '<li>Your admin password is stored in your <code>.env</code> file</li>';
            echo '<li>You can change settings using the admin panel</li>';
            echo '<li>Use <code>make logs</code> to view application logs</li>';
            echo '<li>Use <code>make backup</code> to create backups</li>';
            echo '</ul>';
        }

        echo '</div>';
        echo '<p><a href="../settings?submit=update" class="button">Configure Site Settings</a></p>';

        unset($_SESSION['datadir']);
    } // End Install

    /**
     * Call each version, starting with the oldest. Upgrade from one to the next until done
     */

    function do_update_10()
    {
        echo 'Updating DB version 1.0...<br>';
        include("upgrade_10.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_11rc2()
    {
        echo 'Updating DB version 1.1rc2...<br>';
        include("upgrade_11rc2.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_11()
    {
        echo 'Updating DB version 1.1...<br>';
        include("upgrade_11.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_12p1()
    {
        echo 'Updating from DB version 1.2p1...<br>';
        include("upgrade_12p1.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_12p3()
    {
        echo 'Updating from DB version 1.2p3...<br>';
        include("upgrade_12p3.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_124()
    {
        echo 'Updating from DB version 1.2.4...<br>';
        include("upgrade_124.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1252()
    {
        echo 'Updating from DB version 1.2.5.2...<br>';
        include("upgrade_1252.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1256()
    {
        echo 'Updating from DB version 1.2.5.6...<br />';
        include("upgrade_1256.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1257()
    {
        echo 'Updating from DB version 1.2.5.7...<br />';
        include("upgrade_1257.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1261()
    {
        echo 'Updating from DB version 1.2.6.1...<br />';
        include("upgrade_1261.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1262()
    {
        echo 'Updating from DB version 1.2.6.2...<br />';
        include("upgrade_1262.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_1263()
    {
        echo 'Updating from DB version 1.2.6.3...<br />';
        include("upgrade_1263.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_128()
    {
        echo 'Updating from DB versions 1.2.8...<br />';
        include("upgrade_128.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }

    function do_update_129()
    {
        echo 'Updating from DB versions 1.2.9...<br />';
        include("upgrade_129.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }
    function do_update_130()
    {
        echo 'Updating from DB versions 1.3.0...<br />';
        include("upgrade_130.php");
        echo 'All Done with update! Click <a href="../index">HERE</a> to login<br>';
    }
    function do_update_136()
    {
        echo 'Updating from DB versions 1.3.6...<br />';
        include("../configs/config.php");
        include("upgrade_136.php");
        echo '<hr style="border: 2px solid green; margin: 20px 0;">';
        echo '<h3 style="color: green;">✓ Database Upgrade Complete!</h3>';
        echo '<p><strong>Your database has been successfully upgraded.</strong></p>';
        echo '<br>';
        echo '<p><a href="../index" style="background-color: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px;">Continue to Application</a></p>';
        echo '<hr style="border: 2px solid green; margin: 20px 0;">';
    }

    function print_intro(PDO $pdo)
    {
        include_once(__DIR__ . '/../../version.php');
        include_once(__DIR__ . '/../../models/Settings.class.php');

        $prefix = !empty($_SESSION['db_prefix']) ? $_SESSION['db_prefix'] : $GLOBALS['CONFIG']['db_prefix'];
        $db_version = Settings::get_db_version($pdo, $prefix);
        $is_upgrade = ($db_version != $GLOBALS['CONFIG']['required_db_version']);

        ?>
    <h3>Welcome to the OpenDocMan Database Installer/Updater Tool</h3>
</div>
<hr>
<table>
    <tr>
        <td><a href="../../../docs/opendocman.txt" target="#main">Installation Instructions (text)</a><br><br></td>
    </tr>
</table>

<table align="center">
    <tr>
        <td><strong>Please BACKUP all data and files before proceeding!</strong><br><br></td>
    </tr>
    <tr>
        <td>
            Please choose one from the following based on your current version.<br><br>
            Note: If you are updating and your current version # is lower than the newest upgrade listed below then you
            have database updates to perform. <br/><br/>
        </td>
    </tr>
    <?php if ($db_version == 'Unknown') {
    ?>
        <tr>
            <td>New Installation (Will wipe any current data!)<br/><br/></td>
        </tr>
        <tr>
        <td>
            <a href="/install/index?op=install" class="button" onclick="return confirm('Are you sure? This will modify the database you have configured in configs/config.php. Only use this option for a FRESH INSTALL.')">
                Click HERE To set up database for v<?php echo $GLOBALS['CONFIG']['current_version'];
    ?> release of OpenDocMan </a><br /><br />
        </td>
        <?php

} elseif ($is_upgrade) {
    ?>
        <tr>
            <td>Your current Database schema version: <strong><?php echo(htmlentities($db_version));
    ?></strong><br/><br/>
            Required Database schema version: <?php echo $GLOBALS['CONFIG']['required_db_version'];
    ?><br/><br />
            </td>
        </tr>
        <tr>
            <td>Upgrade your current database from a previous version<br/><br/></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_136">Upgrade from DB schema version 1.3.6</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_130">Upgrade from DB schema version 1.3.0</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_129">Upgrade from DB schema version 1.2.9</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_128">Upgrade from DB schema version 1.2.8</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1263">Upgrade from DB schema version 1.2.6.3</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1262">Upgrade from DB schema version 1.2.6.2</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1261">Upgrade from DB schema version 1.2.6.1</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1257">Upgrade from DB schema version 1.2.5.7</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1256">Upgrade from DB schema version 1.2.5.6</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_1252">Upgrade from DB schema version 1.2.5.2</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_124">Upgrade from DB schema version 1.2.4</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_12p3">Upgrade from DB schema version 1.2p3</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_12p1">Upgrade from DB schema version 1.2p1</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_11">Upgrade from DB schema version 1.1</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_11rc2">Upgrade from DB schema version 1.1rc2</a><br><br></td>
        </tr>
        <tr>
            <td><a href="/install/index?op=update_10">Upgrade from DB schema version 1.0</a><br><br></td>
        </tr>
        <tr>
            <td><hr style="margin: 20px 0;"></td>
        </tr>
        <tr>
            <td><strong style="color: #ff6b6b;">DANGER ZONE</strong><br/><br/></td>
        </tr>
        <tr>
            <td><strong>FORCE FRESH INSTALLATION</strong> (Will delete ALL existing data!)<br/><br/></td>
        </tr>
        <tr>
            <td>
                <a href="/install/index?op=install&force_fresh=1" class="button" onclick="return confirm('WARNING: This will DELETE ALL existing data and create a fresh installation. Are you absolutely sure?')" style="background-color: #ff6b6b; color: white; padding: 10px;">
                    FORCE Fresh Install - DELETE ALL DATA
                </a><br/><br/>
            </td>
        </tr>
        <?php

} else {
    ?>
        <tr>
            <td>
                Nothing to update<br><br>
                Click <a href="../index">HERE</a> to login<br>
            </td>
        </tr>
        <?php

}
        ?>
</table>
<?php

    }
?>
</body>
</html>
