<?php
/*
file_ops.php - admin file operations
Copyright (C) 2002-2004 Stephen Lawrence Jr, Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.

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

include('config.php');
session_start();
//$_SESSION['uid'] = 102;
//$_GET['submit'] = 'view_checkedout';
//echo $_POST['submit'];
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
	exit;
}

// get a list of documents the user has "view" permission for
// get current user's information-->department
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
if(!$user_obj->isRoot())
{
	header('Location:error.php?ec=24');
}
$flag = 0;
if(!isset($_GET['starting_index']))
{
	    $_GET['starting_index'] = 0;
}

if(!isset($_GET['stoping_index']))
{
	    $_GET['stoping_index'] = $_GET['starting_index']+$GLOBALS['CONFIG']['page_limit'];
}

if(!isset($_GET['sort_by']))
{
	    $_GET['sort_by'] = 'id';
}

if(!isset($_GET['sort_order']))
{
	    $_GET['sort_order'] = 'asc';
}
if(!isset($_GET['page']))
{
	    $_GET['page'] = 0;
}
if(@$_GET['submit'] == 'view_checkedout')
{
	echo "\n" . '<form name="table" action="' . $_SERVER['PHP_SELF'] . '" method="POST">'; 
	echo "\n" . '<input name="submit" type="hidden" value="Clear Status">';
	draw_header(msg('label_checked_out_files'));
	draw_menu($_SESSION['uid']);
	draw_status_bar(msg('label_checked_out_files'), @$_REQUEST['last_message']);
	$lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status>0";
	$lresult = mysql_query($lquery) or die("Error in querying: $lquery" . mysql_error());
	$llen = mysql_num_rows($lresult);
	$array_id = array();
	for($i=0; $i<$llen; $i++)
        {
            list($array_id[$i]) = mysql_fetch_row($lresult);
        }
	$sorted_id_array = my_sort($array_id, $_GET['sort_order'], $_GET['sort_by']);
	$lpage_url = $_SERVER['PHP_SELF'] . '?';
	$userpermission = new UserPermission($_SESSION['uid'], $connection, $database);
	$list_status = list_files($sorted_id_array, $userpermission, $lpage_url, $GLOBALS['CONFIG']['dataDir'], $_GET['sort_order'], $_GET['sort_by'], $_GET['starting_index'], $_GET['stoping_index'], true);
	if($list_status != -1 )
	{
		echo "\n" . '<BR><center><div class="buttons"><button class="positive" type="submit" name="submit" value="Clear Status">' . msg('button_clear_status') . '</button></div></center><br />';
		echo "\n" . '</form>';
	}
	list_nav_generator(sizeof($sorted_id_array), $GLOBALS['CONFIG']['page_limit'], $GLOBALS['CONFIG']['num_page_limit'], $lpage_url, $_GET['page'], $_GET['sort_by'], $_GET['sort_order']);
	draw_footer();
}
elseif (@$_POST['submit'] == 'Clear Status')
{
	$lquery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data set status=0 where id=";
	for($i=0; $i<$_POST['num_checkboxes']; $i++)
	{
		if(@$_POST['checkbox'.$i])
		{
			mysql_query($lquery . $_POST['checkbox'.$i]) or die('Error in querying' . mysql_error());
		}
	}
	header('Location:' . $_SERVER['PHP_SELF'] . '?state=2&submit=view_checkedout');
}
