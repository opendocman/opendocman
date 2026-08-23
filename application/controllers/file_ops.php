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

use Aura\Html\Escaper as e;

// (C) 2002-2004 Stephen Lawrence Jr, Khoa Nguyen
// Admin file operations

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

// get a list of documents the user has "view" permission for
// get current user's information-->department
$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isRoot()) {
    header('Location:error?ec=24');
}
$flag = 0;
if (isset($_GET['submit']) && $_GET['submit'] == 'view_checkedout') {
    draw_header(msg('label_checked_out_files'), $last_message);

    $GLOBALS['smarty']->assign('active_admin', 'file_ops');
    $GLOBALS['smarty']->assign('state', 3);
    $csrf = $GLOBALS['csrf']->getTokenForTemplate('/file_ops');
    $GLOBALS['smarty']->assign('clear_csrf_field', $csrf['field']);
    ob_start();
    display_smarty_template('out.tpl');
    $GLOBALS['smarty']->assign('content', ob_get_clean());
    display_smarty_template('_admin_content.tpl');
    draw_footer();
} elseif (isset($_POST['submit']) && $_POST['submit'] == 'Clear Status') {
    // Validate CSRF token for Clear Status operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
    if (isset($_POST["checkbox"])) {
        foreach ($_POST['checkbox'] as $cbox) {
            $file_id = $cbox;
            $file_obj = new FileData($file_id, $pdo);
            $file_obj->setStatus(0);
        }
    }
    header('Location:file_ops?state=2&submit=view_checkedout');
} else {
    echo 'Nothing to do';
}
