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

/**
 * Retrieves and creates the configs/config.php file.
 *
 * The permissions for the base directory must allow for writing files in order
 * for the configs/config.php to be created using this page.
 *
 * @package OpenDocMan
 * @subpackage Administration
 */
session_start();
/**
 * We are installing.
 *
 * @package OpenDocMan
 */
define('ODM_INSTALLING', true);

/**
 * We are blissfully unaware of anything.
 */
define('ODM_SETUP_CONFIG', true);

/**
 * Disable error reporting
 *
 * Set this to error_reporting( E_ALL ) or error_reporting( E_ALL | E_STRICT ) for debugging
 */
error_reporting(15);

define('ABSPATH', dirname(dirname(dirname(__FILE__))) . '/');

/**
 * Load environment variables into $_ENV if not already available
 * This fixes issues where Docker environment variables aren't accessible via $_ENV
 */
if (!isset($_ENV['IS_DOCKER']) && getenv('IS_DOCKER')) {
    // Force populate $_ENV from environment if it's not already populated
    foreach ($_SERVER as $key => $value) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
    
    // Also try to get environment variables directly
    $env_vars = [
        'IS_DOCKER', 'ADMIN_PASSWORD', 'SESSION_SECRET',
        'APP_DB_HOST', 'APP_DB_NAME', 'APP_DB_USER', 'APP_DB_PASS',
        'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD',
        'ODM_DATA_DIR', 'DB_PREFIX', 'ODM_HOSTNAME',
        'HTTP_PORT', 'HTTPS_PORT', 'DB_EXTERNAL_PORT'
    ];
    
    foreach ($env_vars as $var) {
        if (!isset($_ENV[$var])) {
            $value = getenv($var);
            if ($value !== false) {
                $_ENV[$var] = $value;
            }
        }
    }
}

/**
 * Helper function to get environment variables with fallback methods
 */
function getEnvVar($name, $default = null) {
    // Try $_ENV first
    if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
        return $_ENV[$name];
    }
    
    // Try getenv() as fallback
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    // Try $_SERVER as last resort
    if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
        return $_SERVER[$name];
    }
    
    return $default;
}

/**#@-*/

if (!file_exists(ABSPATH . 'configs/config-sample.php')) {
    echo('Sorry, I need a config-sample.php file to work from. Please re-upload this file from your OpenDocMan installation.');
    exit;
}

// Check if configs/config.php has been created
if (file_exists(ABSPATH . 'configs/config.php') || file_exists(ABSPATH . 'configs/docker-configs/config.php')) {
    echo("<p>The file 'configs/config.php' already exists. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href=''>installing now</a>.</p>");
    exit;
}

if (isset($_GET['step'])) {
    $step = $_GET['step'];
} else {
    $step = 0;
}

/**
 * Display setup configs/config.php file header.
 *
 */
function display_header()
{
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OpenDocMan &rsaquo; Setup Configuration File</title>
<link rel="stylesheet" href="../../css/install.css" type="text/css" />
<script type="text/javascript" src="../../js/jquery.min.js"></script>
<script type="text/javascript" src="../../js/jquery-compatibility.js"></script>
<script type="text/javascript" src="../../js/jquery.validate.min.js"></script>
<script type="text/javascript" src="../../js/additional-methods.min.js"></script>
</head>
<body>
<h1 id="logo"><img alt="OpenDocMan" src="../images/logo.gif" /></h1>
<?php

}//end function display_header();

