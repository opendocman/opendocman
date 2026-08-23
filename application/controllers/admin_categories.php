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

// Admin Categories CRUD — Tabulator-based category management

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

draw_header(msg('category'), $last_message);

$GLOBALS['smarty']->assign('department_list', Department::getAllDepartments($pdo));
$GLOBALS['smarty']->assign('category_list', Category::getAllCategories($pdo));

$avail_users_query = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name, first_name";
$avail_users_stmt = $pdo->prepare($avail_users_query);
$avail_users_stmt->execute();
$GLOBALS['smarty']->assign('user_list', $avail_users_stmt->fetchAll(PDO::FETCH_ASSOC));

$GLOBALS['smarty']->assign('active_admin', 'categories');
ob_start();
display_smarty_template('admin_categories.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');

echo '<script src="' . $GLOBALS['CONFIG']['base_url'] . 'js/permissions-editor.js?v=' . filemtime(dirname(__FILE__) . '/../../public/js/permissions-editor.js') . '"></script>';
echo '<script>window.permEditorLabels = ' . json_encode(perm_editor_labels()) . ';</script>';
echo '<script src="' . $GLOBALS['CONFIG']['base_url'] . 'js/bootstrap5/admin-crud.js?v=' . filemtime(dirname(__FILE__) . '/../../public/js/bootstrap5/admin-crud.js') . '"></script>';

draw_footer();