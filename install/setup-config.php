<?php
/**
 * Retrieves and creates the config.php file.
 *
 * The permissions for the base directory must allow for writing files in order
 * for the config.php to be created using this page.
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
error_reporting(0);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

/**#@-*/

if (!file_exists(ABSPATH . 'config-sample.php'))
{
	echo ('Sorry, I need a config-sample.php file to work from. Please re-upload this file from your OpenDocMan installation.');
        exit;
}

$configFile = file(ABSPATH . 'config-sample.php');

// Check if config.php has been created
if (file_exists(ABSPATH . 'config.php'))
{
	echo ("<p>The file 'config.php' already exists. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href='./'>installing now</a>.</p>");
        exit;

}

if (isset($_GET['step']))
	$step = $_GET['step'];
else
	$step = 0;

/**
 * Display setup config.php file header.
 *
 */
function display_header() {
	header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OpenDocMan &rsaquo; Setup Configuration File</title>
<link rel="stylesheet" href="../templates/common/css/install.css" type="text/css" />
<script type="text/javascript" src="../includes/jquery.min.js"></script>
<script type="text/javascript" src="../includes/jquery.validate.min.js"></script>
<script type="text/javascript" src="../includes/additional-methods.min.js"></script>
</head>
<body>
<h1 id="logo"><img alt="OpenDocMan" src="../images/logo.gif" /></h1>
<?php
}//end function display_header();

