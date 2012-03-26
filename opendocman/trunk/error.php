<?php
/*
error.php - displays error messages based on error code $ec
Copyright (C) 2002-2004  Stephen Lawrence, Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
 
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

// includes
include('odm-load.php');
session_start();

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('error'), $last_message);

if(isset($_REQUEST['ec']))
{
    switch ($_REQUEST['ec'])
    {
        // login failure
        case 0:
            $message = msg('message_there_was_an_error_loggin_you_in') . ' <a href="'.$GLOBALS['CONFIG']['base_url'].'">' .msg('login') . '</a>';
            break;

        // session problem
        case 1:
            $message = msg('message_session_error') . '<a href='.$GLOBALS['CONFIG']['base_url'].'>' . msg('login') . '</a>';
            break;

        // malformed variable/failed query
        case 2:
            $message = msg('message_error_performing_action');
            break;

        // User already exists
        case 3:
            $message = msg('message_record_exists');
            break;

        // User not admin
        case 4:
            $message = msg('message_you_are_not_administrator');
            break;

        // Category exists
        case 5:
            $message = msg('message_record_exists').':'.$_REQUEST['category'].' <a href=out.php>Back</a>';
            break;

        // Input Field Blank
        case 6:
            $message = msg('message_you_did_not_enter_value') .' <a href=out.php>Back</a>';
            break;


        // file not uploaded
        case 11:
            $message = msg('message_please_upload_valid_doc');
            break;

        // rights not assigned
        case 12:
            $message = msg('message_you_must_assign_rights');
            break;

        // illegal file type
        case 13:
            $last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '' );
            $message =  msg('message_that_filetype_not_supported') . ' Admin->Settings->allowedFileTypes:<br><br />Current allowed filetypes:<ul align=left>';
            //echo "_File array is " . array_values($_FILES['file']);
            foreach($GLOBALS['CONFIG']['allowedFileTypes'] as $thistype)
            {
                $message .= '<li>'.$thistype;
            }
            $message .= '</ul>';
            break;
        //non-unique account
        case 14:
            $message = msg('message_non_unique_account');
            break;
        //check-in wrong filename
        case 15:
            $message = msg('message_wrong_file_checkin');
            break;
        //non unique id in filename
        case 16:
            $message = msg('message_non_unique_key');
            break;
        // file cannot be checked-in
        case 17:
            $message = msg('message_this_file_cannot_be_checked_in');
            break;
        //non-complete upload
        case 18:
            $message = msg('message_this_file_cannot_be_uploaded');
            break;
        //no account in ODM
        case 19:
            $message = msg('message_you_do_not_have_an_account') . ' <a href="mailto:' . $GLOBALS['CONFIG']['site_mail'] . '"> ' . $GLOBALS['CONFIG']['site_mail'] . '</a>';
            break;
        // cannot do this on revision
        case 20:
            $message = msg('message_this_operation_cannot_be_done_rev');
            break;
        // operation cannot be done on file
        case 21:
            $message = msg('message_this_operation_cannot_be_done_file');
            break;
        // bad root_id setting
        case 22:
            $message = msg('message_unable_to_determine_root');
            break;
        // Folder not writable
        case 23:
            $message = msg('message_folder_error_check');
            break;
        // Non root user trying to access root operations
        case 24:
            $message =msg('message_this_page_requires_root');
            break;
        // File too big
        case 25:
            $message =msg('message_the_file_is_too_large') .' ' . $GLOBALS['CONFIG']['max_filesize'];
            break;
        case 26:
            $message =msg('message_the_file_is_too_large_php_ini') .' ' . min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
            break;
        //default
        default:
            $message = msg('message_there_was_an_error_performing_the_action') .' ' . msg('please') . ' <a href='.$GLOBALS['CONFIG']['base_url'].'>' . msg('login') . '</a>';
            break;
    }
    draw_error($message);
}
draw_footer();
