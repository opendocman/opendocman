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

$request_state = e::h(($_REQUEST['state']+1));
?>
<div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white"><h5 class="card-title mb-0"><?php echo msg('label_admin_crud')?></h5></div>
            <div class="card-body">
                <p class="card-text"><?php echo msg('label_admin_crud_desc')?></p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="admin_users" class="btn btn-primary"><?php echo msg('users')?></a>
                    <a href="admin_departments" class="btn btn-primary"><?php echo msg('label_department')?></a>
                    <a href="admin_categories" class="btn btn-primary"><?php echo msg('category')?></a>
                </div>
            </div>
        </div>
    </div>

<?php if ($user_obj->isRoot()) { ?>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('file')?></h5></div>
            <div class="list-group list-group-flush">
                <a href="<?php echo 'delete?mode=view_del_archive&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_delete_undelete')?></a>
                <a href="<?php echo 'toBePublished?mode=root&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_reviews')?></a>
                <a href="<?php echo 'rejects?mode=root&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_rejections')?></a>
                <a href="<?php echo 'check-exp?&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_check_expiration')?></a>
                <a href="<?php echo 'file_ops?&state=' . $request_state; ?>&submit=view_checkedout" class="list-group-item list-group-item-action"><?php echo msg('label_checked_out_files')?></a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('label_user_defined_fields')?></h5></div>
            <div class="list-group list-group-flush">
                <a href="<?php echo 'udf?submit=add&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_add')?></a>
                <a href="<?php echo 'udf?submit=deletepick&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('label_delete')?></a>
                <?php
                $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll();
                foreach ($result as $row) {
                    echo '<a href="udf?submit=edit&udf=' . e::h($row[0]) . '&state=' . $request_state . '" class="list-group-item list-group-item-action">' . e::h($row[2]) . '</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mt-1">
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('label_settings')?></h5></div>
            <div class="list-group list-group-flush">
                <a href="<?php echo 'settings?submit=update&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('adminpage_edit_settings')?></a>
                <a href="<?php echo 'filetypes?submit=update&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('adminpage_edit_filetypes')?></a>
                <a href="content_index" class="list-group-item list-group-item-action"><?php echo msg('label_content_search_index')?></a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('adminpage_reports')?></h5></div>
            <div class="list-group list-group-flush">
                <a href="<?php echo 'access_log?submit=update&state=' . $request_state; ?>" class="list-group-item list-group-item-action"><?php echo msg('adminpage_access_log')?></a>
                <a href="file_list_report" class="list-group-item list-group-item-action"><?php echo msg('adminpage_reports_file_list')?></a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('adminpage_about_section_title')?></h5></div>
            <div class="card-body">
                <p class="mb-1"><?php echo msg('adminpage_about_section_app_version') . ": " . e::h($GLOBALS['CONFIG']['current_version']); ?></p>
                <p class="mb-0"><?php echo msg('adminpage_about_section_db_version') . ": " . e::h(Settings::get_db_version($pdo)); ?></p>
            </div>
        </div>
    </div>
</div>

<?php } else { ?>
</div>
<?php } ?>

<?php if (isset($GLOBALS['plugin']) && is_array($GLOBALS['plugin']->getPluginsList()) && $user_obj->isRoot()) { ?>
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('label_plugins') ?></h5></div>
                <div class="card-body">
                    <?php callPluginMethod('onAdminMenu'); ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php
$content = ob_get_clean();
$GLOBALS['smarty']->assign('content', $content);
display_smarty_template('_content.tpl');
draw_footer();
