<?php
// delete.php - delete a file(s0 from the respository and the db

// check sessio
session_start();
if (!session_is_registered('uid'))
{
	header('Location:error.php?ec=1');
	exit;
}
include('config.php');
if( !isset($_REQUEST['caller']) )
{	$_REQUEST['caller'] = 'out.php';	}
$userperm_obj = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
if( $_REQUEST['mode'] == 'tmpdel' )
{
	if(!@isset($_REQUEST['num_checkboxes'] ))
		$_REQUEST['num_checkboxes'] =1;
	// all ok, proceed!
	//mysql_free_result($result);
	if( !is_dir($GLOBALS['CONFIG']['archiveDir']) )
	{	mkdir($GLOBALS['CONFIG']['archiveDir']);	}
	for($i = 0; $i<$_REQUEST['num_checkboxes']; $i++)
	{
		if(@$_REQUEST['id' . $i])
		{
			$id = $_REQUEST['id' . $i];
			if(strchr($id, '_') )
			{
				header('Location:error.php?ec=20');
			}
			if($userperm_obj->canAdmin($id))
			{
				$file_obj = new FileData($id, $GLOBALS['connection'], $GLOBALS['database']);
				$file_obj->temp_delete();
				fmove($GLOBALS['CONFIG']['dataDir'] . $id . '.dat', $GLOBALS['CONFIG']['archiveDir'] . $id . '.dat');
			}
		}
	}
	// delete from directory
	// clean up and back to main page
	$last_message = urlencode('Document has been archived');
	header('Location: out.php?last_message=' . $last_message);
}
elseif( $_REQUEST['mode'] == 'pmntdel' )
{
	if( !$userperm_obj->user_obj->isAdmin() )
	{	header('Location: error.php?ec=4');	exit;	}
	if(!@isset($_REQUEST['num_checkboxes'] ))
		$_REQUEST['num_checkboxes'] =1;
	// all ok, proceed!
	//mysql_free_result($result);
	for($i = 0; $i<$_REQUEST['num_checkboxes']; $i++)
	{
		if(@$_REQUEST['id' . $i])
		{
			$id = $_REQUEST['id' . $i];
			if(strchr($id, '_') )
			{
				header('Location:error.php?ec=20');
			}
			if($userperm_obj->canAdmin($id))
			{
				// delete from db
				$query = "DELETE FROM data WHERE id = '$id'";
				$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
			
				// delete from db
				$query = "DELETE FROM dept_perms WHERE fid = '$id'";
				$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
			
				$query = "DELETE FROM user_perms WHERE fid = '$id'";
				$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
			
				$query = "DELETE FROM log WHERE id = '$id'";
				$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
				$filename = $id . ".dat";
				unlink($GLOBALS['CONFIG']['archiveDir'] . $filename);
				if( is_dir($GLOBALS['CONFIG']['revisionDir'] . $id . '/') )
				{
					$dir = opendir($GLOBALS['CONFIG']['revisionDir'] . $id . '/');
					if( is_dir($GLOBALS['CONFIG']['revisionDir'] . $id . '/') )
					{
						$dir = opendir($GLOBALS['CONFIG']['revisionDir'] . $id . '/');
						while($lreadfile = readdir($dir))
						{
							if(is_file($GLOBALS['CONFIG']['revisionDir'] . "$id/$lreadfile"))
							{
								unlink($GLOBALS['CONFIG']['revisionDir'] . "$id/$lreadfile");
							}
						}
						rmdir($GLOBALS['CONFIG']['revisionDir'] . $id);
					}
				}
			}
		}
	}
	// delete from directory
	// clean up and back to main page
	$last_message = urlencode('Document successfully deleted');
	header('Location: delete.php?mode=view_del_archive&last_message=' . $last_message);
}
elseif( $_REQUEST['mode'] == 'view_del_archive' )
{
	//publishable=2 for archive deletion
	$lquery = "SELECT id FROM data WHERE publishable=2";
	$lresult = mysql_query($lquery, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	$array_id = array();
	for($i = 0; $i < mysql_num_rows($lresult); $i++)
	{	list($array_id[$i]) = mysql_fetch_row($lresult);	}
	$luserperm_obj = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
	$lfileobj_array = $luserperm_obj->convertToFileDataOBJ($array_id);
	if(!isset($_POST['starting_index']))
	{
		        $_POST['starting_index'] = 0;
	}

	if(!isset($_POST['stoping_index']))
	{
		        $_POST['stoping_index'] = $_POST['starting_index']+$GLOBALS['CONFIG']['page_limit']-1;
	}

	if(!isset($_POST['sort_by']))
	{
		        $_POST['sort_by'] = 'id';
	}

	if(!isset($_POST['sort_order']))
	{
		        $_POST['sort_order'] = 'a-z';
	}

	if(!isset($_POST['page']))
	{
		        $_POST['page'] = 0;
	}
	draw_menu($_SESSION['uid']);
	draw_header('Rejected Files');
	@draw_status_bar('Rejected Document Listing', $_POST['last_message']);
	$page_url = $_SERVER['PHP_SELF'] . '?';

	$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
	$userperms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
	$sorted_obj_array = obj_array_sort_interface($lfileobj_array, $_POST['sort_order'], $_POST['sort_by']);
	echo '<FORM name="table" method="POST" action="' . $_SERVER['PHP_SELF'] . '" onsubmit="return window.confirm(\'Are you sure?\');">' . "\n";

?>
		<TABLE border="1"><TR><TD>
<?php
		list_files($sorted_obj_array, $userperms, $page_url, $GLOBALS['CONFIG']['archiveDir'], $_POST['sort_order'],  $_POST['sort_by'], $_POST['starting_index'], $_POST['stoping_index'], true);
	list_nav_generator(sizeof($sorted_obj_array), $GLOBALS['CONFIG']['page_limit'], $page_url, $_POST['page'], $_POST['sort_by'], $_POST['sort_order']);
?>
		</TD></TR><TR><TD><CENTER><INPUT type="SUBMIT" name="mode" value="Undelete"><INPUT type="submit"
		name="mode" value="Delete file(s)">
		</TABLE></FORM>

<?php
}
elseif($_POST['mode']=='Delete file(s)')
{
	$url = 'delete.php?mode=pmntdel&';
	$id = 0;
	for($i = 0; $i<$_POST['num_checkboxes']; $i++)
		if(isset($_POST["checkbox$i"]))
		{
			$fileid = $_POST["checkbox$i"];
			$url .= 'id'.$id.'='.$fileid.'&';
			$id ++;
		}
	$url = substr($url, 0, strlen($url)-1);
	header('Location:'.$url.'&num_checkboxes='.$_POST['num_checkboxes']);
}
elseif($_REQUEST['mode'] = 'Undelete')
{
	for($i= 0; $i<$_REQUEST['num_checkboxes']; $i++)
	{
		if(isset($_REQUEST["checkbox$i"]))
		{
			$file_obj = new FileData($_REQUEST["checkbox$i"], $GLOBALS['connection'], $GLOBALS['database']);
			$file_obj->undelete();
			 fmove($GLOBALS['CONFIG']['archiveDir'] . $_REQUEST["checkbox$i"] . '.dat', $GLOBALS['CONFIG']['dataDir'] . $_REQUEST["checkbox$i"] . '.dat');
		}
	}
	header('Location:' . $_REQUEST['caller'] . '?last_message=' . urlencode('Document has been unarchived'));
}

?>
