<?php
// out.php - display a list/ of all available documents that user has permission to view (with file status)
// check to ensure valid session, else redirect
//$_SESSION['uid']=140;$sort_by = 'author';
session_start();

if (!isset($_SESSION['uid']))
{
        header('Location:error.php?ec=1');
        exit;
}


if (!isset($_REQUEST['last_message']))
{
    $_REQUEST['last_message']='';
}

// includes

include ('config.php');
draw_header('File Listing');
draw_menu($_SESSION['uid']);
draw_status_bar('Document Listing', $_REQUEST['last_message']);
sort_browser(); 
$query = "SELECT * FROM dept_reviewer WHERE dept_reviewer.user_id = $_SESSION[uid]";
$result = mysql_query($query) or die ("Error in Query:$query".mysql_error());
$count = mysql_num_rows($result);
$department_id = array();
$index = 0;
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);

if($user_obj->isReviewer() && sizeof($user_obj->getReviewee()) > 0)
{
        echo "<a href='toBePublished.php'> You have ". sizeof($user_obj->getReviewee()). " documents waiting to be reviewed!</a>  <BR>";
}

$rejected_files_obj = $user_obj->getRejectedFiles();
if(isset($rejected_files_obj[0]) && $rejected_files_obj[0] != null)
{
        echo "<a href='rejects.php'> ". sizeof($rejected_files_obj)." of your documents were rejected!</a> <BR>";
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
	$_GET['sort_order'] = 'a-z';
}

if(!isset($_GET['page']))
{
	$_GET['page'] = 0;
}

//set values
$page_url = $_SERVER['PHP_SELF'] . '?submit=true';
list($user_department)=mysql_fetch_row($result);
$user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
$file_obj_array = $user_perms->getAllowedFileOBJs();
$count = sizeof($file_obj_array);

$sorted_obj_array = obj_array_sort_interface($file_obj_array, $_GET['sort_order'], $_GET['sort_by']);
list_files($sorted_obj_array, $user_perms, $page_url,  $GLOBALS['CONFIG']['dataDir'], $_GET['sort_order'], $_GET['sort_by'], $_GET['starting_index'], $_GET['stoping_index'], 'false','false');
// clean up
	
	echo '</table>';
	echo '<br>';
	$limit=$GLOBALS['CONFIG']['page_limit'];
	$total_hit = sizeof($sorted_obj_array);
	list_nav_generator($total_hit, $limit,$page_url, $_GET['page'], $_GET['sort_by'], $_GET['sort_order']);	
	echo '</center>';
	draw_footer();	
?>
