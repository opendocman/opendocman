<?php
/*
admin.php - provides admin interface
Copyright (C) 2007 Stephen Lawrence Jr., Jon Miner
Copyright (C) 2002-2011 Stephen Lawrence Jr.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

session_start();
// admin.php - administration functions for admin users 
// check for valid session
// includes
include('odm-load.php');
include('udf_functions.php');
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

// open a connection to the database
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;
// Check to see if user is admin
if(!$user_obj->isAdmin())
{
    header('Location:error.php?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
draw_header(msg('label_admin'), $last_message);
?>
    <table border="1" cellspacing="5" cellpadding="5" >
        <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('users')?></font></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_department')?></font></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('category')?></font></th><?php if($user_obj->isRoot()) echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' . msg('file') . '</th></font>'; ?>
        <?php
        if($user_obj->isRoot())
            udf_admin_header();
        ?>
        <tr>
            <td>
                <!-- User Admin -->
                <table border="0">
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('user.php?submit=adduser&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_add')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('user.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_delete')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('user.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_update')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('user.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_display')?></a></b></td>
                    </tr>
                </table>
            </td>
            <td>
                <!-- Department Admin -->
                <table border="0">
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('department.php?submit=add&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_add')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('department.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_delete')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('department.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_update')?></a></b></td>
                    </tr>
                    <tr>
                        <td><b><a href="<?php echo $secureurl->encode('department.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_display')?></a></b></td>
                    </tr>
                </table>
            </td>
<td>
    <!-- Category Admin -->
    <table border="0">
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('category.php?submit=add&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_add')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('category.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_delete')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('category.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_update')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('category.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_display')?></a></b></td>
        </tr>
    </table>
</td>
<?php if ( $user_obj->isRoot()	) { ?>
<td>
    <!-- Root-Only Section -->
    <table border="0" valign="top">
        <tr>
            <td ><b><a href="<?php echo $secureurl->encode('delete.php?mode=view_del_archive&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_delete_undelete')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('toBePublished.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_reviews')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('rejects.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_rejections')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('check_exp.php?&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('label_check_expiration')?></a></b></td>
        </tr>
        <tr>
            <td><b><a href="<?php echo $secureurl->encode('file_ops.php?&state=' . ($_REQUEST['state']+1)); ?>&submit=view_checkedout"><?php echo msg('label_checked_out_files')?></a></b></td>
        </tr>
    </table>
</td>
    <?php udf_admin_menu($secureurl); ?>
</tr>

<tr>
    <td>
        <table>
            <tr>
                <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_settings')?></font></th>
            </tr>
            <tr>
                <td><b><a href="<?php echo $secureurl->encode('settings.php?submit=update&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('adminpage_edit_settings'); ?></a></b></td>
            </tr>
            <tr>
                <td><b><a href="<?php echo $secureurl->encode('filetypes.php?submit=update&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('adminpage_edit_filetypes'); ?></a></b></td>
            </tr>
        </table>
    </td>
     <td>
         <table>
             <tr>
                 <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('adminpage_reports');?></font></th>
             </tr>
             <tr>
                 <td><b><a href="<?php echo $secureurl->encode('access_log.php?submit=update&state=' . ($_REQUEST['state']+1)); ?>"><?php echo msg('adminpage_access_log');?></a></b></td>
             </tr>
         </table>
     </td>
</tr>

    <?php } ?>

</table>
    <?php

if(is_array($GLOBALS['plugin']->getPluginsList()) && $user_obj->isRoot())
{
    ?>
            <table border="1" cellspacing="5" cellpadding="5" >
            <th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo msg('label_plugins')?></font></th>
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
    ?>
    <?php
draw_footer();