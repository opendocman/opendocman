<?php
/*
view_file.php - draws screen which allows users to view files inline
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
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

session_cache_limiter('private');
session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

require_once("AccessLog_class.php");

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$request_id = $_REQUEST['id']; //save an original copy of id
if (strchr($_REQUEST['id'], '_')) {
    list($_REQUEST['id'], $revision_id) = explode('_', $_REQUEST['id']);
    $revision_dir = $GLOBALS['CONFIG']['revisionDir'] . '/'. $_REQUEST['id'] . '/';
}

if (!isset($_GET['submit'])) {
    draw_header(msg('view') . ' ' . msg('file'), $last_message);
    $file_obj = new FileData($_REQUEST['id'], $pdo);
    $file_name = $file_obj->getName();
    $file_id = $file_obj->getId();
    $realname = $file_obj->getName();

    // Get the suffix of the file so we can look it up
    // in the $mimetypes array
    $suffix = '';
    if (strchr($realname, '.')) {
        // Fix by blackwes
        $prefix = (substr($realname, 0, (strrpos($realname, "."))));
        $suffix = strtolower((substr($realname, ((strrpos($realname, ".")+1)))));
    }
    
    $mimetype = File::mime_by_ext($suffix);

    $GLOBALS['smarty']->assign('mimetype', $mimetype);
    $GLOBALS['smarty']->assign('file_id', $file_id);

    // drw form
    display_smarty_template('view_file.tpl');
    draw_footer();
} elseif ($_GET['submit'] == 'view') {
    $file_obj = new FileData($_REQUEST['id'], $pdo);
    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($_REQUEST['id'], $file_obj->READ_RIGHT, $file_obj);
    $realname = $file_obj->getName();

    if (isset($revision_id)) {
        $filename = $revision_dir . $request_id . ".dat";
    } elseif ($file_obj->isArchived()) {
        $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";
    } else {
        $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";
    }

    if (file_exists($filename)) {
        // send headers to browser to initiate file download
        header('Content-Length: '.filesize($filename));
        // Pass the mimetype so the browser can open it
        header('Cache-control: private');
        header('Content-Type: ' . $_GET['mimetype']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($realname) . '"');
        // Apache is sending Last Modified header, so we'll do it, too
        $modified=filemtime($filename);
        header('Last-Modified: '. date('D, j M Y G:i:s T', $modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
        readfile($filename);
        AccessLog::addLogEntry($_REQUEST['id'], 'V', $pdo);
    } else {
        echo msg('message_file_does_not_exist');
    }
} elseif ($_GET['submit'] == 'Download') {
    $file_obj = new FileData($_REQUEST['id'], $pdo);
    
    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($_REQUEST['id'], $file_obj->READ_RIGHT, $file_obj);
    
    $realname = $file_obj->getName();
    
    if (isset($revision_id)) {
        $filename = $revision_dir . $request_id . ".dat";
    } elseif ($file_obj->isArchived()) {
        $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";
    } else {
        $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";
    }

    if (file_exists($filename)) {
        // send headers to browser to initiate file download
        header('Cache-control: private');
        header('Content-Type: '.$_GET['mimetype']);
        header('Content-Disposition: attachment; filename="' . $realname . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($filename);
        AccessLog::addLogEntry($_REQUEST['id'], 'D', $pdo);
    } else {
        echo msg('message_file_does_not_exist');
    }
} else {
    echo msg('message_nothing_to_do');
    echo 'submit is ' . $_GET['submit'];
}
