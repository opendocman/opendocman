<?php
/*
index.php - main login form
Copyright (C) 2002-2013 Stephen Lawrence Jr.

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

// Report all PHP errors (bitwise 63 may be used in PHP 3)
// includes
session_start();

/*
 * Test to see if we have the config.php file. If not, must not be installed yet.
*/

if (!file_exists('config.php')) {
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
        <link rel="stylesheet" href="templates/common/css/install.css" type="text/css"/>
    </head>
    <body>Looks like this is a new installation because we did not find a config.php file or we cannot locate the
    database. We need to create a config.php file now: <p><a href="install/setup-config.php" class="button">Create a
            Configuration File</a></p></body>
    </html>
    <?php
    exit;
}

require_once('odm-load.php');

if (!isset($_REQUEST['last_message'])) {
    $_REQUEST['last_message'] = '';
}

// Call the plugin API
callPluginMethod('onBeforeLogin');

if (isset($_SESSION['uid'])) {
    // redirect to main page
    if (isset($_REQUEST['redirection'])) {
        redirect_visitor($_REQUEST['redirection']);
    } else {
        redirect_visitor('out.php');
    }
}

if (isset($_POST['login'])) {
    if (!is_dir($GLOBALS['CONFIG']['dataDir']) || !is_writable($GLOBALS['CONFIG']['dataDir'])) {
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

        // Run the plugin API
        callPluginMethod('onAfterLogin');

        // redirect to main page
        if (isset($_REQUEST['redirection'])) {
            redirect_visitor($_REQUEST['redirection']);
        } else {
            redirect_visitor('out.php');
        }
        // close connection
    } else {
        // Login Failed
        // redirect to error page

        // Call the plugin API
        callPluginMethod('onFailedLogin');

        header('Location: error.php?ec=0');
    }
} elseif (!isset($_POST['login']) && $GLOBALS['CONFIG']['authen'] == 'mysql') {
    $redirection = (isset($_REQUEST['redirection']) ? $_REQUEST['redirection'] : '');

    $GLOBALS['smarty']->assign('redirection', htmlentities($redirection, ENT_QUOTES));
    display_smarty_template('login.tpl');
} else {
    echo 'Check your config';
}
draw_footer();
