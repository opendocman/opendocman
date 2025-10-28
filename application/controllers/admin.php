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

session_start();

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

$request_state = e::h(($_REQUEST['state']+1));
?>
    <table border="1" cellspacing="5" cellpadding="5" >
        <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('users')?></font></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_department')?></font></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('category')?></font></th><?php if ($user_obj->isRoot()) {
    echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' . msg('file') . '</th></font>';
} ?>
        <?php
        if ($user_obj->isRoot()) {
            udf_admin_header();
        }
        ?>
        <tr>
            <td>
                <!-- User Admin -->
                <table border="0">
                    <tr>
                        <td><b><a href="<?php echo 'user?submit=adduser&state=' . ($request_state); ?>"><?php echo msg('label_add')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'user?submit=deletepick&state=' . ($request_state); ?>"><?php echo msg('label_delete')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'user?submit=updatepick&state=' . ($request_state); ?>"><?php echo msg('label_update')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'user?submit=showpick&state=' . ($request_state); ?>"><?php echo msg('label_display')?></a></b></td>
                    </tr>
                </table>
            </td>
            <td>
                <!-- Department Admin -->
                <table border="0">
                    <tr>
                        <td><b><a href="<?php echo 'department?submit=add&state=' . ($request_state); ?>"><?php echo msg('label_add')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'department?submit=deletepick&state=' . ($request_state); ?>"><?php echo msg('label_delete')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'department?submit=updatepick&state=' . ($request_state); ?>"><?php echo msg('label_update')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo 'department?submit=showpick&state=' . ($request_state); ?>"><?php echo msg('label_display')?></a></b></td>
                    </tr>
                </table>
            </td>
<td>
    <!-- Category Admin -->
    <table border="0">
        <tr>
            <td><b><a href="<?php echo 'category?submit=add&state=' . ($request_state); ?>"><?php echo msg('label_add')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'category?submit=deletepick&state=' . ($request_state); ?>"><?php echo msg('label_delete')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'category?submit=updatepick&state=' . ($request_state); ?>"><?php echo msg('label_update')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'category?submit=showpick&state=' . ($request_state); ?>"><?php echo msg('label_display')?></a></b></td>
        </tr>
    </table>
</td>
<?php if ($user_obj->isRoot()) {
    ?>
<td>
    <!-- Root-Only Section -->
    <table border="0" valign="top">
        <tr>
            <td ><b><a href="<?php echo 'delete?mode=view_del_archive&state=' . ($request_state);
    ?>"><?php echo msg('label_delete_undelete')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'toBePublished?mode=root&state=' . $request_state;
    ?>"><?php echo msg('label_reviews')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'rejects?mode=root&state=' . $request_state;
    ?>"><?php echo msg('label_rejections')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'check_exp?&state=' . $request_state;
    ?>"><?php echo msg('label_check_expiration')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo 'file_ops?&state=' . $request_state;
    ?>&submit=view_checkedout"><?php echo msg('label_checked_out_files')?></a></b></td>
        </tr>
    </table>
</td>
    <?php udf_admin_menu();
    ?>
</tr>

<tr>
    <td>
        <table>
            <tr>
                <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_settings')?></font></th>
            </tr>
            <tr>
                <td><b><a href="<?php echo 'settings?submit=update&state=' . $request_state;
    ?>"><?php echo msg('adminpage_edit_settings');
    ?></a></b></td>
            </tr>
            <tr>
                <td><b><a href="<?php echo 'filetypes?submit=update&state=' . $request_state;
    ?>"><?php echo msg('adminpage_edit_filetypes');
    ?></a></b></td>
            </tr>
        </table>
    </td>
     <td>
         <table>
             <tr>
                 <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('adminpage_reports');
    ?></font></th>
             </tr>
             <tr>
                 <td><b><a href="<?php echo 'access_log?submit=update&state=' . $request_state;
    ?>"><?php echo msg('adminpage_access_log');
    ?></a></b></td>
             </tr>
             <tr>
                 <td><b><a href="file_list_report"><?php echo msg('adminpage_reports_file_list');
    ?></a></b></td>
             </tr>

         </table>
     </td>
     <td>
         <table>
             <tr>
                 <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('adminpage_about_section_title');
    ?></font></th>
             </tr>
             <tr>
                 <td><b><?php echo msg('adminpage_about_section_app_version') . ": " . e::h($GLOBALS['CONFIG']['current_version']);
    ?></b></td>
             </tr>
             <tr>
                 <td><b><?php echo msg('adminpage_about_section_db_version') . ": " . e::h(Settings::get_db_version($pdo));
                         ?></b></td>
             </tr>
             <tr>
                 <td>&nbsp;</td>
             </tr>
         </table>
     </td>
</tr>

    <?php 
} ?>

</table>
    <?php

if (isset($GLOBALS['plugin']) && is_array($GLOBALS['plugin']->getPluginsList())) {
    if ($user_obj->isRoot()) {
        ?>
        <table border="1" cellspacing="5" cellpadding="5">
            <th bgcolor="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_plugins') ?></font></th>
            <tr>
                <td>
                    <?php
                    //Perform the admin loop section to add plugin menu items
                    callPluginMethod('onAdminMenu');
                    ?>
                </td>
            </tr>
        </table>
        <?php

    }
}
    ?>
    <?php
draw_footer();
