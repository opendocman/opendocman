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

// (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
// User administration
use Aura\Html\Escaper as e;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$user_obj = new User($_SESSION['uid'], $pdo);

// Make sure the item and uid are set, then check to make sure they are the same and they have admin privs, otherwise, user is not able to modify another users' info
if (isset($_SESSION['uid']) & isset($_GET['item'])) {
    if ($_SESSION['uid'] != $_GET['item'] && $user_obj->isAdmin() != true) {
        header('Location: error?ec=4');
        exit;
    }
}

$redirect = 'admin';

//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if ($user_obj->isAdmin() == true) {
    $mode = 'enabled';
} else {
    $mode = 'disabled';
}
if ($mode == 'disabled' && isset($_GET['item']) && $_GET['item'] != $_SESSION['uid']) {
    header('Location: error?ec=4');
    exit;
}


if (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'adduser') {
    header('Location: admin_users');
    exit;
} elseif (isset($_POST['submit']) && 'Add User' == $_POST['submit']) {
    // Validate CSRF token for Add User operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    if (!$user_obj->isAdmin()) {
        header('Location: error?ec=4');
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
        header('Location: error?ec=3');
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
                    (username, password, department, phone, Email,last_name, first_name, can_add, can_checkin, pw_change_required)
                    VALUES(
                        :username,
                        :password,
                        :department,
                        :phonenumber,
                        :email,
                        :lastname,
                        :firstname,
                        :can_add,
                        :can_checkin,
                        1
                )";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':username' => $_POST['username'],
            ':password' => PasswordHasher::hash($_POST['password']),
            ':department' => $_POST['department'],
            ':phonenumber' => $phonenumber,
            ':email' => $_POST['Email'],
            ':lastname' => $_POST['last_name'],
            ':firstname' => $_POST['first_name'],
            ':can_add' => $_POST['can_add'],
            ':can_checkin' => $_POST['can_checkin']
        ));

        // INSERT into admin
        $user_id = $pdo->lastInsertId();
        ;
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
        $mail_from = e::h($full_name) . ' <' . $user_obj->getEmailAddress() . '>';
        $mail_headers = "From: " . e::h($mail_from)  . PHP_EOL;
        $mail_headers .= "Content-Type: text/plain; charset=UTF-8" . PHP_EOL;
        $mail_subject = msg('message_account_created_add_user');
        $mail_greeting = e::h($new_user_full_name) . ":" . PHP_EOL . msg('email_i_would_like_to_inform');
        $mail_body = msg('email_your_account_created') . ' ' . $date . '.  ' . msg('email_you_can_now_login') . ':' . PHP_EOL . PHP_EOL;
        $mail_body .= $GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
        $mail_body .= msg('username') . ': ' . $new_user_obj->getName() . PHP_EOL . PHP_EOL;
        if ($GLOBALS['CONFIG']['authen'] == 'mysql') {
            $mail_body .= msg('password') . ': ' . e::h($_POST['password']) . PHP_EOL . PHP_EOL;
        }
        $mail_salute =  msg('email_salute') . ",". PHP_EOL . e::h($full_name);
        $mail_to = $new_user_obj->getEmailAddress();
        $mail_flags = "-f".$user_obj->getEmailAddress();
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($mail_to, $mail_subject, $mail_greeting . ' ' . $mail_body . $mail_salute, $mail_headers);
        }
        $last_message = urlencode(msg('message_user_successfully_added'));

        // Call the plugin API call for this section
        callPluginMethod('onAfterAddUser');

        header('Location: admin?last_message=' . urlencode($last_message));
    }
} elseif (isset($_POST['submit']) && 'Delete User' == $_POST['submit']) {
    // Validate CSRF token for Delete User operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location: error?ec=4');
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

    // Change owner to root user
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET owner='{$GLOBALS['CONFIG']['root_id']}' WHERE owner = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_POST['id']));

    // back to main page
    $last_message = urlencode('#' . $_POST['id'] . ' ' . msg('message_user_successfully_deleted'));
    header('Location: admin?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Delete') {
    header('Location: admin');
    exit;
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'deletepick') {
    header('Location: admin');
    exit;
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Show User') {
    header('Location: admin');
    exit;
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'showpick') {
    header('Location: admin');
    exit;
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Modify User') {
    // Self-edit: render the profile form for the requested user. Admins may edit
    // any user; non-admins may only edit their own (enforced above).
    if (@$GLOBALS['CONFIG']['demo'] == 'True') {
        draw_header(msg('userpage_update_user'), $last_message);
        echo msg('userpage_update_user_demo');
        draw_footer();
        exit;
    }

    draw_header(msg('userpage_update_user'), $last_message);
    $target_user = new User($_REQUEST['item'], $pdo);
    $display_reviewer_row = $target_user->isAdmin() ? true : false;

    $query = "SELECT dept_id, user_id FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':user_id' => $_REQUEST['item']));
    $dept_reviewer = $stmt->fetchAll();

    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array());
    $department_list = $stmt->fetchAll();

    // Departments this user reviews for.
    $department_reviewer = [];
    foreach ($dept_reviewer as $row) {
        $department_reviewer[$row[0]] = $row[0];
    }
    // Options for the reviewer multi-select (each dept marked selected if the user reviews it).
    $department_select_options = '';
    foreach ($department_list as $row) {
        $selected = isset($department_reviewer[$row[0]]) ? ' selected' : '';
        $department_select_options .= '<option value="' . e::h($row[0]) . '"' . $selected . '>' . e::h($row[1]) . '</option>';
    }

    $can_add = $target_user->can_add == 1 ? 'checked' : '';
    $can_checkin = $target_user->can_checkin == 1 ? 'checked' : '';

    $csrf_data = $GLOBALS['csrf']->getTokenForTemplate('/user');
    $GLOBALS['smarty']->assign('csrf_token_field', $csrf_data['field']);
    $GLOBALS['smarty']->assign('csrf_token_value', $csrf_data['token']);
    $GLOBALS['smarty']->assign('csrf_field_name', $csrf_data['field_name']);
    $GLOBALS['smarty']->assign('csrf_index_name', $csrf_data['index_name']);

    $GLOBALS['smarty']->assign('user', $target_user);
    $GLOBALS['smarty']->assign('mysql_auth', $GLOBALS['CONFIG']['authen'] == 'mysql');
    $GLOBALS['smarty']->assign('mode', $mode);
    $GLOBALS['smarty']->assign('user_department', $target_user->getDeptID());
    $GLOBALS['smarty']->assign('display_reviewer_row', $display_reviewer_row);
    $GLOBALS['smarty']->assign('is_admin', $target_user->isAdmin());
    $GLOBALS['smarty']->assign('department_list', $department_list);
    $GLOBALS['smarty']->assign('department_select_options', $department_select_options);
    $GLOBALS['smarty']->assign('can_add', $can_add);
    $GLOBALS['smarty']->assign('can_checkin', $can_checkin);
    $GLOBALS['smarty']->assign('pw_change_required_checked', $target_user->isPasswordChangeRequired() ? 'checked' : '');
    display_smarty_template('user/edit.tpl');
    draw_footer();
    exit;
} elseif (isset($_POST['submit']) && 'Update User' == $_POST['submit']) {
    // Validate CSRF token for Update User operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }

    // Check to make sue they are either the user being modified or an admin
    if (($_POST['id'] != $_SESSION['uid']) && !$user_obj->isAdmin()) {
        header('Location: error?ec=4');
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
        $query .= " password = :password, ";
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
    if ($user_obj->isAdmin()) {
        $query .= " , pw_change_required = :pw_change_required ";
    }
    $query .= " WHERE id = :id ";

    $stmt = $pdo->prepare($query);
    if (!empty($_POST['password'])) {
        $passwordHash = PasswordHasher::hash($_POST['password']);
        $stmt->bindParam(':password', $passwordHash);
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
    if ($user_obj->isAdmin()) {
        $pw_change_required = isset($_POST['pw_change_required']) ? 1 : 0;
        $stmt->bindParam(':pw_change_required', $pw_change_required, PDO::PARAM_INT);
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
    header('Location: out?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'updatepick') {
    header('Location: admin');
    exit;
} elseif (isset($_REQUEST['cancel']) and $_REQUEST['cancel'] == 'Cancel') {
    $last_message = "Action Cancelled";
    header('Location: admin?last_message=' . urlencode($last_message));
} else {
    header('Location: admin?last_message=' . urlencode('Unrecognizalbe action'));
}
