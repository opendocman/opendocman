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

// Main login form

// Configure session for Docker environment
if (getenv('IS_DOCKER')) {
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_secure', false);
    ini_set('session.cookie_httponly', true);
    ini_set('session.use_strict_mode', true);
}

session_start();

$pdo = $GLOBALS['pdo'];

/*
 * Test to see if we have the config.php file. If not, must not be installed yet.
*/
if (!file_exists(__DIR__ . '/../configs/config.php') && !file_exists(__DIR__ . '/../configs/docker-configs/config.php')) {
    if (
        !extension_loaded('pdo')
        || !extension_loaded('pdo_mysql')
    ) {
        echo "<p>PHP pdo Extensions not loaded. <a href='./'>try again</a>.</p>";
        exit;
    }
    // A config file doesn't exist

    ?>
    <html>
    <head>
        <link rel="stylesheet" href="css/install.css" type="text/css"/>
    </head>
        <body>
            <h2>Looks like this is a new installation because we did not find a configs/config.php file or we cannot locate the
            database. We need to create a configs/config.php file now:</h2>
            <p><a href="install/setup-config" class="button">Create a Configuration File</a></p>
        </body>
    </html>
    <?php
    exit;
}


if (!isset($_REQUEST['last_message'])) {
    $_REQUEST['last_message'] = '';
}

// Check if system is properly installed before proceeding
if (!isset($GLOBALS['CONFIG']['authen'])) {
    // System not installed yet, redirect to installation
    header('Location: install/setup-config');
    exit;
}

// Call the plugin API (only if function exists)
if (function_exists('callPluginMethod')) {
    callPluginMethod('onBeforeLogin');
}



if (isset($_SESSION['uid'])) {
    // redirect to main page
    if (isset($_REQUEST['redirection'])) {
        redirect_visitor($_REQUEST['redirection']);
    } else {
        redirect_visitor('out');
    }
}

if (isset($_POST['login'])) {
    // Validate CSRF token for login form
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        echo "<font color=red>Security token validation failed. Please try again.</font>";
        draw_footer();
        exit;
    } elseif (!is_dir($GLOBALS['CONFIG']['dataDir']) || !is_writable($GLOBALS['CONFIG']['dataDir'])) {
        echo "<font color=red>" . msg('message_datadir_problem') . "</font>";
    }

    $frmuser = $_POST['frmuser'];
    $frmpass = $_POST['frmpass'];

    // check login and md5()
    // connect and execute query
    $query = "
      SELECT
        id,
        username,
        password
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}user
      WHERE
        username = :frmuser
      AND
        password = md5(:frmpass)
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':frmuser' => $frmuser,
        ':frmpass' => $frmpass
    ));
    $result = $stmt->fetchAll();

    if (count($result) != 1) {
        // Check old password() method
        $query = "
          SELECT
            id,
            username,
            password
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}user
          WHERE
            username = :frmuser
          AND
            password = password(:frmpass)
            ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':frmuser' => $frmuser,
            ':frmpass' => $frmpass
        ));
        $result = $stmt->fetchAll();
    }

    // if row exists - login/pass is correct
    if (count($result) == 1) {
        // register the user's ID
        $id = $result[0]['id'];

        // initiate a session
        $_SESSION['uid'] = $id;

        // Run the plugin API (only if function exists)
        if (function_exists('callPluginMethod')) {
            callPluginMethod('onAfterLogin');
        }

        // redirect to main page
        if (isset($_REQUEST['redirection'])) {
            redirect_visitor($_REQUEST['redirection']);
        } else {
            redirect_visitor('out');
        }
    } else {
        // Login Failed
        // redirect to error page

        // Call the plugin API (only if function exists)
        if (function_exists('callPluginMethod')) {
            callPluginMethod('onFailedLogin');
        }

        header('Location: error?ec=0');
    }
} elseif (!isset($_POST['login']) && $GLOBALS['CONFIG']['authen'] == 'mysql') {
    $redirection = (isset($_REQUEST['redirection']) ? $_REQUEST['redirection'] : '');
    $GLOBALS['smarty']->assign('redirection', htmlentities($redirection, ENT_QUOTES));
    display_smarty_template('login.tpl');
} else {
    echo 'Check your config';
}
draw_footer();
