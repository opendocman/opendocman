<?php
use Aura\Html\Escaper as e;

/*
file_ops.php - admin file operations
Copyright (C) 2002-2004 Stephen Lawrence Jr, Khoa Nguyen
Copyright (C) 2005-2015 Stephen Lawrence Jr.

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
include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

// get a list of documents the user has "view" permission for
// get current user's information-->department
$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isRoot()) {
    header('Location:error.php?ec=24');
}
$flag = 0;
if (isset($_GET['submit']) && $_GET['submit'] == 'view_checkedout') {
    echo PHP_EOL . '<form name="table" action="file_ops.php" method="POST">';
    echo PHP_EOL . '<input name="submit" type="hidden" value="Clear Status">';
    draw_header(msg('label_checked_out_files'), $last_message);

    $file_id_array = $user_obj->getCheckedOutFiles();

    $page_url = 'file_ops.php?';
    $user_perm_obj = new UserPermission($_SESSION['uid'], $pdo);
    $list_status = list_files($file_id_array, $user_perm_obj, $GLOBALS['CONFIG']['dataDir'], true, true);
    if ($list_status != -1) {
        echo PHP_EOL . '<BR><div class="buttons"><button class="positive" type="submit" name="submit" value="Clear Status">' . msg('button_clear_status') . '</button></div><br />';
        echo PHP_EOL . '</form>';
    }
    draw_footer();
} elseif (isset($_POST['submit']) && $_POST['submit'] == 'Clear Status') {
    if (isset($_POST["checkbox"])) {
        foreach ($_POST['checkbox'] as $cbox) {
            $file_id = $cbox;
            $file_obj = new FileData($file_id, $pdo);
            $file_obj->setStatus(0);
        }
    }
    header('Location:file_ops.php?state=2&submit=view_checkedout');
} else {
    echo 'Nothing to do';
}
