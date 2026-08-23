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
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
    // Validate CSRF on POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
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
                if ($srcPath && file_exists($srcPath)) {
                    $dstPath = getFilePath($id, $realname, 'archive');
                    $dstDir = dirname($dstPath);
                    if (!is_dir($dstDir)) {
                        mkdir($dstDir, 0775, true);
                    }
                    fmove($srcPath, $dstPath);
                }
            }
            AccessLog::addLogEntry($_REQUEST['id' . $i], 'X', $pdo);
            // Clean up incoming file if one exists
            $incomingPath = getFilePath($id, $realname, 'incoming');
            if (file_exists($incomingPath)) {
                unlink($incomingPath);
                $incomingDir = dirname($incomingPath);
                if (is_dir($incomingDir) && count(scandir($incomingDir)) <= 2) {
                    rmdir($incomingDir);
                }
            }
        }
    }
    // delete from directory
    // clean up and back to main page
    $last_message = msg('message_document_has_been_archived');
        
    // Call the plugin API call for this section
    callPluginMethod('onAfterArchiveFile');
    
    header('Location: out?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'view_del_archive') {
    
    draw_header(msg('area_deleted_files'), $last_message);

    $GLOBALS['smarty']->assign('active_admin', 'delete');
    $GLOBALS['smarty']->assign('state', 2);
    $delete_csrf = $GLOBALS['csrf']->getTokenForTemplate('/delete');
    $GLOBALS['smarty']->assign('delete_csrf_field', $delete_csrf['field']);
    ob_start();
    display_smarty_template('out.tpl');
    $GLOBALS['smarty']->assign('content', ob_get_clean());
    display_smarty_template('_admin_content.tpl');
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'undelete') {
    if (!isset($_REQUEST['num_checkboxes'])) {
        $_REQUEST['num_checkboxes'] = 1;
    }
    for ($i = 0; $i < $_REQUEST['num_checkboxes']; $i++) {
        if (isset($_REQUEST['id' . $i])) {
            $fileId = $_REQUEST['id' . $i];
            $file_obj = new FileData($fileId, $pdo);
            $file_obj->undelete();
            $realname = $file_obj->getName();
            $srcPath = getFilePath($fileId, $realname, 'archive');
            $dstPath = getFilePath($fileId, $realname, 'data');
            $dstDir = dirname($dstPath);
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0775, true);
            }
            if (file_exists($srcPath)) {
                fmove($srcPath, $dstPath);
            }
        }
    }
    header('Location: delete?mode=view_del_archive&last_message=' . urlencode(msg('undeletepage_file_undeleted')));
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'delete_permanent') {
    if (!isset($_REQUEST['num_checkboxes'])) {
        $_REQUEST['num_checkboxes'] = 1;
    }
    for ($i = 0; $i < $_REQUEST['num_checkboxes']; $i++) {
        if (isset($_REQUEST['id' . $i])) {
            pmt_delete($_REQUEST['id' . $i]);
        }
    }
    header('Location: delete?mode=view_del_archive&last_message=' . urlencode(msg('undeletepage_file_permanently_deleted')));
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
            if (file_exists($srcPath)) {
                fmove($srcPath, $dstPath);
            }
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

            // Delete content index entry
            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}content_index WHERE file_id = :id";
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
            // Delete incoming file if present
            $incomingPath = getFilePath($id, $realname, 'incoming');
            if (file_exists($incomingPath)) {
                unlink($incomingPath);
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

            // Clean up empty parent directories
            $dataDir = dirname($dataPath);
            if (is_dir($dataDir) && count(scandir($dataDir)) <= 2) {
                rmdir($dataDir);
            }
            $archiveDir = dirname($archivePath);
            if (is_dir($archiveDir) && count(scandir($archiveDir)) <= 2) {
                rmdir($archiveDir);
            }
            $incomingDir = dirname($incomingPath);
            if (is_dir($incomingDir) && count(scandir($incomingDir)) <= 2) {
                rmdir($incomingDir);
            }
            return true;
        }
    }
    return false;
}
