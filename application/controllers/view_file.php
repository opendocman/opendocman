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

// (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
// Draws screen which allows users to view files inline

session_cache_limiter('private');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$full_request_id = $_REQUEST['id']; //save an original copy of id (may include revision suffix)
if (strchr($_REQUEST['id'], '_')) {
    list($_REQUEST['id'], $revision_id) = explode('_', $_REQUEST['id']);
    $revision_id = is_numeric($revision_id) ? (int) $revision_id : null;
}
$request_id = (int) $_REQUEST['id'];

if (!isset($_REQUEST['submit'])) {
    draw_header(msg('view') . ' ' . msg('file'), $last_message);
    $file_obj = new FileData($request_id, $pdo);
    $file_name = $file_obj->getName();
    $file_id = $file_obj->getId();
    $realname = $file_obj->getName();

    // Get the suffix of the file so we can look it up
    $suffix = '';
    if (strchr($realname, '.')) {
        // Fix by blackwes
        $prefix = (substr($realname, 0, (strrpos($realname, "."))));
        $suffix = strtolower((substr($realname, ((strrpos($realname, ".")+1)))));
    }

    // If we have a revision ID lets use the original
    // request id that included the file id and revision number (ex. 1_0)
    if (isset($revision_id)) {
        $file_id = $full_request_id;
    }

    $mimetype = File::mime_by_ext($suffix);

    $GLOBALS['smarty']->assign('mimetype', $mimetype);
    $GLOBALS['smarty']->assign('file_id', $file_id);

    // drw form
    display_smarty_template('view_file.tpl');
    draw_footer();
} elseif ($_REQUEST['submit'] == 'view') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    $file_obj = new FileData($request_id, $pdo);
    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($request_id, $file_obj->READ_RIGHT, $file_obj);
    $realname = $file_obj->getName();

    if (isset($revision_id)) {
        $filename = getFilePath($request_id, $realname, 'revision', $revision_id);
    } elseif ($file_obj->isArchived()) {
        $filename = getFilePath($request_id, $realname, 'archive');
    } elseif ($file_obj->isPublishable() === 0 || $file_obj->isPublishable() === -1) {
        $incomingPath = getFilePath($request_id, $realname, 'incoming');
        if (file_exists($incomingPath)) {
            $filename = $incomingPath;
        } else {
            $filename = getFilePath($request_id, $realname, 'data');
        }
    } else {
        $filename = getFilePath($request_id, $realname, 'data');
    }

    if (file_exists($filename)) {
        // send headers to browser to initiate file download
        header('Content-Length: '.filesize($filename));
        // Pass the mimetype so the browser can open it
        header('Cache-control: private');
        header('Content-Type: ' . $_REQUEST['mimetype']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($realname) . '"');
        // Apache is sending Last Modified header, so we'll do it, too
        $modified=filemtime($filename);
        header('Last-Modified: '. date('D, j M Y G:i:s T', $modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
        readfile($filename);
        AccessLog::addLogEntry($request_id, 'V', $pdo);
    } else {
        echo msg('message_file_does_not_exist');
    }
} elseif ($_REQUEST['submit'] == 'Download') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    $file_obj = new FileData($request_id, $pdo);

    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($request_id, $file_obj->READ_RIGHT, $file_obj);

    $realname = $file_obj->getName();

    if (isset($revision_id)) {
        $filename = getFilePath($request_id, $realname, 'revision', $revision_id);
    } elseif ($file_obj->isArchived()) {
        $filename = getFilePath($request_id, $realname, 'archive');
    } elseif ($file_obj->isPublishable() === 0 || $file_obj->isPublishable() === -1) {
        $incomingPath = getFilePath($request_id, $realname, 'incoming');
        if (file_exists($incomingPath)) {
            $filename = $incomingPath;
        } else {
            $filename = getFilePath($request_id, $realname, 'data');
        }
    } else {
        $filename = getFilePath($request_id, $realname, 'data');
    }

    if (file_exists($filename)) {
        // send headers to browser to initiate file download
        header('Cache-control: private');
        header('Content-Type: '.$_REQUEST['mimetype']);
        header('Content-Disposition: attachment; filename="' . $realname . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($filename);
        AccessLog::addLogEntry($request_id, 'D', $pdo);
    } else {
        echo msg('message_file_does_not_exist');
    }
} else {
    echo msg('message_nothing_to_do');
}
