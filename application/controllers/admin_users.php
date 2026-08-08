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

// Admin Users CRUD — Tabulator-based user management

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
draw_header(msg('users'), $last_message);

$GLOBALS['smarty']->assign('department_list', Department::getAllDepartments($pdo));
$GLOBALS['smarty']->assign('category_list', Category::getAllCategories($pdo));

ob_start();
display_smarty_template('admin_users.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_content.tpl');

echo '<script src="' . $GLOBALS['CONFIG']['base_url'] . 'js/bootstrap5/admin-crud.js?v=' . filemtime(dirname(__FILE__) . '/../../public/js/bootstrap5/admin-crud.js') . '"></script>';

draw_footer();