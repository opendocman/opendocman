<?php
/*
check-out.php - performs checkout and updates database
Copyright (C) 2002-2004  Stephen Lawrence, Khoa Nguyen
Copyright (C) 2005-2011  Stephen Lawrence

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

// check for session and $_REQUEST['id']
session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

require_once("AccessLog_class.php");
 
$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (strchr($_REQUEST['id'], '_')) {
    header('Location:error.php?ec=20');
}
if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    header('Location:error.php?ec=2');
    exit;
}
/* if the user has read-only authority on the file, his check out 
will be the same as the person with admin or modify right except that the DB will not have any recored of him checking out this file.  Therefore, he will not be able to check-in the file on
the server
*/
$file_data_obj = new FileData($_GET['id'], $pdo);
$file_data_obj->setId($_GET['id']);
if ($file_data_obj->getError() != null || $file_data_obj->getStatus() > 0  || $file_data_obj->isArchived()) {
    header('Location:error.php?ec=2');
    exit;
}
if (!isset($_GET['submit'])) {
    draw_header(msg('area_check_out_file'), $last_message);
    // form not yet submitted
    // display information on how to initiate download
    checkUserPermission($_REQUEST['id'], $file_data_obj->WRITE_RIGHT, $file_data_obj);
    ?>


<p>

<form action="<?php echo $_SERVER['PHP_SELF'];
    ?>" method="get">
    <input type="hidden" name="id" value="<?php echo $_GET['id'];
    ?>">
    <input type="hidden" name="access_right" value="<?php echo $_GET['access_right'];
    ?>">
    <div class="buttons"><button class="regular" type="submit" name="submit" value="Click here"><?php echo msg('area_check_out_file')?></button>&nbsp;<?php echo msg('message_click_to_checkout_document')?></div>
</form>
    <?php echo msg('message_once_the_document_has_completed')?>&nbsp;<a href="out.php"><?php echo msg('button_continue')?></a>.
    <?php
    draw_footer();
}
// form submitted - download
else {
    $id = (int) $_REQUEST['id'];

    checkUserPermission($id, $file_data_obj->WRITE_RIGHT, $file_data_obj);
    $real_name = $file_data_obj->getName();
    if ($_GET['access_right'] == 'modify') {
        // since this user has checked it out and will modify it
        // update db to reflect new status
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET status = :uid WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':uid' => $_SESSION['uid'],
            ':id' => $id
        ));
    }
    // calculate filename
    $filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';

    if (file_exists($filename)) {
        // send headers to browser to initiate file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $real_name . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($filename);
        
        AccessLog::addLogEntry($id, 'O', $pdo);
        AccessLog::addLogEntry($id, 'D', $pdo);
    } else {
        echo 'File does not exist...';
    }
}
