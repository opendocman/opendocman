<?php
// user.php - Administer Users
// check for valid session
// if changes are to be made on other account, then $item will contain
// the other account's id number. 
session_start();
if (!session_is_registered('SESSION_UID'))
{
        header('Location:error.php?ec=1');
        exit;
}

// includes
include('config.php');
///////////////////////////////////////////////////////////////////////////
// Any person who is accessing this page, if they access their own account, then it's ok.
// If they are not accessing their own account, then they have to be an admin.
$connection = mysql_connect($hostname, $user, $pass) or die ("Unable to connect!");
$user_obj = new User($SESSION_UID, $connection, $database);
if($SESSION_UID != $item && $user_obj->isAdmin() != true )
{
        header('Location:error.php?ec=4');
        exit;
}
//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if($user_obj->isAdmin() == true)
        $mode = 'enabled';
        else 
        $mode = 'disabled';
        if($mode == 'disabled' && $item != $SESSION_UID)
{
        header('Location:error.php?ec=4');
        exit;
}
////////////////////////////////////////////////////////////////////////////
if($submit and $submit != 'Cancel')
{
        draw_header('Admin users');
        draw_menu($SESSION_UID);
}


// open a connection to the database

if($submit == 'adduser')
{
        draw_status_bar('Add New User', $message);
        // Check to see if user is admin
        ?>
                <SCRIPT LANGUAGE="JavaScript1.2" src="FormCheck.js"></script>			   

                <center>
                <table border="0" cellspacing="5" cellpadding="5">
                <form name="add_user" action="commitchange.php" method="POST" enctype="multipart/form-data">
                <tr><td><b>Last Name</b></td><td><input name="last_name" type="text"></td></tr>
                <tr><td><b>First Name</b></td><td><input name="first_name" type="text"></td></tr>
                <tr><td><b>Username</b></td><td><input name="username" type="text"></td></tr>
                <tr>
                <td><b>Phone Number</b></td>
                <td>
                <input name="phonenumber" type="text">
                </td>
                </tr>
                <tr>
                <td><b>Example</b></td>
                <td><b>999 9999999</b></td>
                </tr>
                <tr>
                <td><b>E-mail Address</b></td>
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
                <td><b>Department</b></td>
                <td>
                <select name="department">
                <?php			
                // query to get a list of departments
                $query = "SELECT id, name FROM department ORDER BY name";
        $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

        while(list($id, $name) = mysql_fetch_row($result))
        {
                echo '<option value=' . $id . '>' . $name . '</option>';
        }

        mysql_free_result ($result);
        ?>
                </select>
                </td>
                <tr>
                <td><b>Admin?</b></td>
                <td>
                <input name="admin" type="checkbox" value="1">
                </td>
                </tr>
                <TR>
                <TD><B>Reviewer?</B></TD>
                <TD><INPUT type='checkbox' name='reviewer' value='1'></TD>
                </TR>
                <TR>
                <TD></TD>
                <TD>
                <SELECT name='department_review[]' multiple>
                <?php 
                $query = "SELECT department.id, department.name FROM department ORDER BY name";
        $result = mysql_db_query($database, $query, $connection) or die("Error in query: $query". mysql_error());
        echo '<OPTION SELECTED>Select the department(s)</OPTION>';
        while(list($dept_id, $dept_name) = mysql_fetch_row($result))
        {
                echo '<OPTION value="' . $dept_id . '">' . $dept_name . '</OPTION>' . "\n";
                ?>
                        </SELECT>
                        </TD>
                        </TR>
                        <tr>
                        <td></td>
                        <td columnspan=3 align="center"><input type="Submit" name="adduser" onClick="return validate(add_user);" value="Add User">
                        </form>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>
                        </center>

                        <?php
                        draw_footer();
        }
}
        elseif($submit == 'Delete User')
        {
                $delete='';
                draw_status_bar('Delete User', $message);
                ?>
                        <center>
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="commitchange.php?id=<?php echo $item;?> " method="POST" enctype="multipart/form-data">
                        <tr>
                        <td valign="top">Are you sure you want to delete 

                        <?php
                        $query = 'SELECT id, first_name, last_name FROM user WHERE id=' . $item .'';
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
                while(list($id, $first_name, $last_name) = mysql_fetch_row($result))
                {
                        echo $first_name.' '.$last_name;
                }

                mysql_free_result ($result);
                ?> 

                        </td>
                        <td colspan="4" align="center">
                        <input type="Submit" name="deleteuser" value="Yes">
                        </td>
                        </form>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                        <td colspan="4" align="center">
                        <input type="Submit" name="submit" value="No, Cancel">
                        </td>
                        </form>
                        </tr>
                        </form>
                        </table>
                        </center>
                        <?php
                        draw_footer();
        }
        elseif($submit == 'deletepick')
        {
                $deletepick='';
                draw_status_bar('Choose User to Delete', $message);
                ?>
                        <center>
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="<?php $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                        <tr>
                        <td><b>User</b></td>
                        <td colspan=3>
                        <select name="item">
                        <?php
                        $query = "SELECT id,username, last_name, first_name FROM user ORDER BY last_name";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
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
                        <input type="Submit" name="submit" value="Delete User">
                        </form>
                        </td>
                        <td>
                        <form action="<?php echo $PHP_SELF; ?>">
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>
                        </center>

                        <?php
                        draw_footer();
        }
        elseif($submit == 'Show User')
        {
                // query to show item
                draw_status_bar("Display Item Information", $message);
                ?>
                        <center>
                        <table border=0>
                        <th>User Information</th>
                        <?php
                        $user_obj = new User($item, $connection, $database);
                $full_name = $user_obj->getFullName();
                echo "<tr><td>ID#:</td><td>$item</td></tr>";
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
                        </center>
                        <?php
                        draw_footer();
        }
        elseif($submit == 'showpick')
        {
                draw_status_bar('Choose User to View', $message);

                $showpick='';
                ?>
                        <center>
                        <table border="0" cellspacing="5" cellpadding="5">
                        <form action="<?php $PHP_SELF; ?>" method="POST" enctype="multipart/form-data">
                        <tr>
                        <td><b>User</b></td>
                        <td colspan=3>
                        <select name="item">
                        <?php
                        $query = 'SELECT id, username, first_name, last_name FROM user ORDER BY last_name';
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
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
                        </center>
                        <?php
                        draw_footer();
        }
        elseif($submit == 'Modify User')
        {
        		$user_obj = new User($SESSION_UID, $connection, $database);
                draw_status_bar("Update User",$message);
                ?>
                        <script LANGUAGE="JavaScript1.2" src="FormCheck.js">
                        function redirect(url_location)
                        {       window.location=url_location    }

                </SCRIPT>

                        <center>
                        <table border="0" cellspacing="5" cellpadding="5">
                        <tr>
                        <form name="update" action="commitchange.php" method="POST" enctype="multipart/form-data">
                        <?php
                        // query to get a list of users
                        echo '<INPUT type="hidden" name="callee" value="'.$callee.'">';
                $query = "SELECT * FROM user where id='$item' ORDER BY username";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
                ?>

                        <?
                        while(list($id,$username, $password, $department, $phonenumber, $Email, $last_name, $first_name) = mysql_fetch_row($result))
                        {
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
                                if( $GLOBALS["CONFIG"]["authen"] =='mysql' && $update_pwd=='true')
                                {
                                        ?>
                                                <tr>
                                                <td><b>Password</b></td>
                                                <td>
                                                <input name="password" type="password">
                                                </td>
                                                </tr>
                                                <tr>
                                                <td><b>Confirm Password</b></td>
                                                <td>
                                                <input name="conf_password" type="password">
                                                </td>
                                                </tr>
                                                </tr>
                                                <tr>

                                                </tr>
                                                <tr>
                                                <td><b>E-mail Address: </td>
                                                <td colspan=4>
                                                <input name="Email" type="text" value="<?php echo $Email; ?>"></td>
                                                </tr>
                                                <tr>
<?php
                        }
                mysql_free_result ($result);
                ?>
                        </tr>
                        <tr>
                        <td><b>Department</b></td>
                        <td colspan=3>

                        <select name="department" <?php echo $mode; ?>>

                        <?php
                        // query to get a list of departments
                        $query = "SELECT department.id, department.name FROM department ORDER BY department.name";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
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
                        $user_obj = new User($item, $connection, $database);
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
                        $checked = 'checked';
                else
                        $checked = '';
                ?>
                        </TR>
                        <TR>
                        <TD><B>Reviewer</B></TD>
                        <?php
                        echo '<TD><INPUT type="checkbox" '.$checked.' name="reviewer" '.$mode.'></TD></TR>'."\n";
                ?>
                        </td>
                        </tr>
                        <TR><TD></TD>
                        <TD>
                        <SELECT name='department_review[]' multiple <?php echo $mode; ?>>
                        <OPTION value='-1'>Choose the department(s)</OPTION>
                        <?php
                        $query = "SELECT dept_id, user_id FROM dept_reviewer where user_id = $item";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
                $query = "SELECT department.id, department.name FROM department ORDER BY name";
                $result2 = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
                $hits = mysql_num_rows($result);
                for($i = 0; $i< $hits; $i++)
                        list($department_reviewer[$i][0], $department_reviewer[$i][1]) = mysql_fetch_row($result);
                $hits = mysql_num_rows($result2);
                for($i=0; $i<$hits; $i++)
                        list( $department[$i][0], $department[$i][1]) = mysql_fetch_row($result2);
                mysql_free_result($result);
                mysql_free_result($result2);
                for($d= 0; $d<sizeof($department); $d++)
                {
                        $found = false;
                        for($r = 0; $r<sizeof($department_reviewer); $r++)
                        {
                                if($department[$d][0] == $department_reviewer[$r][0])
                                {
                                        echo("<option value=\"" . $department[$d][0] ."\" selected> " . $department[$d][1] ."</option>\n");
                                        $found = true;
                                        $r = sizeof($department_reviewer);
                                }
                        }
                        if( !$found )
                                echo("<option VALUE=\"" .$department[$d][0] ."\">" .$department[$d][1] ."</option>\n");
                }

                ?>
                        </SELECT>
                        </TD></TR>
                        <td colspan="1" align="right">
                        </td>
                        <td>
                        <input type="Submit" name="updateuser"  onClick="return validatemod(update);" value="Modify User">
                        </form>
                        <form action="<?php echo $PHP_SELF; ?>" >
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>
                        </center>
                        <?php
                        draw_footer();
        }
}
        elseif($submit == 'updatepick')
        {
                draw_status_bar('Modify User',$message);

                // Check to see if user is admin
                $query = "SELECT admin FROM admin WHERE id = '$SESSION_UID' and admin = '1'";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
                if(mysql_num_rows($result) <= 0)
                {
                        header('Location:error.php?ec=4');
                        exit;
                }
                ?>
                        <center>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?update_pwd=true" method="POST" enctype="multipart/form-data">
                        <table border="0" cellspacing="5" cellpadding="5">
                        <tr>
                        <td><b>Username to modify:</b></td>
                        <td colspan=3><select name="item">
                        <?php

                        // query to get a list of users
                        $query = "SELECT id, username, first_name, last_name FROM user ORDER BY last_name";
                $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());


                while(list($id, $username, $first_name, $last_name) = mysql_fetch_row($result))
                {
                        echo '<option value="' . $id . '">' . $last_name . ', ' . $first_name . ' - ' . $username . '</option>';
                }

                mysql_free_result ($result);
                echo "the username right now is: $first_name $last_name";
                ?>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="4" align="right">
                        <input type="Submit" name="submit" value="Modify User">
                        </td>
                        </form>
                        <td colspan="4" align="center">
                        <form action="<?php echo $PHP_SELF; ?>">
                        <input type="Submit" name="submit" value="Cancel">
                        </form>
                        </td>
                        </tr>
                        </table>
                        </center>

                        <?php
                        draw_footer();
        }
        elseif($submit == 'change_password_pick')
        {
                draw_status_bar('Change password', $last_message);
                $user_obj = new User($SESSION_UID, $connection, $database);
                $submit_message = 'Changing password';
?>
                        <br>
                                <script LANGUAGE="JavaScript">
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

                                </SCRIPT>
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
        elseif($submit == 'change_personal_info_pick')
        {
                draw_status_bar('Change password', $last_message);
                $user_obj = new User($SESSION_UID, $connection, $database);
                $cancel_message = 'Password alteration had been canceled';
                $submit_message = 'Changing password';
?>
                <br>
                                <script LANGUAGE="JavaScript">
                                function redirect(url_location)
                                {	window.location=url_location	}

                                </SCRIPT>
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
                                <center><input type="Submit" name="change_personal_info" value="Submit">
                                <input type="Button" name="submit" value="Cancel" onclick="redirect('profile.php?last_message=Personal Info alteration canceled')"></center>
                                </form>
<?php
        }
        elseif ($submit == 'Cancel')
                header('Location:admin.php?last_message='.$last_message);
