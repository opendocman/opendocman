<?php
use Aura\Html\Escaper as e;

/*
delete.php - delete a file from the respository and the db
Copyright (C) 2002-2004  Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2016  Stephen Lawrence Jr.

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

// check session
session_start();
if (!isset($_SESSION['uid'])) {
    header('Location:error.php?ec=1');
    exit;
}
include('odm-load.php');
require_once("AccessLog_class.php");

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$redirect = 'out.php';

$userperm_obj = new User_Perms($_SESSION['uid'], $pdo);

// User has requested a deletion from the file detail page
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'tmpdel') {
    if (!isset($_REQUEST['num_checkboxes'])) {
        $_REQUEST['num_checkboxes'] =1;
    }
    // all ok, proceed!
    if (!is_dir($GLOBALS['CONFIG']['archiveDir'])) {
        // Make sure directory is writable
        if (!mkdir($GLOBALS['CONFIG']['archiveDir'], 0775)) {
            $last_message='Could not create ' . $GLOBALS['CONFIG']['archiveDir'];
            header('Location:error.php?ec=23&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    for ($i = 0; $i<$_REQUEST['num_checkboxes']; $i++) {
        if (isset($_REQUEST['id' . $i])) {
            $id = $_REQUEST['id' . $i];
            if (strchr($id, '_')) {
                header('Location:error.php?ec=20');
            }
            if ($userperm_obj->canAdmin($id)) {
                $file_obj = new FileData($id, $pdo);
                $file_obj->temp_delete();
                fmove($GLOBALS['CONFIG']['dataDir'] . $id . '.dat', $GLOBALS['CONFIG']['archiveDir'] . $id . '.dat');
            }
            AccessLog::addLogEntry($_REQUEST['id' . $i], 'X', $pdo);
        }
    }
    // delete from directory
    // clean up and back to main page
    $last_message = msg('message_document_has_been_archived');
        
    // Call the plugin API call for this section
    callPluginMethod('onAfterArchiveFile');
    
    header('Location: out.php?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'view_del_archive') {
    
    //publishable=2 for archive deletion
    $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE publishable=2";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $array_id = array();
    $i = 0;
    foreach ($result as $row) {
        $array_id[$i] = $row['id'];
        $i++;
    }

    $luserperm_obj = new UserPermission($_SESSION['uid'], $pdo);
    
    draw_header(msg('area_deleted_files'), $last_message);
    $page_url = e::h($_SERVER['PHP_SELF']) . '?mode=' . $_REQUEST['mode'];

    $user_obj = new User($_SESSION['uid'], $pdo);
    $userperms = new UserPermission($_SESSION['uid'], $pdo);

    $list_status = list_files($array_id, $userperms, $GLOBALS['CONFIG']['archiveDir'], true);

    if ($list_status != -1) {
        $GLOBALS['smarty']->assign('lmode', '');
        display_smarty_template('deleteview.tpl');
    }
} elseif (isset($_POST['submit']) && $_POST['submit']=='Delete file(s)') {
    isset($_REQUEST['checkbox']) ? $_REQUEST['checkbox'] : '';

    foreach ($_REQUEST['checkbox'] as $value) {
        if (!pmt_delete($value)) {
            header('Location: error.php?ec=21');
            exit;
        }
    }
    header('Location:' . urlencode($redirect) . '?last_message=' . urlencode(msg('undeletepage_file_permanently_deleted')));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Undelete') {
    if (isset($_REQUEST['checkbox'])) {
        foreach ($_REQUEST['checkbox'] as $fileId) {
            $file_obj = new FileData($fileId, $pdo);
            $file_obj->undelete();
            fmove($GLOBALS['CONFIG']['archiveDir'] . $fileId . '.dat', $GLOBALS['CONFIG']['dataDir'] . $fileId . '.dat');
        }
    }
    header('Location:' . urlencode($redirect) . '?last_message=' . urlencode(msg('undeletepage_file_undeleted')));
}

draw_footer();

/*
 * Permanently Delete A File
 * @param integer $id The file ID to be deleted permanently
 */
function pmt_delete($id)
{
    global $pdo;

    $userperm_obj = new User_Perms($_SESSION['uid'], $pdo);
    
    if (!$userperm_obj->user_obj->isRoot()) {
        header('Location: error.php?ec=4');
        exit;
    }
    // all ok, proceed!
    if (isset($id)) {
        if (strchr($id, '_')) {
            header('Location:error.php?ec=20');
        }
        if ($userperm_obj->canAdmin($id)) {
            // delete from db
            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':id' => $id));

            // delete from db
            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_perms WHERE fid = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':id' => $id));

            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':id' => $id));

            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':id' => $id));

            $filename = $id . ".dat";
            unlink($GLOBALS['CONFIG']['archiveDir'] . $filename);
            if (is_dir($GLOBALS['CONFIG']['revisionDir'] . $id . '/')) {
                $dir = opendir($GLOBALS['CONFIG']['revisionDir'] . $id . '/');
                if (is_dir($GLOBALS['CONFIG']['revisionDir'] . $id . '/')) {
                    $dir = opendir($GLOBALS['CONFIG']['revisionDir'] . $id . '/');
                    while ($lreadfile = readdir($dir)) {
                        if (is_file($GLOBALS['CONFIG']['revisionDir'] . "$id/$lreadfile")) {
                            unlink($GLOBALS['CONFIG']['revisionDir'] . "$id/$lreadfile");
                        }
                    }
                    rmdir($GLOBALS['CONFIG']['revisionDir'] . $id);
                }
            }
            return true;
        }
    }
    return false;
}
