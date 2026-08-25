<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

// Admin email ingest audit log

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    header('Location: error?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$csrf_data = $GLOBALS['csrf']->getTokenForTemplate('/admin_crud_ajax');
$GLOBALS['smarty']->assign('csrf_token_value', $csrf_data['token']);
$GLOBALS['smarty']->assign('csrf_field_name', $csrf_data['field_name']);
$GLOBALS['smarty']->assign('csrf_index_name', $csrf_data['index_name']);
$GLOBALS['smarty']->assign('csrf_index_value', $csrf_data['index']);

draw_header(msg('adminpage_email_ingest_log'), $last_message);

$GLOBALS['smarty']->assign('active_admin', 'email_audit');
$GLOBALS['smarty']->assign('audit_outcome_filter', in_array($_REQUEST['outcome'] ?? '', ['created', 'rejected', 'error'], true) ? $_REQUEST['outcome'] : '');

ob_start();
display_smarty_template('admin_email_audit.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');

echo '<script src="' . $GLOBALS['CONFIG']['base_url'] . 'js/bootstrap5/admin-crud.js?v=' . filemtime(dirname(__FILE__) . '/../../public/js/bootstrap5/admin-crud.js') . '"></script>';

draw_footer();