<?php
/*
   forgot_password.php - utility to reset a user password
   Copyright (C) 2005-2006 Glowball Solutions & Stephen Lawrence
   This page was added to the core files for this utility.

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

include_once('config.php');

if(isset($GLOBALS['CONFIG']['allow_password_reset']) && $GLOBALS['CONFIG']['allow_password_reset'] != 'On')
{
    echo 'Sorry, your are not allowed to do that';
    exit;
}

if (!isset($_REQUEST['last_message']))
{
    $_REQUEST['last_message']='';
}

if (isset($_POST['password']) && strlen($_POST['password']) && isset($_POST['username']) && strlen($_POST['username']) && isset($_POST['code']) && strlen($_POST['code']) && isset($_POST['user_id']) && $_POST['user_id']+0>0) 
{
    // reset their password and code
    $newPass = trim($_POST['password']);
    $oldCode = $_POST['code'];
    $username = trim($_POST['username']);
    $user_id = $_POST['user_id']+0;

    // reset the password
    $query = "UPDATE user SET password = PASSWORD('" . trim($_POST['password']) . "'), pw_reset_code = NULL WHERE id = " . $user_id . " AND username = '" . $username . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    $redirect = 'index.php?last_message=' . urlencode('Your password has been changed.  Please log in to view documents.');
    header("Location: $redirect");
    exit;
}
else if (isset($_GET['username']) && strlen($_GET['username']) && isset($_GET['code']) && strlen($_GET['code'])) 
{
    // they have clicked on the link we sent them
    $username = trim($_GET['username']);
    $code = trim($_GET['code']);

    // make sure we have a match
    $query = "SELECT id FROM user WHERE username = '" . $username . "' AND pw_reset_code = '" . $code . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    if (!mysql_num_rows($result)) 
    {
        $redirect = 'forgot_password.php?last_message=' . urlencode('The code you are trying to use to reset your password is no longer valid.  Please use this form to reset your password.');
        header("Location: $redirect");
        exit;
    }
    else 
    {
        $userInfo = mysql_fetch_array($result);
        $user_id = $userInfo['id'];
        // build the header and navigation
        /*





           ADD FORMATTING HERE




         */
        if (strlen($_REQUEST['last_message']))
            echo "<p class=\"hilitename\">" . $_REQUEST['last_message'] . ".</p>\n";
        ?>

            <p>Set your new password using the form below.</p>

            <form action="forgot_password.php" method="post">
            <input type="hidden" name="action" value="forgot">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="username" value="<?php echo $username; ?>">
            <input type="hidden" name="code" value="<?php echo $code; ?>">
            <table>
            <tr>
            <th>New Password:</th>
            <td><input type="password" name="password" size="12" maxlength="50"></td>
            </tr>
            <tr>
            <td>&nbsp;</td>
            <td><input type="submit" value="Reset Password"></td>
            </tr>
            </table>
            </form>

            <?php
            // build the footer
            /*





               ADD FORMATTING HERE




             */
    }
}
else if (isset($_POST['username']) && strlen($_POST['username'])) 
{	
    // they have sent an username
    $username = trim($_POST['username']);

    // find them in the database
    $query = "SELECT id, Email FROM user WHERE username = '" . $username . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    // send them back if we didn't find the username
    if (mysql_num_rows($result) == 0) 
    {
        $redirect = 'forgot_password.php?last_message=' . urlencode('The username you entered was not found in our system.  Contact us if you have forgotten your username.');
        header("Location: $redirect");
        exit;
    }
    else 
    {
        $user_info = mysql_fetch_array($result);
        $user_id = $user_info['id'];
        $email = $user_info['Email'];

        // create a reset code
        $salt = "abcdefghjkmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        srand((double)microtime()*1005008); 
        $i = 0;
        while ($i <= 7) 
        {
            $num = rand() % 63;
            $tmp = substr($salt, $num, 1);
            $randstring = $randstring . $tmp;
            $i++;
        }
        $reset_code = md5($randstring);

        // add the reset code to the database for this user
        $query = "UPDATE user SET pw_reset_code = '" . $reset_code . "' WHERE id = " . $user_id;
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

        // generate the link
        $resetLink = $GLOBALS['CONFIG']['base_url'] . '/forgot_password.php?username=' . $username . '&code=' . $reset_code;

        // send the email
        mail($email, "Reset Password", "Someone has requested a password reset.  If you wish to reset your password please follow the link below.  If you do not wish to reset your password then simply do nothing and disregard this email.

                $resetLink

                Thank you,
                Administration
                ", "From: " . $GLOBALS['CONFIG']['site_mail']);

        $redirect = 'forgot_password.php?last_message=' . urlencode('An email has been sent to the email address on file with a link that must be followed in order to reset the password.');
        header("Location: $redirect");
        exit;
    }
}

// default form
else 
{
    // build the header and navigation
    /*





       ADD FORMATTING HERE




     */
    if (strlen($_REQUEST['last_message']))
        echo "<p>" . $_REQUEST['last_message'] . ".</p>\n";
    ?>

        <p>This site has a high level of security and we cannot retrieve your password for you.  You can use this form to reset your password.  Enter your username and we will send an email to the email address on file with a link that you must follow to reset your password.  At that point you may set it to anything you wish.</p>

        <p>Please contact us if you have forgotten your username.</p>

        <form action="forgot_password.php" method="post">
        <table border="0">
        <tr>
        <th>Username:</th>
        <td><input type="text" name="username" size="25" maxlength="25"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" value="Reset Password"></td>
        </tr>
        </table>
        </form>

        <?php
        /*





           ADD FORMATTING HERE




         */
}
?>
