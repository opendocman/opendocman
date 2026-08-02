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
// Page for changing personal info

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('area_personal_profile'), $last_message);
ob_start();
?>

    <input type="hidden" name="callee" value="profile">
    <div class="d-grid gap-2 col-12 col-md-6 mx-auto">
        <a href="user?submit=Modify+User&item=<?php echo $_SESSION['uid']; ?>" class="btn btn-primary"><?php echo msg('profilepage_update_profile')?></a>
    </div>
<?php
$content = ob_get_clean();
$GLOBALS['smarty']->assign('content', $content);
display_smarty_template('_content.tpl');
draw_footer();
