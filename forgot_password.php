<?php
/*
   forgot_password.php - utility to reset a user password
   Copyright (C) 2005-2006 Glowball Solutions
   Copyright (C) 2005-2012 Stephen Lawrence Jr.
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

include_once('odm-load.php');

if(isset($GLOBALS['CONFIG']['allow_password_reset']) && $GLOBALS['CONFIG']['allow_password_reset'] != 'True')
{
    echo msg('message_sorry_not_allowed');
    exit;
}

if (!isset($_REQUEST['last_message']))
{
    $_REQUEST['last_message']='';
}

if (isset($_POST['password']) && strlen($_POST['password']) && isset($_POST['username']) && strlen($_POST['username']) && isset($_POST['code']) && ( strlen($_POST['code']) == 32) && isset($_POST['user_id']) && $_POST['user_id']+0>0) 
{
    // reset their password and code
    $newPass = trim($_POST['password']);
    $oldCode = str_replace(' ', '', $_POST['code']);
    $username = str_replace(' ', '', $_POST['username']);
    $user_id = (int) $_POST['user_id']+0;

    // reset the password
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET password = md5('" . $newPass . "'), pw_reset_code = NULL WHERE id = " . $user_id . " AND username = '" . $username . "' AND pw_reset_code = '" . $oldCode . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    $redirect = 'index.php?last_message=' . urlencode(msg('message_your_password_has_been_changed'));
    header("Location: $redirect");
    exit;
}
else if (isset($_GET['username']) && strlen($_GET['username']) && isset($_GET['code']) && ( strlen($_GET['code']) == 32 ) ) 
{
    // they have clicked on the link we sent them
    $username = trim($_GET['username']);
    $code = trim($_GET['code']);

    // make sure we have a match
    $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '" . $username . "' AND pw_reset_code = '" . $code . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    if (!mysql_num_rows($result)) 
    {
        $redirect = 'forgot_password.php?last_message=' . urlencode(msg('message_the_code_you_are_using'));
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
            draw_error($_REQUEST['last_message']);
        ?>

            <p><?php echo msg('message_set_your_new_password')?></p>

            <form action="forgot_password.php" method="post">
            <input type="hidden" name="action" value="forgot">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="username" value="<?php echo $username; ?>">
            <input type="hidden" name="code" value="<?php echo $code; ?>">
            <table>
            <tr>
            <th><?php echo msg('label_new_password')?>:</th>
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
else if (isset($_POST['username']) && strlen($_POST['username']) > 0) 
{	
    // they have sent an username
    $username = trim($_POST['username']);

    // find them in the database
    $query = "SELECT id, Email FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '" . $username . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

    // send them back if we didn't find the username
    if (mysql_num_rows($result) == 0) 
    {
        $redirect = 'forgot_password.php?last_message=' . urlencode(msg('message_the_username_you_entered'));
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
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET pw_reset_code = '" . $reset_code . "' WHERE id = " . $user_id;
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());

        // generate the link
        $resetLink = $GLOBALS['CONFIG']['base_url'] . '/forgot_password.php?username=' . $username . '&code=' . $reset_code;
        $mail_headers  = "From: " . $GLOBALS['CONFIG']['site_mail'] . "\r\n";
        $mail_headers .= "Content-Type: text/plain; charset=UTF-8" . "\r\n";
        $mail_body  = msg('email_someone_has_requested_password')."\n\n";
        $mail_body .= $resetLink . "\n\n";
        $mail_body .= msg('email_thank_you') . "\n\n";
        $mail_body .= msg('area_admin') . "\n\n";
        
        // send the email
        mail($email, msg('area_reset_password'), $mail_body, $mail_headers);

        $redirect = 'forgot_password.php?last_message=' . urlencode(msg('message_an_email_has_been_sent'));
        header("Location: $redirect");
        exit;
    }
}

// default form
else 
{
    if (strlen($_REQUEST['last_message']))
        draw_error($_REQUEST['last_message']);
    ?>

        <p><?php echo msg('message_this_site_has_high_security')?></p>


        <form action="forgot_password.php" method="post">
        <table border="0">
        <tr>
        <th><?php echo msg('username')?>    :</th>
        <td><input type="text" name="username" size="25" maxlength="25"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" value="Reset Password"></td>
        </tr>
        </table>
        </form>

        <?php
}