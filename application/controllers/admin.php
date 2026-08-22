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

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$user_obj = new User($_SESSION['uid'], $pdo);

if (!$user_obj->isAdmin()) {
    header('Location:error?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
draw_header(msg('label_admin'), $last_message);
ob_start();

$GLOBALS['smarty']->assign('active_admin', 'dashboard');

if (isset($GLOBALS['plugin']) && is_array($GLOBALS['plugin']->getPluginsList()) && $user_obj->isRoot()) {
    ?>
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header bg-white"><h6 class="mb-0"><?php echo msg('label_plugins') ?></h6></div>
        <div class="card-body">
            <?php callPluginMethod('onAdminMenu'); ?>
        </div>
    </div>
    <?php
}

$stats = [];
$countQueries = [
    'users' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}user",
    'departments' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}department",
    'categories' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}category",
    'files' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}data",
];
foreach ($countQueries as $k => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats[$k] = (int) $stmt->fetchColumn();
}
$GLOBALS['smarty']->assign('stats', $stats);
$GLOBALS['smarty']->assign('app_version', $GLOBALS['CONFIG']['current_version'] ?? '-');
$GLOBALS['smarty']->assign('db_version', Settings::get_db_version($pdo));

display_smarty_template('admin_dashboard.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');
draw_footer();
