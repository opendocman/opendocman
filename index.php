<?php
/*
index.php - main login form
Copyright (C) 2002, 2003, 2004  Stephen Lawrence

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
require('config.php');

if (!isset($_REQUEST['last_message']))
{
    $_REQUEST['last_message']='';
}

if(isset($_POST['login']))
{
    if(!valid_username($_POST['frmuser']))
    {
        echo "<font color=red>The username or password was invalid. Please try again.</font>";
        exit;
    }

    if(!is_dir($GLOBALS['CONFIG']['dataDir']) || !is_writeable($GLOBALS['CONFIG']['dataDir']))
    {
        echo "<font color=red>There is a problem with your dataDir. Check to make sure it exists and is writeable</font>";
        exit;
    }

    $frmuser = $_POST['frmuser'];
    $frmpass = $_POST['frmpass'];

    // Check for NIS/YP data
    if ( $GLOBALS['CONFIG']['try_nis'] == "On")
    {
        $pwent = @split(":",`ypmatch $frmuser passwd`);
        if(isset($pwent))
            $cryptpw = @crypt(stripslashes($frmpass),substr($pwent[1],0,2));
    }

    // check login and password
    // connect and execute query
    $query = "SELECT id, username, password FROM user WHERE username = '$frmuser' AND password = password('$frmpass')";
    $result = mysql_query("$query") or die ("Error in query: $query. " . mysql_error());

    // if MySQL login fails, check NIS/YP data
    if ( $GLOBALS['CONFIG']['try_nis'] == "On")
    {
        if (mysql_num_rows($result) == 0)
        {
          if (isset($pwent) && isset($cryptpw) && strcmp($cryptpw,$pwent[1]) == 0)
          {
            $query = "SELECT id, username, password FROM user WHERE username = '$frmuser'";
            $result = mysql_query("$query") or die ("Error in query: $query. " . mysql_error());
          }
        }
    }

    // if row exists - login/pass is correct
    if (mysql_num_rows($result) == 1)
    {
        // register the user's ID
        list($id, $username, $password) = mysql_fetch_row($result);
        // initiate a session
        $_SESSION['uid'] = $id;
        // redirect to main page
        if(isset($_REQUEST['redirection']))
            header('Location:' . $_REQUEST['redirection']);
        else
            header('Location:out.php');
        mysql_free_result ($result);	
        // close connection
    }
    else
        // login/pass check failed
    {
        mysql_free_result ($result);	
        // redirect to error page
        header('Location: error.php?ec=0');
    }

}
elseif($GLOBALS['CONFIG']['authen'] =='kerbauth')
{

        // check login and password
        // connect and execute query
        if (!isset($_COOKIE['AuthUser']))
        {
                header('Location: https://secureweb.ucdavis.edu:443/cgi-auth/sendback?'.$GLOBALS['CONFIG']['base_url']);
        }
        else
        {
                list ($userid, $id2, $id3) = split ('[-]', $_COOKIE['AuthUser']);
                //// query to get id num from username
                $query = "SELECT id FROM user WHERE username='$userid'";
                $result = mysql_query($query) or die ('Error in query: '.$query . mysql_error());
                // if row exists then the user has an account
                if (mysql_num_rows($result) == 1)
                {
                        // initiate a session
                        session_start();
                        // register the user's ID
                        session_register('uid');
                        list($id) = mysql_fetch_row($result);
                        $_SESSION['uid'] = $id;
                        // redirect to main page
                        header('Location:out.php');
                        mysql_free_result ($result);	
                        // close connection
                }
                // User passed auth, but does not have an account
                else 
                {
                        header('Location:error.php?ec=19');
                }
        }
}
elseif(!isset($_POST['login']) && $GLOBALS['CONFIG']['authen'] =='mysql')
{
    if(is_dir('install'))
    {
        $install_msg = '<span style="color: red;">Security Notice: If you already installed/updated then you should remove the "install" folder before proceeding</span>';
    }
    
    ?>
<!--

        index.php - displays a login form

        -->

        <html>
        <head>
        <TITLE><?php echo $GLOBALS['CONFIG']['title']; ?></TITLE>
        <basefont face="Verdana">
        </head>

        <body bgcolor="White">

        <table cellspacing="0" cellpadding="0">
        <tr>
        <td align="left"><img src="images/logo.gif" alt="Site Logo" border=0></td>
        </tr>
        </table>

        <table border="0" cellspacing="5" cellpadding="5">
        <tr>
        <td valign="top">
        <?php echo $install_msg; ?>
        <center>
        <table border="0" cellspacing="5" cellpadding="5">
        <form action="index.php" method="post">
        <?php
		if(isset($_REQUEST['redirection']))
			echo '<input type="hidden" name="redirection" value="' . $_REQUEST['redirection'] . '">' . "\n"; ?>
		<tr>
        <td>Username</td>
        <td><input type="Text" name="frmuser" size="15"></td>
        </tr>
        <tr>
        <td>Password</td>
        <td><input type="password" name="frmpass" size="15">
        <?php
        if($GLOBALS['CONFIG']['allow_password_reset'] == 'On')
        {
            echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/forgot_password.php">Forgot your password?</a>';
        }
?>
        </td>
        </tr>
        <tr>
        <td colspan="2" align="CENTER"><input type="Submit" name="login" value="Enter"></td>
        </tr>
        <?php
        if($GLOBALS['CONFIG']['allow_signup'] == 'On')
        {
?>
        <tr>
            <td colspan="2"><a href="<?php echo $GLOBALS['CONFIG']['base_url']; ?>/signup.php">Sign-up for an account</a>
        </tr>
<?php
}
?>

        </form>
        </table>
        </center>
        </td>
        <td valign="top">
        Welcome to OpenDocMan.
        <p>
        Log in to begin using the system's powerful storage, publishing and revision control features.
        </td>
        <td width="20%">
        &nbsp;
    </td>
        </tr>
        </table>

        </center>

<?php
        draw_footer();
}
else
{
        echo 'Check your config';
}
?>
