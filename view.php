<?php
/*
view.php - performs download without updating database
Copyright (C) 2002, 2003, 2004  Stephen Lawrence Jr., Khoa Nguyen
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

// Deprecated

// check for session and $id
session_start();

include_once('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($id) || $id == '') {
    header('Location:error.php?ec=2');
    exit;
}

// includes
// in case file is accessed directly
// verify again that user has view rights

$filedata = new FileData($id, $pdo);
$filedata->setId($id);

if ($filedata->getError() != '') {
    header('Location:error.php?ec=2');
    ob_end_flush();        // Flush buffer onto screens
    ob_end_clean();        // Clean up buffer
    exit;
} else {
    // all checks completed

    /* to avoid problems with some browsers,
       download script should not include parameters on the URL
       so let's use a form and pass the parameters via POST
    */

    // form not yet submitted
    // display information on how to initiate download
    if (!isset($submit)) {
        draw_header('View File', $last_message);

        $GLOBALS['smarty']->assign('file_id', $filedata->getId());
        display_smarty_template('view.tpl');
        
        draw_footer();
    }
    // form submitted - begin download
    else {
        $id = $filedata->getId();
        $realname = $filedata->getName();

        // get the filename
        $filename = $GLOBALS['CONFIG']['dataDir'] . $_POST['id'] . '.dat';

        if (file_exists($filename)) {
            // send headers to browser to initiate file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.rawurlencode($realname));
            readfile($filename);
            
            // Call the plugin API
            callPluginMethod('onViewFile');
        } else {
            echo 'File not readable...';
        }
    }
}
