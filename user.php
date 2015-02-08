<?php
/*
user.php - user administration
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2015 Stephen Lawrence Jr.
 
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

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$user_obj = new User($_SESSION['uid'], $pdo);

// Make sure the item and uid are set, then check to make sure they are the same and they have admin privs, otherwise, user is not able to modify another users' info
if (isset($_SESSION['uid']) & isset($_GET['item'])) {
    if ($_SESSION['uid'] != $_GET['item'] && $user_obj->isAdmin() != true) {
        header('Location: error.php?ec=4');
        exit;
    }
}

$redirect = 'admin.php';

//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if ($user_obj->isAdmin() == true) {
    $mode = 'enabled';
} else {
    $mode = 'disabled';
}
if ($mode == 'disabled' && isset($_GET['item']) && $_GET['item'] != $_SESSION['uid']) {
    header('Location: error.php?ec=4');
    exit;
}


if (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'adduser') {
    draw_header(msg('area_add_new_user'), $last_message);
    // Check to see if user is admin

    $onBeforeAddUser = callPluginMethod('onBeforeAddUser');

    $mysql_auth = $GLOBALS["CONFIG"]["authen"] == 'mysql';

    $rand_password = makeRandomPassword();

    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array());
    $department_list = $stmt->fetchAll();

    $GLOBALS['smarty']->assign('onBeforeAddUser', $onBeforeAddUser);
    $GLOBALS['smarty']->assign('mysql_auth', $mysql_auth);
    $GLOBALS['smarty']->assign('rand_password', $rand_password);
    $GLOBALS['smarty']->assign('department_list', $department_list);

    display_smarty_template('user_add.tpl');

    draw_footer();
} elseif (isset($_POST['submit']) && 'Add User' == $_POST['submit']) {
    if (!$user_obj->isAdmin()) {
        header('Location: error.php?ec=4');
        exit;
    }
    // Check to make sure user does not already exist
    $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = :username ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':username' => $_POST['username']
    ));

    // If the above statement returns more than 0 rows, the user exists, so display error
    if ($stmt->rowCount() > 0) {
        header('Location: error.php?ec=3');
        exit;
    } else {
        $phonenumber = @$_POST['phonenumber'];

        if (!isset($_POST['can_add'])) {
            $_POST['can_add'] = 0;
        }
        if (!isset($_POST['can_checkin'])) {
            $_POST['can_checkin'] = 0;
        }

        // INSERT into user
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user
                    (username, password, department, phone, Email,last_name, first_name, can_add, can_checkin)
                    VALUES(
                        :username,
                        md5(:password),
                        :department,
                        :phonenumber,
                        :email,
                        :lastname,
                        :firstname,
                        :can_add,
                        :can_checkin
                )";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':username' => $_POST['username'],
            ':password' => $_POST['password'],
            ':department' => $_POST['department'],
            ':phonenumber' => $phonenumber,
            ':email' => $_POST['Email'],
            ':lastname' => $_POST['last_name'],
            ':firstname' => $_POST['first_name'],
            ':can_add' => $_POST['can_add'],
            ':can_checkin' => $_POST['can_checkin']
        ));

        // INSERT into admin
        $user_id = $pdo->lastInsertId();;
        if (!isset($_POST['admin'])) {
            $_POST['admin'] = '0';
        }
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES(:user_id, :admin)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':user_id' => $user_id,
            ':admin' => $_POST['admin']
        ));

        if (isset($_POST['department_review'])) {
            for ($i = 0; $i < sizeof($_POST['department_review']); $i++) {
                $dept_rev = $_POST['department_review'][$i];
                $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id, user_id) values(:dept_rev, :user_id)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':dept_rev' => $dept_rev,
                    ':user_id' => $user_id
                ));
            }
        }

        // mail user telling him/her that his/her account has been created.
        $user_obj = new user($_SESSION['uid'], $pdo);
        $new_user_obj = new User($user_id, $pdo);
        $date = date('Y-m-d H:i:s T'); //locale insensitive
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
        $get_full_name = $new_user_obj->getFullName();
        $new_user_full_name = $get_full_name[0] . ' ' . $get_full_name[1];
        $mail_from = $full_name . ' <' . $user_obj->getEmailAddress() . '>';
        $mail_headers = "From: $mail_from" . "\r\n";
        $mail_headers .= "Content-Type: text/plain; charset=UTF-8" . "\r\n";
        $mail_subject = msg('message_account_created_add_user');
        $mail_greeting = $new_user_full_name . ":\n\r\t" . msg('email_i_would_like_to_inform');
        $mail_body = msg('email_your_account_created') . ' ' . $date . '.  ' . msg('email_you_can_now_login') . ':' . "\n\r";
        $mail_body .= $GLOBALS['CONFIG']['base_url'] . "\n\n";
        $mail_body .= msg('username') . ': ' . $new_user_obj->getName() . "\n\n";
        if ($GLOBALS['CONFIG']['authen'] == 'mysql') {
            $mail_body .= msg('password') . ': ' . $_POST['password'] . "\n\n";
        }
        $mail_salute = "\n\r" . msg('email_salute') . ",\n\r$full_name";
        $mail_to = $new_user_obj->getEmailAddress();
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($mail_to, $mail_subject, ($mail_greeting . ' ' . $mail_body . $mail_salute), $mail_headers);
        }
        $last_message = urlencode(msg('message_user_successfully_added'));

        // Call the plugin API call for this section
        callPluginMethod('onAfterAddUser');

        header('Location: admin.php?last_message=' . $last_message);
    }
} elseif (isset($_POST['submit']) && 'Delete User' == $_POST['submit']) {
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location: error.php?ec=4');
        exit;
    }

    // DELETE admin info
    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_POST['id']));

    // DELETE user info
    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_POST['id']));

    // DELETE perms info
    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE uid = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_POST['id']));

    // Change data info to nobody
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET owner='0' WHERE owner = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_POST['id']));

    // back to main page
    $last_message = urlencode('#' . $_POST['id'] . ' ' . msg('message_user_successfully_deleted'));
    header('Location: admin.php?last_message=' . $last_message);
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Delete') {
    // If demo mode, don't allow them to update the demo account
    if (@$GLOBALS['CONFIG']['demo'] == 'True') {
        draw_header('Delete User ', $last_message);
        echo 'Sorry, demo mode only, you can\'t do that';
        draw_footer();
        exit;
    }
    $delete = '';
    $user_obj = new User($_POST['item'], $pdo);
    draw_header(msg('userpage_status_delete') . $user_obj->getName(), $last_message);

    // smarty calls
    $GLOBALS['smarty']->assign('user_id', $user_obj->getId());
    $GLOBALS['smarty']->assign('full_name', $user_obj->getFullName());

    display_smarty_template('user_delete.tpl');

    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'deletepick') {
    $deletepick = '';
    draw_header(msg('userpage_user_delete'), $last_message);

    $query = "SELECT id,username, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $user_list = $stmt->fetchAll();
    
    $GLOBALS['smarty']->assign('user_list', $user_list);
    display_smarty_template('user_delete_pick.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Show User') {

    $user_obj = new User($_POST['item'], $pdo);
    draw_header(msg('userpage_show_user') . $user_obj->getName(), $last_message);

    $GLOBALS['smarty']->assign('user', $user_obj);
    $GLOBALS['smarty']->assign('first_name', $user_obj->first_name);
    $GLOBALS['smarty']->assign('last_name', $user_obj->last_name);
    $GLOBALS['smarty']->assign('isAdmin', $user_obj->isAdmin());
    $GLOBALS['smarty']->assign('isReviewer', $user_obj->isReviewer());
    display_smarty_template('user_show.tpl');

    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'showpick') {
    draw_header(msg('userpage_choose_user'), $last_message);

    $showpick = '';

    $state = $_REQUEST['state'] + 1;

    $query = "SELECT id, username, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array());
    $user_list = $stmt->fetchAll();

    $GLOBALS['smarty']->assign('user_list', $user_list);
    $GLOBALS['smarty']->assign('state', $state);
    display_smarty_template('user_show_pick.tpl');
    
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Modify User') {
    // If demo mode, don't allow them to update the demo account
    if (@$GLOBALS['CONFIG']['demo'] == 'True') {
        draw_header(msg('userpage_update_user'), $last_message);
        echo msg('userpage_update_user_demo');
        draw_footer();
        exit;
    } else {
        // Begin Not Demo Mode
        $user_obj = new User($_REQUEST['item'], $pdo);
        draw_header(msg('userpage_update_user') . $user_obj->getName(), $last_message);
        ?>
        <form name="update" id="modifyUserForm" action="user.php" method="POST" enctype="multipart/form-data">
            <table border="0" cellspacing="5" cellpadding="5">
                <tr>

                    <?php
                    $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(':id' => $_REQUEST['item']));
                    $row = $stmt->fetch();

                    echo '<td><b>' . msg('userpage_id') . '</b></td><td colspan=4>' . $row['id'] . '</td>';
                    echo '<input type=hidden name=id value="' . $row['id'] . '">';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td><b>' . msg('userpage_last_name') . '</b></td><td colspan=4><INPUT NAME="last_name" TYPE="text" VALUE="' . $row['last_name'] . '" class="required" minlength="2" maxlength="255"></td></TR>';
                    echo '<td><b>' . msg('userpage_first_name') . '</b></td><td colspan=4><INPUT NAME="first_name" TYPE="text" VALUE="' . $row['first_name'] . '" class="required" minlength="2" maxlength="255"></td></TR>';
                    echo '<td><b>' . msg('userpage_username') . '</b></td><td colspan=4><INPUT NAME="username" TYPE="text" VALUE="' . $row['username'] . '" class="required" minlength="2" maxlength="25"></td></TR>';
                    echo "<tr>";
                    echo("<td><b>" . msg('userpage_phone_number') . "</b></td><td colspan=4><input name=\"phonenumber\" type=\"text\" value=\"{$row['phone']}\" maxlegnth=\"20\"></td>");
                    // If mysqlauthentication, then ask for password
                    if ($GLOBALS["CONFIG"]["authen"] == 'mysql')
                    {
                    ?>
                <tr>
                    <td><b><?php echo msg('userpage_password');?></b></td>
                    <td>
                        <input name="password" type="password" maxlength="10">
                        <font size="1"><?php echo msg('userpage_leave_empty');?></font>
                    </td>
                </tr>
                </tr>
                <?php
                }//endif
                ?>
                <tr>
                    <td><b><?php echo msg('userpage_email');?></td>
                    <td colspan=4>
                        <input name="Email" type="text" value="<?php echo $row['Email']; ?>" class="email required"
                               maxlength="50"></td>
                </tr>
                <tr>

                </tr>
                <tr>
                    <td><b><?php echo msg('userpage_department');?></b></td>
                    <td colspan=3>

                        <select name="department" <?php echo $mode; ?>>
                            <?php
                            $user_department = $user_obj->getDeptID();
                            // query to get a list of departments
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute(array());
                            $result = $stmt->fetchAll();

                            foreach ($result as $row) {
                                if ($row['id'] == $user_department) {
                                    echo '<option selected value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                } else {
                                    echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><b><?php echo msg('userpage_admin');?></b></td>
                    <td colspan=1>
                        <?php
                        if ($user_obj->isAdmin()) {
                            echo '<input name="admin" type="checkbox" value="1" checked ' . $mode . ' id="cb_admin"></input>' . "\n";
                        } else {
                            echo '<input name="admin" type="checkbox" value="1"  ' . $mode . ' id="cb_admin"></input>' . "\n";
                        }
                        ?>
                </TR>
                <TR id="userReviewDepartmentRow" <?php if ($user_obj->isAdmin()) {
                    echo 'style="display: none;"';
                } ?>>
                    <TD id="userReviewDepartmentLabelTd"><?php echo msg('userpage_reviewer_for');?></TD>
                    <TD id="userReviewDepartmentListTd">
                        <SELECT class="multiView" id="userReviewDepartmentsList" name='department_review[]'
                                multiple="multiple" <?php echo $mode; ?>>
                            <?php
                            $user_department = $user_obj->getDeptID();
                            $query = "SELECT dept_id, user_id FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = :user_id";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute(array(':user_id' => $_REQUEST['item']));
                            $result = $stmt->fetchAll();

                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute(array());
                            $result2 = $stmt->fetchAll();

                            //for dept that this user is reviewing for
                            $i = 0;
                            foreach ($result as $row) {
                                $department_reviewer[$i][0] = $row[0];
                                $department_reviewer[$i][1] = $row[1];
                                $i++;
                            }
                            // for all depts
                            $i = 0;
                            foreach ($result2 as $row) {
                                $all_department[$i][0] = $row[0];
                                $all_department[$i][1] = $row[1];
                                $i++;
                            }

                            for ($d = 0; $d < sizeof($all_department); $d++) {
                                $found = false;
                                if (isset($department_reviewer)) {
                                    for ($r = 0; $r < sizeof($department_reviewer); $r++) {
                                        if ($all_department[$d][0] == $department_reviewer[$r][0]) {
                                            echo("<option value=\"" . $all_department[$d][0] . "\" selected> " . $all_department[$d][1] . "</option>\n");
                                            $found = true;
                                            $r = sizeof($department_reviewer);
                                        }
                                    }
                                }
                                if (!$found) {
                                    echo("<option VALUE=\"" . $all_department[$d][0] . "\">" . $all_department[$d][1] . "</option>\n");
                                }
                            }

                            ?>
                        </SELECT>
                    </TD>
                </TR>
                <?php
                $can_add = '';
                $can_checkin = '';
                if ($user_obj->can_add == 1) {
                    $can_add = "checked";
                }
                if ($user_obj->can_checkin == 1) {
                    $can_checkin = "checked";
                }
                ?>
                <tr>
                    <td><?php echo msg('userpage_can_add')?>?</td>
                    <td>
                        <input name="can_add" type="checkbox" value="1" <?php echo $can_add; ?> id="cb_can_add"></input>
                    </td>
                </tr>
                <tr>
                    <td><?php echo msg('userpage_can_checkin')?>?</td>
                    <td>
                        <input name="can_checkin" type="checkbox" value="1" <?php echo $can_checkin; ?>
                               id="cb_can_checkin"></input>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <INPUT type="hidden" name="set_password" value="0">

                        <div class="buttons">
                            <button class="positive" type="Submit" name="submit"
                                    value="Update User"><?php echo msg('userpage_button_update')?></button>
                        </div>
                    </td>
                    <td>
                        <div class="buttons">
                            <button class="negative cancel" type="Submit" name="cancel"
                                    value="Cancel"><?php echo msg('userpage_button_cancel')?></button>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
        <script>
            $(document).ready(function () {
                $('#modifyUserForm').validate();
            });
        </script>
    <?php
    } // End Not Demo mode
    draw_footer();
} elseif (isset($_POST['submit']) && 'Update User' == $_POST['submit']) {

    // Check to make sue they are either the user being modified or an admin
    if (($_POST['id'] != $_SESSION['uid']) && !$user_obj->isAdmin()) {
        header('Location: error.php?ec=4');
        exit;
    }

    if (!isset($_POST['admin']) || $_POST['admin'] == '') {
        $_POST['admin'] = '0';
    }

    if (!isset($_POST['can_add']) || $_POST['can_add'] == '') {
        $_POST['can_add'] = '0';
    }
    if (!isset($_POST['can_checkin']) || $_POST['can_checkin'] == '') {
        $_POST['can_checkin'] = '0';
    }

    // UPDATE admin info
    if ($user_obj->isAdmin()) {
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}admin set admin = :admin WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':admin' => $_POST['admin'],
            ':id' => $_POST['id']
        ));
    }
    // UPDATE into user
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET ";

    if ($user_obj->isAdmin()) {
        $query .= " username = :username, ";
        $query .= " can_add = :can_add, ";
        $query .= " can_checkin = :can_checkin, ";
    }

    if (!empty($_POST['password'])) {
        $query .= " password = md5(:password), ";
    }
    if ($user_obj->isAdmin()) {
        if (isset($_POST['department'])) {
            $query .= " department = :department, ";
        }
    }
    if (isset($_POST['phonenumber'])) {
        $query .= " phone = :phonenumber, ";
    }

    if (isset($_POST['Email'])) {
        $query .= " Email = :Email, ";
    }

    if (isset($_POST['last_name'])) {
        $query .= " last_name = :last_name, ";
    }

    if (isset($_POST['first_name'])) {
        $query .= " first_name = :first_name ";
    }
    $query .= " WHERE id = :id ";

    $stmt = $pdo->prepare($query);
    if (!empty($_POST['password'])) {
        $stmt->bindParam(':password', $_POST['password']);
    }
    if ($user_obj->isAdmin()) {
        if (isset($_POST['department'])) {
            $stmt->bindParam(':department', $_POST['department']);
        }
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':can_add', $_POST['can_add']);
        $stmt->bindParam(':can_checkin', $_POST['can_checkin']);
    }
    if (isset($_POST['phonenumber'])) {
        $stmt->bindParam(':phonenumber', $_POST['phonenumber']);
    }
    if (isset($_POST['Email'])) {
        $stmt->bindParam(':Email', $_POST['Email']);
    }
    if (isset($_POST['last_name'])) {
        $stmt->bindParam(':last_name', $_POST['last_name']);
    }
    if (isset($_POST['first_name'])) {
        $stmt->bindParam(':first_name', $_POST['first_name']);
    }
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();


    if ($user_obj->isAdmin()) {
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':user_id' => $_POST['id']));

        if (isset($_REQUEST['department_review'])) {
            for ($i = 0; $i < sizeof($_REQUEST['department_review']); $i++) {
                $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id,user_id) VALUES(:dept_id, :user_id)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':dept_id' => $_REQUEST['department_review'][$i],
                    ':user_id' => $_POST['id']
                ));
            }
        }
    }

    // back to main page

    $last_message = urlencode(msg('message_user_successfully_updated'));
    header('Location: ' . $redirect . '?last_message=' . $last_message);
} // CHOOSE USER TO UPDATE
elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'updatepick') {
    draw_header(msg('userpage_modify_user'), $last_message);

    // Check to see if user is admin
    $query = "SELECT admin FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = :uid and admin = '1'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':uid' => $_SESSION['uid']
    ));

    if ($stmt->rowCount() <= 0) {
        header('Location: error.php?ec=4');
        exit;
    }
    ?>
    <form action="user.php" method="POST" enctype="multipart/form-data">
        <INPUT type="hidden" name="state" value="<?php echo($_REQUEST['state'] + 1); ?>"/>
        <table border="0" cellspacing="5" cellpadding="5">
            <tr>
                <td><b><?php echo msg('userpage_user');?></b></td>
                <td colspan=3><select name="item">
                        <?php

                        // query to get a list of users
                        $query = "SELECT id, username, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $result = $stmt->fetchAll();

                        foreach ($result as $row) {
                            echo '<option value="' . $row['id'] . '">' . $row['last_name'] . ', ' . $row['first_name'] . ' - ' . $row['username'] . '</option>';
                        }

                        ?>
                </td>
                <td>
                    <div class="buttons">
                        <button class="positive" type="Submit" name="submit"
                                value="Modify User"><?php echo msg('userpage_button_modify')?></button>
                    </div>
                </td>
                <td>
                    <div class="buttons">
                        <button class="negative" type="Submit" name="cancel"
                                value="Cancel"><?php echo msg('userpage_button_cancel')?></button>
                    </div>
                </td>
            </tr>
        </table>
    </form>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'change_password_pick') {
    draw_header('Change password', $last_message);
    $user_obj = new User($_SESSION['uid'], $pdo);
    $submit_message = 'Changing password';

    ?>
    <br>
    <script type="text/javascript">
        function Validate(dataform) {
            if (dataform.new_password.value != dataform.confirm_password.value) {
                alert("The two password fields do not match.  Please recheck.")
                return false
            }
            else {
                return true
            }
        }
        function redirect(url_location) {
            window.location = url_location
        }

    </script>
    <form action="commitchange.php" method="post" enctype="multipart/form-data">
        <table name="header" align="center" border="1">
            <tr>
                <td align="center" bgcolor="teal"><b>User Information</b></td>
            </tr>
        </table>
        <table name="list" align="center" border="1">
            <tr>
                <td align="left">ID</td>
                <td align="left"><?php echo $user_obj->getDeptId(); ?></td>
            </tr>
            <tr>
                <td align="left">Username</td>
                <td align="left"><?php echo $user_obj->getName(); ?></td>
            </tr>
            <tr>
                <td align="left">Department</td>
                <td align="left"><?php echo $user_obj->getDeptName(); ?></td>
            </tr>
        </table>
        <br>
    </form>
<?php
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'change_personal_info_pick') {
    draw_header('Change password', $last_message);
    $user_obj = new User($_SESSION['uid'], $pdo);
    $cancel_message = 'Password alteration had been canceled';
    $submit_message = 'Changing password';
    // If demo mode, don't allow them to update the demo account
    if (@$GLOBALS['CONFIG']['demo'] == 'True') {
        echo 'Sorry, demo mode only, you can\'t do that';
        draw_footer();
        exit;
    }
    ?>
    <br>
    <script type="text/javascript">
        function redirect(url_location) {
            window.location = url_location
        }

    </script>
    <form action="commitchange.php" method="post" enctype="multipart/form-data">
        <table name="header" align="center" border="1">
            <tr>
                <td align="center" bgcolor="teal"><b>User Information</b></td>
            </tr>
        </table>
        <table name="list" align="center" border="1">
            <tr>
                <td align="left">ID</td>
                <td align="left"><?php echo $user_obj->getDeptId(); ?></td>
            </tr>
            <tr>
                <td align="left">Username</td>
                <td align="left"><input type="text" name="username" value="<?php echo $user_obj->getName(); ?>"></td>
            </tr>
            <tr>
                <td align="left">Department</td>
                <td align="left"><?php echo $user_obj->getDeptName(); ?></td>
            </tr>
        </table>
        <br>
        <input type="hidden" name="submit" value="change_personal_info">
        <input type="Submit" name="change_personal_info" value="Submit">
        <input type="Button" name="cancel" value="Cancel"
               onclick="redirect('profile.php?last_message=Personal Info alteration canceled')">
    </form>
<?php
} elseif (isset($_REQUEST['cancel']) and $_REQUEST['cancel'] == 'Cancel') {
    $last_message = "Action Cancelled";
    header('Location: admin.php?last_message=' . $last_message);
} else {
    header('Location: admin.php?last_message=' . urlencode('Unrecognizalbe action'));
}