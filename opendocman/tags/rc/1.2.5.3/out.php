<?php
/*
out.php - display a list/ of all available documents that user has permission to view (with file status)
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

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

// check to ensure valid session, else redirect
session_start();
//$_SESSION['uid']=102; $sort_by = 'author';
//$start_time = time();
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
	exit;
}

// includes
$GLOBALS['state'] = 1;
require_once 'config.php';
if($GLOBALS['CONFIG']['treeview'] == "On"){
   require_once 'treeview.php';
}

if (isset($_REQUEST['last_message'])) {
    $last_message = sanitize_system_string($_REQUEST['last_message']);
}

draw_header('File Listing');
draw_menu($_SESSION['uid']);
draw_status_bar('Document Listing', @$_REQUEST['last_message']);
if($GLOBALS['CONFIG']['treeview'] != "On"){
   sort_browser();
}
$secureurl_obj = new phpsecureurl;
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);

if($user_obj->isReviewer() && sizeof($user_obj->getRevieweeIds()) > 0)
{
    echo '<img src="images/exclamation.gif"> <a href="' . $secureurl_obj->encode('toBePublished.php?state=1') . '">You have '. sizeof($user_obj->getRevieweeIds()). ' document';
    if (sizeof($user_obj->getRevieweeIds()) != 1)
    {
        echo 's';
    }
    echo ' waiting to be reviewed!</a>  <BR>';
}
$rejected_files_obj = $user_obj->getRejectedFileIds();
if(isset($rejected_files_obj[0]) && $rejected_files_obj[0] != null)
{
    echo '<img src="images/exclamation_red.gif"> <a href="' . $secureurl_obj->encode('rejects.php?state=1') . '">'. sizeof($rejected_files_obj) . ' of your documents were rejected!</a> <BR>';
}
$llen = $user_obj->getNumExpiredFiles();
if($llen > 0)
{
	echo '<img src="images/exclamation_red.gif"><a href="javascript:window.location=\'search.php?submit=submit&sort_by=id&where=author_locked_files&sort_order=asc&keyword=-1&exact_phrase=on\'"> '. $llen . ' of your document(s) expired!</a> <BR>';
}
// get a list of documents the user has "view" permission for
// get current user's information-->department
if(!isset($_GET['starting_index']))
{
	$_GET['starting_index'] = 0;
}

if(!isset($_GET['stoping_index']))
{
	$limit=$GLOBALS['CONFIG']['page_limit'];
	$_GET['stoping_index'] = ($_GET['starting_index']+$limit-1);
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
//set values
$page_url = $_SERVER['PHP_SELF'] . '?submit=true';
$user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
//$start_P = getmicrotime();
$file_id_array = $user_perms->getViewableFileIds();
//$end_P = getmicrotime();

$count = sizeof($file_id_array);
//$lsort_b = getmicrotime();
$sorted_id_array = my_sort($file_id_array, $_GET['sort_order'], $_GET['sort_by']);
//$lsort_e = getmicrotime();
//$sorted_obj_array = $user_perms->convertToFileDataOBJ($sorted_id_array);
//$llist_b = getmicrotime();

// Patch by jonathanwminer
if($GLOBALS['CONFIG']['treeview'] == "On")
{
   $_GET['starting_index'] = 0;
   $_GET['stoping_index'] = sizeof($sorted_id_array);
}

if($GLOBALS['CONFIG']['treeview'] != 'On')
{
    echo '<table border="0">';
    echo '<tr><td>';
    list_files($sorted_id_array, $user_perms, $page_url,  $GLOBALS['CONFIG']['dataDir'], $_GET['sort_order'], $_GET['sort_by'], $_GET['starting_index'], $_GET['stoping_index'], 'false','false');
    $limit=$GLOBALS['CONFIG']['page_limit'];
    $total_hit = sizeof($file_id_array);
    list_nav_generator($total_hit, $limit, $GLOBALS['CONFIG']['num_page_limit'], $page_url, $_GET['page'], $_GET['sort_by'], $_GET['sort_order']);	

    //$llist_e = getmicrotime();
    // clean up
    echo '</td></tr></table>';

}

if($GLOBALS['CONFIG']['treeview'] == 'On')
{
    if(is_array($sorted_id_array) && $sorted_id_array[0] != '')
    {
        show_tree($sorted_id_array, $_GET['starting_index'], $_GET['stoping_index']);
    }
}
draw_footer();	
//echo '<br> <b> Load Page Time: ' . (getmicrotime() - $start_time) . ' </b>';
//echo '<br> <b> Load Permission Time: ' . ($end_P - $start_P) . ' </b>';	
//echo '<br> <b> Load Sort Time: ' . ($lsort_e - $lsort_b) . ' </b>';	
//echo '<br> <b> Load Table Time: ' . ($llist_e - $llist_b) . ' </b>';	
?>