switch($step) {
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

<p>If for any reason this automatic file creation doesn't work, don't worry. All this does is fill in the database information to a configuration file. You may also simply open <code>config-sample.php</code> in a text editor, fill in your information, and save it as <code>config.php</code> and import the <code>database.sql</code> file into your database.</p>

<p class="step"><a href="setup-config.php?step=1" class="button">Let&#8217;s go!</a></p>
<?php
	break;

	case 1:
		display_header();
	?>
<form method="post" id="configform" action="setup-config.php?step=2">
	<p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="dbname">Database Name</label></th>
			<td><input name="dbname" id="dbname" type="text" size="25" value="opendocman" class="required" minlength="2" /></td>
			<td>The name of the database you want to run OpenDocMan in. </td>
		</tr>
		<tr>
			<th scope="row"><label for="uname">User Name</label></th>
			<td><input name="uname" id="uname" type="text" size="25" value="username" class="required" minlength="2"/></td>
			<td>Your MySQL username</td>
		</tr>
		<tr>
			<th scope="row"><label for="pwd">Password</label></th>
			<td><input name="pwd" id="pwd" type="text" size="25" value="password" /></td>
			<td>...and MySQL password.</td>
		</tr>
		<tr>
			<th scope="row"><label for="dbhost">Database Host</label></th>
			<td><input name="dbhost" id="dbhost" type="text" size="25" value="localhost" class="required" minlength="2"/></td>
			<td>You should be able to get this info from your web host, if <code>localhost</code> does not work. 
                            It can also include a port number. e.g. "hostname:port" or a path to a local socket e.g. ":/path/to/socket" for the localhost. 
                        </td>
		</tr>
		<tr>
			<th scope="row"><label for="prefix">Table Prefix</label></th>
			<td><input name="prefix" id="prefix" type="text" value="odm_" size="8" class="required" minlength="2"/></td>
			<td>If you want to run multiple OpenDocMan installations in a single database, change this.</td>
		</tr>
                <tr>
			<th scope="row"><label for="adminpass">Administrator Password</label></th>
			<td><input name="adminpass" id="adminpass" type="text" value="" size="8" class="required" minlength="6"/></td>
			<td>Enter an administrator password here. Write it down! (only used for new installs)</td>
		</tr>
		<tr>
			<th scope="row"><label for="prefix">Data Directory</label></th>
			<td colspan="2"><input name="datadir" id="datadir" type="text" value="<?php echo dirname($_SERVER['DOCUMENT_ROOT']);?>/odm_data/" size="45" class="required" minlength="2"/>
                            <br/>Enter in a web-writable folder that you have created on your server to store the data files. We have tried to guess for one.<br/>
                            <ul>
                                <li><em>Windows Example:</em> c:/document_repository/</li>
                                <li><em>Linux Example:</em> /var/www/document_repository/</li>
                            </ul>
                        </td>
		</tr>
                <tr>
			<th scope="row"><label for="prefix">Base URL</label></th>
			<td colspan="2"><input name="baseurl" id="baseurl" type="text" size="45" class="required url2" minlength="2" value="http://<?php echo $_SERVER['HTTP_HOST'];?>/opendocman"/>
                            <br/>Enter in the root URL where OpenDocMan will be running from. Example: http://www.myhost.com/opendocman<br/>
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
        // Test the db connection.
	/**#@+
	 * @ignore
	 */
	define('DB_NAME', trim($_POST['dbname']));
	define('DB_USER', trim($_POST['uname']));
	define('DB_PASS', trim($_POST['pwd']));
	define('DB_HOST', trim($_POST['dbhost']));

	// We'll fail here if the values are no good.
        $connection = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die ("Unable to connect to database! Are you sure that you entered the database information correctly?" . mysql_error());
        $result = mysql_query("CREATE DATABASE IF NOT EXISTS `". DB_NAME . "`")
                        or die("<br>Unable to Create Database - Error in query:" . mysql_error());
        $db = mysql_select_db(DB_NAME, $connection) or die ("Can't select database. We have connected to the database so we know the username and password are correct, but we were unable to select the database name you gave us. Are you sure it exists? You might still need to create the database.");

	$dbname  = sanitizeme(trim($_POST['dbname']));
	$uname   = sanitizeme(trim($_POST['uname']));
	$passwrd = sanitizeme(trim($_POST['pwd']));
	$dbhost  = sanitizeme(trim($_POST['dbhost']));
	$prefix  = sanitizeme(trim($_POST['prefix']));
        $adminpass  = sanitizeme(trim($_POST['adminpass']));
        $datadir  = sanitizeme(trim($_POST['datadir']));
        $baseurl  = sanitizeme(trim($_POST['baseurl']));
        
        // Clean up the datadir a bit to make sure it ends with slash
        if(substr($datadir,-1) != '/')
        {
            $datadir .= '/';
        }

        // If no prefix is set, use default
        if ( empty($prefix) )
		$prefix = 'odm_';

        // Require values from form fields
	// Validate $prefix: it can only contain letters, numbers and underscores
	if ( preg_match( '|[^a-z0-9_]|i', $prefix ) )
		die('<strong>ERROR</strong>: "Table Prefix" can only contain numbers, letters, and underscores.' );
         $_SESSION['db_prefix'] = $prefix;
         $_SESSION['datadir'] = $datadir;
         $_SESSION['baseurl'] = $baseurl;
         $_SESSION['adminpass'] = $adminpass;
         
        // Here we check their datadir value and try to create the folder. If we cannot, we will warn them.
        if(!is_dir($datadir))
        {
            if(!mkdir($datadir))
            {
                echo 'Sorry, we were unable to create the data directory folder. You will need to create it manually at ' . $datadir;
            }
        }
        elseif(!is_writable($datadir))
        {
            echo 'The data directory exists, but your web server cannot write to it. Please verify the folder permissions are correct on ' . $datadir;
        }

        // Verify the templates_c is writeable
        if(!is_writable(ABSPATH . '/templates_c'))
        {
            echo 'Sorry, we were unable to write to the templates_c folder. You will need to make sure that ' . ABSPATH . '/templates_c is writeable by the web server';
        }

        // We also need to guess at their base_url value

        // Now replace the default config values with the real ones
	foreach ($configFile as $line_num => $line) {
		switch (substr($line,0,16)) {
			case "define('DB_NAME'":
				$configFile[$line_num] = str_replace("database_name_here", $dbname, $line);
				break;
			case "define('DB_USER'":
				$configFile[$line_num] = str_replace("'username_here'", "'$uname'", $line);
				break;
			case "define('DB_PASS'":
				$configFile[$line_num] = str_replace("'password_here'", "'$passwrd'", $line);
				break;
			case "define('DB_HOST'":
				$configFile[$line_num] = str_replace("localhost", $dbhost, $line);
				break;
			case '$GLOBALS[\'CONFIG':
				$configFile[$line_num] = str_replace('odm_', $prefix, $line);
				break;
		}
	}
	if ( ! is_writable(ABSPATH) ) {
		display_header();
?>
<p>Sorry, but I can't write the <code>config.php</code> file.</p>
<p>You can create the <code>config.php</code> manually and paste the following text into it.</p>
<textarea cols="98" rows="15" class="code"><?php
		foreach( $configFile as $line ) {
			echo htmlentities($line, ENT_COMPAT, 'UTF-8');
		}
?></textarea>
<p>After you've done that, click "Proceed to the installer."</p>
<p class="step"><a href="index.php" class="button">Proceed to the installer</a></p>
<?php
        }else {
            
		$handle = fopen(ABSPATH . 'config.php', 'w');
		foreach( $configFile as $line ) {
			fwrite($handle, $line);
		}
		fclose($handle);
		chmod(ABSPATH . 'config.php', 0666);
		display_header();
?>
<p>Great! You've made it through this part of the installation. OpenDocMan can now communicate with your database. If you are ready, time now to&hellip;</p>

<p class="step"><a href="index.php" class="button">Run the install</a></p>
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
    if (is_array($input))
    {
        foreach($input as $var=>$val)
        {
            $output[$var] = sanitizeme($val);
        }
    }
    else
    {
        if (get_magic_quotes_gpc())
        {
            $input = stripslashes($input);
        }
        //echo "Raw Input:" . $input . "<br />";
        $input  = cleanInput($input);
        $input = strip_tags($input); // Remove HTML
        $input = htmlspecialchars($input); // Convert characters
        $input = trim(rtrim(ltrim($input))); // Remove spaces
        $input = mysql_real_escape_string($input); // Prevent SQL Injection
        $output=$input;
    }
    if(isset($output) && $output != '')
    {
        return $output;
    }
    else
    {
        return false;
    }
}
?>
</body>
</html>
