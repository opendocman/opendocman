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

// Administer allowedFileTypes values

session_start();

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$user_obj = new User($_SESSION['uid'], $pdo);
$filetypes = new FileTypes($pdo);

//If the user is not an admin error out.
if (!$user_obj->isRoot() == true) {
    header('Location: error?ec=24');
    exit;
}

if (isset($_REQUEST['submit']) && $_REQUEST['submit']=='update') {
    draw_header(msg('label_filetypes'), $last_message);
    $filetypes->edit();
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Save') {
    // Validate CSRF token for Save operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    draw_header(msg('label_filetypes'), $last_message);

    if ($filetypes->save($_POST)) {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    } else {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);
    $filetypes->edit();
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Cancel') {
    header('Location: admin?last_message=' . urlencode(msg('message_action_cancelled')));
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'AddNew') {
    draw_header(msg('label_filetypes'), $last_message);
    display_smarty_template('filetype_add.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'AddNewSave') {
    // Validate CSRF token for Add operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    if ($filetypes->add($_POST)) {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    } else {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);

    draw_header(msg('label_filetypes'), $last_message);

    $filetypes->edit();
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'DeleteSelect') {
    draw_header(msg('label_filetypes'), $last_message);

    $filetypes->deleteSelect();
    draw_footer();
} elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Delete') {
    // Validate CSRF token for Delete operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    if ($filetypes->delete($_POST)) {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    } else {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);
    draw_header(msg('label_filetypes'), $last_message);
    $filetypes->edit();
    draw_footer();
} else {
    header('Location: admin?last_message=' . urlencode(msg('message_nothing_to_do')));
}
