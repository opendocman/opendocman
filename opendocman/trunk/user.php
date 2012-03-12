<?php
/*
user.php - user administration
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
 
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
// user.php - Administer Users
// check for valid session
// if changes are to be made on other account, then $item will contain
// the other account's id number.

session_start();

include('odm-load.php');

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
    exit;
}

// includes
$secureurl = new phpsecureurl;
///////////////////////////////////////////////////////////////////////////
// Any person who is accessing this page, if they access their own account, then it's ok.
// If they are not accessing their own account, then they have to be an admin.

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

// Make sure the item and uid are set, then check to make sure they are the same and they have admin privs, otherwise, user is not able to modify another users' info
if (isset($_SESSION['uid']) & isset($_GET['item']))
{
    if($_SESSION['uid'] != $_GET['item'] && $user_obj->isAdmin() != true )
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }
}
if(!isset($_REQUEST['caller']))
{	
    $_REQUEST['caller'] = 'admin.php';
}
//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if($user_obj->isAdmin() == true)
{
    $mode = 'enabled';
}
else
{
    $mode = 'disabled';
}
if($mode == 'disabled' && isset($_GET['item']) && $_GET['item'] != $_SESSION['uid'])
{
    header('Location:' . $secureurl->encode('error.php?ec=4'));
    exit;
}


if(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'adduser')
{
    draw_header(msg('area_add_new_user'), $last_message);
    // Check to see if user is admin
    ?>
                <script type="text/javascript" src="FormCheck.js"></script>

                <form name="add_user" action="user.php" method="POST" enctype="multipart/form-data">    
                <table border="0" cellspacing="5" cellpadding="5">
                
                    <?php
                    // Call the plugin API call for this section
                    callPluginMethod('onBeforeAddUser');                     
                    ?>

                <tr><td><b><?php echo msg('label_last_name')?></b></td><td><input name="last_name" type="text"></td></tr>
                <tr><td><b><?php echo msg('label_first_name')?></b></td><td><input name="first_name" type="text"></td></tr>
                <tr><td><b><?php echo msg('username')?></b></td><td><input name="username" type="text"></td></tr>
                <tr>
                <td><b><?php echo msg('label_phone_number')?></b></td>
                <td>
                <input name="phonenumber" type="text">
                </td>
                </tr>
                <tr>
                <td><b><?php echo msg('label_example')?></b></td>
                <td><b>999 9999999</b></td>
                </tr>
                <tr>
                <td><b><?php echo msg('label_email_address')?></b></td>
                <td>
                <input name="Email" type="text">
                </td>
                </tr>
                <tr>
                <?php
                // If mysqlauthentication, then ask for password
                if( $GLOBALS['CONFIG']['authen'] =='mysql')
                {
                        $rand_password = makeRandomPassword(); 
                        echo '<INPUT type="hidden" name="password" value="' . $rand_password . '">';
                }
        ?>

                <tr>
                <td><b><?php echo msg('label_department')?></b></td>
                <td>
                <select name="department">
                <?php			
                // query to get a list of departments
                $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        while(list($id, $name) = mysql_fetch_row($result))
        {
                echo '<option value=' . $id . '>' . $name . '</option>';
        }

        mysql_free_result ($result);
        ?>
                </select>
                </td>
                <tr>
                <td><b><?php echo msg('label_is_admin')?>?</b></td>
                <td>
                <input name="admin" type="checkbox" value="1">
                </td>
                </tr>
                <TR>
        <TD><B><?php echo msg('label_is_reviewer')?>?</B></TD>
                <TD><INPUT type="checkbox" name="reviewer" value="1"></TD>
                </TR>
                <TR>
                <TD><b><?php echo msg('label_reviewer_for')?></b></TD>
                <TD>
                <SELECT name="department_review[]" multiple />
                <?php 
        $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
        echo '<OPTION SELECTED>' . msg('label_select_departments') . '</OPTION>';
        while(list($dept_id, $dept_name) = mysql_fetch_row($result))
        {
                echo '<OPTION value="' . $dept_id . '">' . $dept_name . '</OPTION>' . "\n";
        }
?>
</SELECT>
</TD>
</TR>
<tr>
<td></td>
<td columnspan=3 align="center"><input type="Submit" name="submit" onClick="return validatemod(add_user);" value="Add User">
<input type="Submit" name="submit" value="Cancel">

</td>
</tr>
</table>
</form>
<?php
draw_footer();
}
elseif(isset($_POST['submit']) && 'Add User' == $_POST['submit'])
{
    if (!$user_obj->isAdmin())
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }
    // Check to make sure user does not already exist
    $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '" . addslashes($_POST['username']) . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    // If the above statement returns more than 0 rows, the user exists, so display error
    if(mysql_num_rows($result) > 0)
    {
        header('Location:' . $secureurl->encode('error.php?ec=3'));
        exit;
    }
    else
    {
        $phonenumber = @$_POST['phonenumber'];
        // INSERT into user
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user (username, password, department, phone, Email,last_name, first_name) VALUES('". addslashes($_POST['username'])."', md5('". addslashes(@$_POST['password']) ."'), '" . addslashes($_POST['department'])."' ,'" . addslashes($phonenumber) . "','". addslashes($_POST['Email'])."', '" . addslashes($_POST['last_name']) . "', '" . addslashes($_POST['first_name']) . '\' )';
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // INSERT into admin
        $userid = mysql_insert_id($GLOBALS['connection']);
        if (!isset($_POST['admin']))
        {
            $_POST['admin']='0';
        }
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES('$userid', '$_POST[admin]')";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        if(isset($_POST['reviewer']))
        {
            for($i = 0; $i<sizeof($_POST['department_review']); $i++)
            {
                $dept_rev=$_POST['department_review'][$i];
                $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id, user_id) values('$dept_rev', '$userid')";
                $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
            }
        }

        // mail user telling him/her that his/her account has been created.
        $user_obj = new user($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
        $new_user_obj = new User($userid, $GLOBALS['connection'], DB_NAME);
        $date = date('Y-m-d H:i:s T'); //locale insensitive
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0].' '.$get_full_name[1];
        $get_full_name = $new_user_obj->getFullName();
        $new_user_full_name = $get_full_name[0].' '.$get_full_name[1];
        $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
        $mail_headers = "From: $mail_from"."\r\n";
        $mail_headers .="Content-Type: text/plain; charset=UTF-8"."\r\n";
        $mail_subject=msg('message_account_created_add_user');
        $mail_greeting=$new_user_full_name.":\n\r\t".msg('email_i_would_like_to_inform');
        $mail_body = msg('email_your_account_created').' '.$date.'.  ' . msg('email_you_can_now_login') . ':'."\n\r";
        $mail_body.= $GLOBALS['CONFIG']['base_url']."\n\n";
        $mail_body.= msg('username') . ': '.$new_user_obj->getName()."\n\n";
        if($GLOBALS['CONFIG']['authen'] == 'mysql')
        {
            $mail_body.=msg('password') . ': '.$_POST['password']."\n\n";
        }
        $mail_salute="\n\r" . msg('email_salute') . ",\n\r$full_name";
        $mail_to = $new_user_obj->getEmailAddress();
        mail($mail_to, $mail_subject, ($mail_greeting.' '.$mail_body.$mail_salute), $mail_headers);
        $_POST['last_message'] = urlencode(msg('message_user_successfully_added'));

        // Call the plugin API call for this section
        callPluginMethod('onAfterAddUser');

        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_POST['last_message']));
    }
}
// DELETE USER
elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Delete')
{
    // If demo mode, don't allow them to update the demo account
    if (@$GLOBALS['CONFIG']['demo'] == 'true')
    {
        draw_header('Delete User ' ,$last_message);
        echo 'Sorry, demo mode only, you can\'t do that';
        draw_footer();
        exit;
    }
    $delete='';
    $user_obj = new User($_POST['item'], $GLOBALS['connection'], DB_NAME);
    draw_header('Delete ' . $user_obj->getName(), $last_message);
    ?>
                        
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="commitchange.php" method="POST" enctype="multipart/form-data">
                        <tr>
                        <td valign="top">Are you sure you want to delete 
						<input type="hidden" name="id" value="<?php echo $_REQUEST['item']; ?>">
                        <?php
                        $query = "SELECT id, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id='{$_POST['item']}'";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                while(list($id, $first_name, $last_name) = mysql_fetch_row($result))
                {
                        echo $first_name.' '.$last_name;
                }

                mysql_free_result ($result);
                ?> 
                        ?
                        </td>
                        <td colspan="4" align="center">
                        <input type="Submit" name="submit" value="Delete User">
                        </td>
                        </form>
                        <form action="user.php" method="POST" enctype="multipart/form-data">
                        <td colspan="4" align="center">
                        <input type="Submit" name="submit" value="Cancel">
                        </td>
                        </form>
                        </tr>
                        </form>
                        </table>
                        
                        <?php
                        draw_footer();
        }
        // CHOOSE THE USER TO DELETE
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'deletepick')
        {
                $deletepick='';
                draw_header('Choose User to Delete', $last_message);
                ?>
                        
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="user.php" method="POST" enctype="multipart/form-data">
                        <INPUT type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
                        <tr>
                        <td><b>User</b></td>
                        <td colspan=3>
                        <select name="item">
                        <?php
                        $query = "SELECT id,username, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                while(list($id, $username,$last_name, $first_name) = mysql_fetch_row($result))
                {
                        echo '<option value=' . $id . '>' . $last_name . ', ' . $first_name . ' - ' . $username . '</option>';
                }

                mysql_free_result ($result);
                $deletepick="";
                ?>
                        </select>
                        </td>
                        <tr>
                        <td colspan="4" align="center">
                        <input type="Submit" name="submit" value="Delete">
                        </form>
                        </td>
                        <td>
                        <form action="user.php">
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>

                        <?php
                        draw_footer();
        }
        // SHOW THE USER INFO
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Show User')
        {
                // query to show item
                $user_obj = new User($_POST['item'], $GLOBALS['connection'], DB_NAME);
                draw_header('Show User: ' . $user_obj->getName(), $last_message);
                ?>
                        <table border=0>
                        <th>User Information</th>
                        <?php
                        
                $full_name = $user_obj->getFullName();
                echo '<tr><td>ID#:</td><td>' . $_POST['item'] . '</td></tr>';
                echo "<TR><TD>First Name</TD><TD>".$full_name[0]."</TD></TR>";
                echo "<TR><TD>Last Name</TD><TD>".$full_name[1]."</TD></TR>";
                echo "<tr><td>username:</td><td>".$user_obj->getName()."</td></tr>";
                echo "<tr><td>Department:</td><td>".$user_obj->getDeptName()."</td></tr>";
                echo "<TR><TD>Email Address</TD><TD>".$user_obj->getEmailAddress()."</TD></TR>";
                echo "<TR><TD>Phone Number</TD><TD>".$user_obj->getPhoneNumber()."</TD></TR>";
                echo "<tr><td>Admin?</td>";
                if ($user_obj->isAdmin())
                        $isadmin="yes";
                else
                        $isadmin="no";
                echo "<td>$isadmin</td>";
                echo "</tr>";
                $isreviewer = 'no';
                if($user_obj->isReviewer() == 1)
                        $isreviewer	= 'yes';
                echo("<TR><TD>Reviewer:</TD><TD>$isreviewer</TD></TR>");
                ?>
                        <form action="admin.php" method="POST" enctype="multipart/form-data">
                        <tr>
                        <td colspan="4" align="center">
                        <input type="Submit" name="" value="Back">
                        </td>
                        </tr>
                        </form>
                        </table>
                        <?php
                        draw_footer();
        }
        // CHOOSE USER TO DISPLAY INFO FOR
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'showpick')
        {
                draw_header('Choose User to View', $last_message);

                $showpick='';
                ?>
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="user.php" method="POST" enctype="multipart/form-data">
                        <INPUT type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>" />
                        <tr>
                        <td><b>User</b></td>
                        <td colspan=3>
                        <select name="item">
                        <?php
                        $query = "SELECT id, username, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
                $result = mysql_query($query) or die ("Error in query: $query. " . mysql_error());
                while(list($id, $username, $first_name, $last_name) = mysql_fetch_row($result))
                {
                        echo '<option value="' . $id . '">' . $last_name . ',' . $first_name . ' - ' . $username . '</option>';
                }
                mysql_free_result ($result);
                ?>
                        </select>
                        </td>
                        <tr>
                        <td colspan="4" align="center">
                        <input type="Submit" name="submit" value="Show User">
                        </form>
                        <td><form name='cancel' action='admin.php'>
                        <input type="Submit" name="submit" value="Cancel">
                        </form><td>
                        </td>
                        </tr>
                        </table>
                        <?php
                        draw_footer();
        }
        // MODIFY USER
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Modify User')
        {
                // If demo mode, don't allow them to update the demo account
                if (@$GLOBALS['CONFIG']['demo'] == 'true')
                {
                        draw_header('Update User ' ,$last_message);
                        echo 'Sorry, demo mode only, you can\'t do that';
                        draw_footer();
                        exit;
                }
                else
                {
                    // Begin Not Demo Mode
                    $user_obj = new User($_REQUEST['item'], $GLOBALS['connection'], DB_NAME);
                    draw_header('Update User: ' . $user_obj->getName() ,$last_message);	
                    ?>
                        <script type="text/javascript" src="FormCheck.js">
                        function redirect(url_location)
                        {       window.location=url_location    }

                    </script>

                        <table border="0" cellspacing="5" cellpadding="5">
                        <tr>
                        <form name="update" action="commitchange.php" method="POST" enctype="multipart/form-data">
                        <INPUT type="hidden" name="caller" value="<?php echo $_REQUEST['caller']; ?>">
                        <?php
                $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}user where id='" . $_REQUEST['item'] . "' ORDER BY username";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                list($id,$username, $password, $department, $phonenumber, $Email, $last_name, $first_name) = mysql_fetch_row($result);
                echo '<tr>';
                echo '<td><B>User ID: </td><td colspan=4>'.$id.'</td>';
                echo '<input type=hidden name=id value="'.$id.'">';
                echo '</tr>';
                echo '<tr>';
                echo '<td><b>Last Name: </td><td colspan=4><INPUT NAME="last_name" TYPE="text" VALUE="'.$last_name.'"></td></TR>';
                echo '<td><b>First Name</td><td colspan=4><INPUT NAME="first_name" TYPE="text" VALUE="'.$first_name.'"></td></TR>';
                echo '<td><b>User name: </td><td colspan=4><INPUT NAME="username" TYPE="text" VALUE="'.$username.'"></td></TR>';

                echo "<tr>";
                echo ("<td><b>Phone Number: </td><td colspan=4><input name=\"phonenumber\" type=\"text\" value=\"$phonenumber\"></td>");
                // If mysqlauthentication, then ask for password
                if( $GLOBALS["CONFIG"]["authen"] =='mysql')
                {
?>
                    <tr>
                    <td><b>Password</b></td>
                    <td>
                    <input name="password" type="password">
                    <font size="1">Leave empty if unchange</font>
                    </td>
                    </tr>
                    <tr>
                    <td><b>Confirm Password</b></td>
                    <td>
                    <input name="conf_password" type="password">
                    </td>
                    </tr>
                    </tr>
<?php                     
                }//endif
?>
                <tr>
                <td><b>E-mail Address: </td>
                <td colspan=4>
                <input name="Email" type="text" value="<?php echo $Email; ?>"></td>
                </tr>
          		<tr>
   		
<?php
				mysql_free_result ($result);
?>
                </tr>
                <tr>
                <td><b>Department</b></td>
                <td colspan=3>

                <select name="department" <?php echo $mode; ?>>
<?php
                // query to get a list of departments
                $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                $userdepartment = $user_obj->getDeptID();
                while(list($id, $name) = mysql_fetch_row($result))
                {
                        if ($id==$userdepartment)
                        {
                                echo '<option selected value="' . $id . '">' . $name . '</option>';
                        }
                        else
                        {
                                echo '<option value="' . $id . '">' . $name . '</option>';
                        }
                }

                mysql_free_result ($result);
?>
                </select>
                </td>
                </tr>
                <tr>
                <td><b>Admin?</b></td>
                <td colspan=1>
<?php
                // query to get a list of departments
                $user_obj = new User($_REQUEST['item'], $GLOBALS['connection'], DB_NAME);
                //if ($adminvalue=='1')
                if($user_obj->isAdmin())
                {
                        echo '<input name="admin" type="checkbox" value="1" checked '.$mode.'></input>'."\n";
                }
                else
                {
                        echo '<input name="admin" type="checkbox" value="1"  '.$mode.'></input>'."\n";
                }
                if($user_obj->isReviewer())
                {
                	$checked = 'checked';
                }
                else
                {
                	$checked = '';
                }
?>
                </TR>
                <TR>
                <TD><B>Reviewer</B></TD>
<?php
                echo '<TD><INPUT type="checkbox" value="1" '.$checked.' name="reviewer" '.$mode.'></TD></TR>'."\n";
?>
                </td>
                </tr>
                <TR><TD></TD>
                <TD>
                <SELECT name='department_review[]' multiple <?php echo $mode; ?>>
                <OPTION value='-1'>Choose the department(s)</OPTION>
<?php
                $query = "SELECT dept_id, user_id FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = '{$_REQUEST['item']}'";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                $result2 = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                $hits = mysql_num_rows($result);
                //for dept that this user is reviewing for
                for($i = 0; $i< $hits; $i++)
               	{
               		list($department_reviewer[$i][0], $department_reviewer[$i][1]) = mysql_fetch_row($result);
               	}
                // for all depts
               	$hits = mysql_num_rows($result2);
                for($i=0; $i<$hits; $i++)
                {
                	list( $all_department[$i][0], $all_department[$i][1]) = mysql_fetch_row($result2);
                }
                mysql_free_result($result);
                mysql_free_result($result2);
                for($d= 0; $d<sizeof($all_department); $d++)
                {
                    $found = false;
                    if(isset($department_reviewer))
                    {
                        for($r = 0; $r<sizeof($department_reviewer); $r++)
                        {
                            if($all_department[$d][0] == $department_reviewer[$r][0])
                            {
                                    echo("<option value=\"" . $all_department[$d][0] ."\" selected> " . $all_department[$d][1] ."</option>\n");
                                    $found = true;
                                    $r = sizeof($department_reviewer);
                            }
                        }
                    }
                    if( !$found )
                   	{
                   		echo("<option VALUE=\"" .$all_department[$d][0] ."\">" .$all_department[$d][1] ."</option>\n");
                   	}
                }

                ?>
                        </SELECT>
                        </TD></TR>
                        <td colspan="1" align="right">
                        </td>
                        <td>
                        <INPUT type="hidden" name="set_password" value="0">
                        <input type="Submit" name="submit"  onClick="return verify(this.form, password, conf_password, set_password);" value="Update User">
                        </form>
                        <form action="user.php" >
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>
                        <script type="text/javascript">
                        	function verify(this_form, pwd, conf_pwd, set_password)
                        	{
                        		if(pwd.value != "" && pwd.value != conf_pwd.value)
                        		{
                        			alert("Password and Confirm Password fields do not match, Please check it again."); 
									return false;
                        		}
                       			if(pwd.value != "")
                       			{
                       				set_password.value = true;
                       			}
                       			else
                       			{
                       				set_password.value = false;
                       			}
                        		return validateEmail(this_form);
                        	
                        	}
                        	</script>
                        	<?php
                } // End Not Demo mode
                          draw_footer();
        }
        // CHOOSE USER TO UPDATE
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'updatepick')
        {
                draw_header('Modify User',$last_message);

                // Check to see if user is admin
                $query = "SELECT admin FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = '{$_SESSION['uid']}' and admin = '1'";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                if(mysql_num_rows($result) <= 0)
                {
                        header('Location:' . $secureurl->encode('error.php?ec=4'));
                        exit;
                }
                ?>
                        <form action="user.php" method="POST" enctype="multipart/form-data">
                        <INPUT type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>" />
                        <table border="0" cellspacing="5" cellpadding="5">
                        <tr>
                        <td><b>Username to modify:</b></td>
                        <td colspan=3><select name="item">
                        <?php

                        // query to get a list of users
                        $query = "SELECT id, username, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
                $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());


                while(list($id, $username, $first_name, $last_name) = mysql_fetch_row($result))
                {
                        echo '<option value="' . $id . '">' . $last_name . ', ' . $first_name . ' - ' . $username . '</option>';
                }

                mysql_free_result ($result);                
                ?>
                        </td>
                        <td  align="right">
                        <input type="Submit" name="submit" value="Modify User">
                        </td>

                        <td align="center">
                        <form action="user.php">
                        <input type="Submit" name="submit" value="Cancel">
                        </td>
                        </tr>
                        </table>
                        </form>
                        <?php
                        draw_footer();
        }
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'change_password_pick')
        {
                draw_header('Change password', $last_message);
                $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
                $submit_message = 'Changing password';

?>
                        <br>
                                <script type="text/javascript">
                                function Validate(dataform)
                                {
                                if(dataform.new_password.value != dataform.confirm_password.value)
                                {	
                                alert("The two password fields do not match.  Please recheck.")
                                return false
                                }
                                else
                                {	return true	}
                                }
                                function redirect(url_location)
                                {	window.location=url_location	}

                                </script>
                                <form action="commitchange.php" method="post" enctype="multipart/form-data\">
                                <table name="header" align="center" border="1">
                                <tr><td align="center" bgcolor="teal"><b>User Information</b></td></tr>
                                </table>
                                <table name="list" align="center" border="1">
                                <tr><td align="left">ID</td><td align="left"><?php echo $user_obj->getDeptId(); ?></td></tr>
                        		<tr><td align="left">Username</td><td align="left"><?php echo $user_obj->getName(); ?></td></tr>
                        		<tr><td align="left">Department</td><td align="left"><?php echo $user_obj->getDeptName(); ?></td></tr>
                        </table>
                        <br>
                        </form>
<?php
        }
        elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'change_personal_info_pick')
        {
                draw_header('Change password', $last_message);
                $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
                $cancel_message = 'Password alteration had been canceled';
                $submit_message = 'Changing password';
                // If demo mode, don't allow them to update the demo account
                if (@$GLOBALS['CONFIG']['demo'] == 'true')
                {
                        echo 'Sorry, demo mode only, you can\'t do that';
                        draw_footer();
                        exit;
                }
?>
                <br>
                                <script type="text/javascript">
                                function redirect(url_location)
                                {	window.location=url_location	}

                                </script>
                                <form action="commitchange.php" method="post" enctype="multipart/form-data">
                                <table name="header" align="center" border="1">
                                <tr><td align="center" bgcolor="teal"><b>User Information</b></td></tr>
                                </table>
                                <table name="list" align="center" border="1">
                                <tr><td align="left">ID</td><td align="left"><?php echo $user_obj->getDeptId(); ?></td></tr>
                                <tr><td align="left">Username</td><td align="left"><input type="text" name="username" value="<?php echo $user_obj->getName(); ?>"></td></tr>
                                <tr><td align="left">Department</td><td align="left"><?php echo $user_obj->getDeptName(); ?></td></tr>
                                </table>
                                <br>
                                <input type="hidden" name="submit" value="change_personal_info">
                                <input type="Submit" name="change_personal_info" value="Submit">
                                <input type="Button" name="submit" value="Cancel" onclick="redirect('profile.php?last_message=Personal Info alteration canceled')">
                                </form>
<?php
        }
        elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Cancel')
        {
                $last_message="Action Cancelled";
                header('Location:' . $secureurl->encode('admin.php?last_message='.$last_message));
        }
        else 
        {	
        	header('Location:' . $secureurl->encode('admin.php?last_message=' . urlencode('Unrecognizalbe action')));
        }