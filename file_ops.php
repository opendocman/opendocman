<?php
/*
file_ops.php - admin file operations
Copyright (C) 2002-2004 Stephen Lawrence Jr, Khoa Nguyen
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

include('odm-load.php');
session_start();
//$_SESSION['uid'] = 102;
//$_GET['submit'] = 'view_checkedout';
//echo $_POST['submit'];

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
	exit;
}

// get a list of documents the user has "view" permission for
// get current user's information-->department
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
if(!$user_obj->isRoot())
{
	header('Location:error.php?ec=24');
}
$flag = 0;
if(isset($_GET['submit']) && $_GET['submit'] == 'view_checkedout')
{
	echo "\n" . '<form name="table" action="' . $_SERVER['PHP_SELF'] . '" method="POST">'; 
	echo "\n" . '<input name="submit" type="hidden" value="Clear Status">';
	draw_header(msg('label_checked_out_files'), $last_message);
        
        $fileid_array = $user_obj->getCheckedOutFiles();

	$lpage_url = $_SERVER['PHP_SELF'] . '?';
	$userpermission = new UserPermission($_SESSION['uid'], $connection, DB_NAME);
	$list_status = list_files($fileid_array, $userpermission, $GLOBALS['CONFIG']['dataDir'], true, true);
	if($list_status != -1 )
	{
		echo "\n" . '<BR><div class="buttons"><button class="positive" type="submit" name="submit" value="Clear Status">' . msg('button_clear_status') . '</button></div><br />';
		echo "\n" . '</form>';
	}
	draw_footer();
}
elseif (isset($_POST['submit']) && $_POST['submit'] == 'Clear Status')
{
    if(isset($_POST["checkbox"]))
    {
        foreach($_POST['checkbox'] as $cbox)
        {
            $fileid = $cbox;
            $file_obj = new FileData($fileid, $GLOBALS['connection'], DB_NAME);
            //$user_obj = new User($file_obj->getOwner(), $connection, DB_NAME);
            //$mail_to = $user_obj->getEmailAddress();
            //mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
            $file_obj->setStatus(0);
        }

    }
    header('Location:' . $_SERVER['PHP_SELF'] . '?state=2&submit=view_checkedout');
}
else
{
    echo 'Nothing to do';
}