switch ($step) {
    case 0:
        display_header();
?>

<p>Welcome to OpenDocMan. Before getting started, we need some information on the database. You will need to know the following items before proceeding.</p>
<ol>
	<li>Database name</li>
	<li>Database username</li>
	<li>Database password</li>
	<li>Database host</li>
	<li>Table prefix (if you want to run more than one OpenDocMan in a single database) </li>
</ol>
<p><strong>You will also need to create a directory (your "dataDir") where you plan to store your uploaded files on the server.</strong> This directory must be writable by the web server but preferably NOT inside your public html folder. The main reason for locating the folder outside or your web document root is so that people won't be able to guess at a URL to directly access your files, bypassing the access restrictions that OpenDocMan puts in place.</p>
<p>You can update your web server configuration file to prevent visitors from browsing your files directly.

<?php
echo '<pre>';
echo htmlentities('
<Directory "/path/to/your/documents/dataDir">
  Deny all
</Directory>
');
echo '</pre>';

echo '<p>Or For newer version of apache</p>';

echo '<pre>';
echo htmlentities('
<Directory "/path/to/your/documents/dataDir">
  Deny From all
</Directory>
');
echo '</pre>';
?>
    <p>
Or don't put your dataDir directory in the web space at all.<br />

Or in a .htaccess file in the dataDir directory:<br />

<pre>
order allow,deny
deny from all
</pre>
    </p>

<p>If for any reason this automatic file creation doesn't work, don't worry. All this does is fill in the database information to a configuration file. You may also simply open <code>config-sample.php</code> in a text editor, fill in your information, and save it as <code>configs/config.php</code> and import the <code>database.sql</code> file into your database.</p>

<p class="step"><a href="setup-config?step=1" class="button">Let&#8217;s go!</a></p>
<?php
    break;

    case 1:
        display_header();
    ?>
<form method="post" id="configform" action="setup-config?step=2">
	<p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
	<?php if (getEnvVar('IS_DOCKER') === 'true'): ?>
	<div style="background-color: #e7f3ff; border: 1px solid #b3d4fc; padding: 10px; margin: 10px 0; border-radius: 4px;">
		<strong>🐳 Docker Environment Detected:</strong> Configuration values have been pre-populated from your environment variables (.env file). You can modify them below if needed.
	</div>
	<?php endif; ?>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="dbname">Database Name</label></th>
			<td><input name="dbname" id="dbname" type="text" size="25" value="<?php echo getEnvVar('APP_DB_NAME', 'opendocman'); ?>" class="required" minlength="2" /></td>
			<td>The name of the database you want to run OpenDocMan in. </td>
		</tr>
		<tr>
			<th scope="row"><label for="uname">User Name</label></th>
			<td><input name="uname" id="uname" type="text" size="25" value="<?php echo getEnvVar('APP_DB_USER', 'opendocman'); ?>" class="required" minlength="2"/></td>
			<td>Your MySQL username</td>
		</tr>
		<tr>
			<th scope="row"><label for="pwd">Password</label></th>
			<td>
				<input name="pwd" id="pwd" type="password" size="25" value="<?php echo getEnvVar('APP_DB_PASS', 'opendocman'); ?>" />
				<?php if (getEnvVar('APP_DB_PASS') !== null && getEnvVar('APP_DB_PASS') !== ''): ?>
				<button type="button" id="toggleDbPassword" style="margin-left: 5px; padding: 2px 8px; font-size: 12px;">Show</button>
				<?php endif; ?>
			</td>
			<td>...and MySQL password.<?php echo (getEnvVar('APP_DB_PASS') !== null ? ' <em>(Pre-filled from environment)</em>' : ''); ?></td>
		</tr>
		<tr>
			<th scope="row"><label for="dbhost">Database Host</label></th>
			<td><input name="dbhost" id="dbhost" type="text" size="25" value="<?php echo getEnvVar('APP_DB_HOST', 'localhost'); ?>" class="required" minlength="2"/></td>
			<td>You should be able to get this info from your web host, if <code>localhost</code> does not work.
                            It can also include a port number. e.g. "hostname;port=3306" or a path to a local socket e.g. ":/path/to/socket" for the localhost.
                        </td>
		</tr>
		<tr>
			<th scope="row"><label for="prefix">Table Prefix</label></th>
			<td><input name="prefix" id="prefix" type="text" value="<?php echo getEnvVar('DB_PREFIX', 'odm_'); ?>" size="8" class="required" minlength="2"/></td>
			<td>If you want to run multiple OpenDocMan installations in a single database, change this.<?php echo (getEnvVar('DB_PREFIX') !== null ? ' <em>(Pre-filled from environment)</em>' : ''); ?></td>
		</tr>
                <tr>
			<th scope="row"><label for="adminpass">Administrator Password</label></th>
			<td>
				<input name="adminpass" id="adminpass" type="password" value="<?php echo getEnvVar('ADMIN_PASSWORD', ''); ?>" size="8" class="required" minlength="5"/>
				<button type="button" id="togglePassword" style="margin-left: 5px; padding: 2px 8px; font-size: 12px;">Show</button>
			</td>
			<td>Enter an administrator password here. Write it down! (only used for new installs)<?php echo (getEnvVar('ADMIN_PASSWORD') !== null ? ' <em>(Pre-filled from environment)</em>' : ''); ?></td>
		</tr>
		<tr>
			<th scope="row"><label for="prefix">Data Directory</label></th>
			<td colspan="2"><input name="datadir" id="datadir" type="text" value="<?php echo getEnvVar('ODM_DATA_DIR', dirname($_SERVER['DOCUMENT_ROOT']) . '/odm_data/');?>" size="45" class="required" minlength="2"/>
                            <br/>Enter in a web-writable folder that you have created on your server to store the data files. We have tried to guess for one.<br/>
                            <ul>
                                <li><em>Windows Example:</em> c:/document_repository/</li>
                                <li><em>Linux Example:</em> /var/www/document_repository/</li>
                            </ul>
                        </td>
		</tr>
	</table>
	<p class="step"><input name="submit" type="submit" value="Submit" class="button" /></p>
</form>
<script>
    $("#configform").validate();
</script>
<?php
    break;

    case 2:
        /*
         * We have the info, now lets write the config.php file
         */
        define('APP_DB_NAME', sanitizeme(trim($_POST['dbname'])));
        define('APP_DB_USER', sanitizeme(trim($_POST['uname'])));
        define('APP_DB_PASS', sanitizeme(trim($_POST['pwd'])));
        define('APP_DB_HOST', sanitizeme(trim($_POST['dbhost'])));

        // We'll fail here if the values are no good.
        $dsn = "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8";
        try {
            $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $prefix  = sanitizeme(trim($_POST['prefix']));
        $adminpass  = sanitizeme(trim($_POST['adminpass']));
        $datadir  = sanitizeme(trim($_POST['datadir']));

        // Validate admin password is not empty
        if (empty($adminpass)) {
            die('<strong>ERROR</strong>: "Administrator Password" cannot be empty. Please provide a password of at least 5 characters.');
        }
        
        // Validate admin password length
        if (strlen($adminpass) < 5) {
            die('<strong>ERROR</strong>: "Administrator Password" must be at least 5 characters long.');
        }

        // Clean up the datadir a bit to make sure it ends with slash
        if (substr($datadir, -1) != '/') {
            $datadir .= '/';
        }

        // If no prefix is set, use default
        if (empty($prefix)) {
            $prefix = 'odm_';
        }

        // Validate $prefix: it can only contain letters, numbers and underscores
        if (preg_match('|[^a-z0-9_]|i', $prefix)) {
            die('<strong>ERROR</strong>: "Table Prefix" can only contain numbers, letters, and underscores.');
        }

         $_SESSION['db_prefix'] = $prefix;
         $_SESSION['datadir'] = $datadir;
         $_SESSION['adminpass'] = $adminpass;

        // Here we check their datadir value and try to create the folder. If we cannot, we will warn them.
        if (!is_dir($datadir)) {
            if (!mkdir($datadir)) {
                echo 'Sorry, we were unable to create the data directory folder. You will need to create it manually at ' . $datadir;
            }
        } elseif (!is_writable($datadir)) {
            echo 'The data directory exists, but your web server cannot write to it. Please verify the folder permissions are correct on ' . $datadir;
        }

        // Verify the templates_c is writable
        if (!is_writable(ABSPATH . 'templates_c')) {
            echo 'Sorry, we were unable to write to the templates_c folder. You will need to make sure that ' . ABSPATH . 'templates_c is writable by the web server';
        }

    // Grab the sample as our source file for building a config.php file
    $configFileSource = file(ABSPATH . 'configs/config-sample.php');

    // Now replace the default config values with the real ones
    foreach ($configFileSource as $line_num => $line) {
        switch (substr($line, 4, 20)) {
            case "define('APP_DB_NAME'":
                $configFileSource[$line_num] = str_replace("database_name_here", APP_DB_NAME, $line);
                break;
            case "define('APP_DB_USER'":
                $configFileSource[$line_num] = str_replace("username_here", APP_DB_USER, $line);
                break;
            case "define('APP_DB_PASS'":
                $configFileSource[$line_num] = str_replace("password_here", APP_DB_PASS, $line);
                break;
            case "define('APP_DB_HOST'":
                $configFileSource[$line_num] = str_replace("localhost", APP_DB_HOST, $line);
                break;
            case '$GLOBALS[\'CONFIG':
                $configFileSource[$line_num] = str_replace("'odm_'", "'$prefix'", $line);
                break;
        }
    }

    $config_folder = ABSPATH . (getEnvVar('IS_DOCKER') === 'true' ? 'configs/docker-configs/' : 'configs/');
    if (! is_writable($config_folder)) {
        display_header();
        ?>
<p>Sorry, but I can't write the <code>configs/config.php</code> file.</p>
<p>You can create the <code>configs/config.php</code> manually and paste the following text into it.</p>
<textarea cols="98" rows="15" class="code"><?php
        foreach ($configFileSource as $line) {
            echo htmlentities($line, ENT_COMPAT, 'UTF-8');
        }
        ?></textarea>
<p>After you've done that, click "Proceed to the installer."</p>
<p class="step"><a href="/install" class="button">Proceed to the installer</a></p>
<?php

    } else {

        $handle = fopen($config_folder . "config.php", 'w');
        foreach ($configFileSource as $line) {
            fwrite($handle, $line);
        }
        fclose($handle);
        chmod($config_folder . 'config.php', 0666);
        display_header();
        ?>
<p>Great! You've made it through this part of the installation. OpenDocMan can now communicate with your database. If you are ready, time now to&hellip;</p>

<p class="step"><a href="/install/index" class="button">Run the install</a></p>
<?php

    }
    break;
}

function cleanInput($input)
{
    $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );
    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitizeme($input)
{
    if (is_array($input)) {
        foreach ($input as $var=>$val) {
            $output[$var] = sanitizeme($val);
        }
    } else {

        //echo "Raw Input:" . $input . "<br />";
        $input  = cleanInput($input);
        $input = strip_tags($input); // Remove HTML
        $input = htmlspecialchars($input); // Convert characters
        $input = trim(rtrim(ltrim($input))); // Remove spaces
        $input = $input; // Prevent SQL Injection
        $output=$input;
    }
    if (isset($output) && $output != '') {
        return $output;
    } else {
        return false;
    }
}
?>

<script type="text/javascript">
$(document).ready(function() {
    // Password toggle functionality for pre-populated admin password
    $('#togglePassword').click(function() {
        var passwordField = $('#adminpass');
        var button = $(this);
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            button.text('Hide');
        } else {
            passwordField.attr('type', 'password');
            button.text('Show');
        }
    });
    
    // Password toggle functionality for pre-populated MySQL password
    $('#toggleDbPassword').click(function() {
        var passwordField = $('#pwd');
        var button = $(this);
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            button.text('Hide');
        } else {
            passwordField.attr('type', 'password');
            button.text('Show');
        }
    });
    
    // Form validation enhancement
    $('#configform').validate({
        rules: {
            adminpass: {
                required: true,
                minlength: 5
            }
        },
        messages: {
            adminpass: {
                required: "Administrator password is required",
                minlength: "Password must be at least 5 characters long"
            }
        }
    });
});
</script>

</body>
</html>
