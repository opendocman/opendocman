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

$outcomeFilter = (isset($_REQUEST['outcome']) && in_array($_REQUEST['outcome'], ['created', 'rejected', 'error'], true))
    ? $_REQUEST['outcome']
    : '';

$limit = 200;
$query = "SELECT id, message_id, from_address, outcome, document_id, reason, created
          FROM {$GLOBALS['CONFIG']['db_prefix']}email_audit";
$params = [];
if ($outcomeFilter !== '') {
    $query .= ' WHERE outcome = :outcome';
    $params[':outcome'] = $outcomeFilter;
}
$query .= ' ORDER BY id DESC LIMIT ' . (int) $limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$auditRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

draw_header(msg('adminpage_email_ingest_log'), $last_message);

$GLOBALS['smarty']->assign('active_admin', 'email_audit');
$GLOBALS['smarty']->assign('audit_rows', $auditRows);
$GLOBALS['smarty']->assign('audit_outcome_filter', $outcomeFilter);
ob_start();
display_smarty_template('admin_email_audit.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');

draw_footer();