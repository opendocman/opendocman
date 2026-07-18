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

// (C) 2002-2004  Stephen Lawrence Jr., Khoa Nguyen
// Delete a file from the respository and the db

use Aura\Html\Escaper as e;


// check session
session_start();
if (!isset($_SESSION['uid'])) {
    header('Location: error?ec=1');
    exit;
}

$pdo = $GLOBALS['pdo'];

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$redirect = 'out';

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
            header('Location: error?ec=23&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    for ($i = 0; $i<$_REQUEST['num_checkboxes']; $i++) {
        if (isset($_REQUEST['id' . $i])) {
            $id = $_REQUEST['id' . $i];
            if (strchr($id, '_')) {
                header('Location: error?ec=20');
            }
            if ($userperm_obj->canAdmin($id)) {
                $file_obj = new FileData($id, $pdo);
                $file_obj->temp_delete();
                $realname = $file_obj->getName();
                $srcPath = getFilePath($id, $realname, 'data');
                $dstPath = getFilePath($id, $realname, 'archive');
                $dstDir = dirname($dstPath);
                if (!is_dir($dstDir)) {
                    mkdir($dstDir, 0775, true);
                }
                fmove($srcPath, $dstPath);
            }
            AccessLog::addLogEntry($_REQUEST['id' . $i], 'X', $pdo);
        }
    }
    // delete from directory
    // clean up and back to main page
    $last_message = msg('message_document_has_been_archived');
        
    // Call the plugin API call for this section
    callPluginMethod('onAfterArchiveFile');
    
    header('Location: out?last_message=' . urlencode($last_message));
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
    display_smarty_template('out.tpl');

    if ($list_status != -1) {
        $GLOBALS['smarty']->assign('lmode', '');
        display_smarty_template('deleteview.tpl');
    }
} elseif (isset($_POST['submit']) && $_POST['submit']=='Delete file(s)') {
    isset($_REQUEST['checkbox']) ? $_REQUEST['checkbox'] : '';

    foreach ($_REQUEST['checkbox'] as $value) {
        if (!pmt_delete($value)) {
            header('Location: error?ec=21');
            exit;
        }
    }
    header('Location: ' . urlencode($redirect) . '?last_message=' . urlencode(msg('undeletepage_file_permanently_deleted')));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Undelete') {
    if (isset($_REQUEST['checkbox'])) {
        foreach ($_REQUEST['checkbox'] as $fileId) {
            $file_obj = new FileData($fileId, $pdo);
            $file_obj->undelete();
            $realname = $file_obj->getName();
            $srcPath = getFilePath($fileId, $realname, 'archive');
            $dstPath = getFilePath($fileId, $realname, 'data');
            $dstDir = dirname($dstPath);
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0775, true);
            }
            fmove($srcPath, $dstPath);
        }
    }
    header('Location: ' . urlencode($redirect) . '?last_message=' . urlencode(msg('undeletepage_file_undeleted')));
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
        header('Location: error?ec=4');
        exit;
    }
    // all ok, proceed!
    if (isset($id)) {
        if (strchr($id, '_')) {
            header('Location: error?ec=20');
        }
        if ($userperm_obj->canAdmin($id)) {
            // Get file info BEFORE deleting DB record
            $file_obj = new FileData($id, $pdo);
            $realname = $file_obj->getName();
            $archivePath = getFilePath($id, $realname, 'archive');
            $dataPath = getFilePath($id, $realname, 'data');

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

            // Delete archive file
            if (file_exists($archivePath)) {
                unlink($archivePath);
            }
            // Delete data file if present (e.g. unarchived edge case)
            if (file_exists($dataPath)) {
                unlink($dataPath);
            }
            // Delete revision files using getFilePath for proper naming
            $revisionDir = $GLOBALS['CONFIG']['revisionDir'] . $id . '/';
            if (is_dir($revisionDir)) {
                $dir = opendir($revisionDir);
                if ($dir) {
                    while (($file = readdir($dir)) !== false) {
                        $fullPath = $revisionDir . $file;
                        if (is_file($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                    closedir($dir);
                }
                rmdir($revisionDir);
            }
            return true;
        }
    }
    return false;
}
