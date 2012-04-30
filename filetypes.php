<?php
/*
   filetypes.php - Administer allowedFileTypes values
   Copyright (C) 2011 Stephen Lawrence Jr.

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
// check for valid session
session_start();
//print_r($_REQUEST);exit;
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

// includes
include('odm-load.php');

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;
$filetypes = new FileTypes_class();

//If the user is not an admin error out.
if(!$user_obj->isRoot() == true)
{
    header('Location:' . $secureurl->encode('error.php?ec=24'));
    exit;
}

if(isset($_REQUEST['submit']) && $_REQUEST['submit']=='update')
{
    draw_header(msg('label_filetypes'), $last_message);
    $filetypes->edit();
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Save')
{
    draw_header(msg('label_filetypes'), $last_message);

    if($filetypes->save($_POST))
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    }
    else
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);
    $filetypes->edit();
    draw_footer();
}
elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Cancel')
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_action_cancelled'))));
}
elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'AddNew')
{
    draw_header(msg('label_filetypes'), $last_message);
    display_smarty_template('filetype_add.tpl');
    draw_footer();
}
elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'AddNewSave')
{
    if($filetypes->add($_POST))
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    }
    else
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);

    draw_header(msg('label_filetypes'), $last_message);

    $filetypes->edit();
    draw_footer();
}
elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'DeleteSelect')
{
    draw_header(msg('label_filetypes'), $last_message);

    $filetypes->deleteSelect();
    draw_footer();
}
elseif(isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Delete')
{
    if($filetypes->delete($_POST))
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_all_actions_successfull'];
    }
    else
    {
        $_POST['last_message'] = $GLOBALS['lang']['message_error_performing_action'];
    }
    $GLOBALS['smarty']->assign('last_message', $_POST['last_message']);
    draw_header(msg('label_filetypes'), $last_message);
    $filetypes->edit();
    draw_footer();
}
else
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_nothing_to_do'))));
}

