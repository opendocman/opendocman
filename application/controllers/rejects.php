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
// Show rejected files

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$with_caption = false;

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_POST['submit'])) {
    draw_header(msg('message_documents_rejected'), $last_message);
    ob_start();

    try {
        $user_obj = new User($_SESSION['uid'], $pdo);
        $user_perms_obj = new UserPermission($_SESSION['uid'], $pdo);
    } catch (Exception $e) {
        error_log("Rejects.php - Error creating user objects: " . $e->getMessage());
        error_log("Rejects.php - Session UID: " . (isset($_SESSION['uid']) ? $_SESSION['uid'] : 'NOT SET'));
        header('Location: error?ec=1&last_message=' . urlencode('User initialization failed'));
        exit;
    }

    // Provide CSRF token for the out.tpl delete button
    if (isset($GLOBALS['csrf'])) {
        $csrf_data = $GLOBALS['csrf']->getTokenForTemplate('/delete');
        $GLOBALS['smarty']->assign('delete_csrf_field', $csrf_data['field']);
    }

    $GLOBALS['smarty']->assign('state', -1);
    display_smarty_template('out.tpl');
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
}
?>