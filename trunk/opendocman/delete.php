<?php
// delete.php - delete a file(s0 from the respository and the db

// check sessio
session_start();
if (!session_is_registered('uid'))
{
	header('Location:error.php?ec=1');
	exit;
}
if(!isset($num_checkboxes))
	$num_checkboxes =1;
include('config.php');
$userperm_obj = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
// all ok, proceed!
//mysql_free_result($result);
if(!isset($_REQUEST['num_checkboxes']))
	$lnum_checkboxes = 1;
else
	$lnum_checkboxes = $_REQUEST['num_checkboxes'];
for($i = 0; $i< $lnum_checkboxes; $i++)
	if($_REQUEST['id' . $i])
	{
		$id = $_REQUEST['id' . $i];
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
			unlink($GLOBALS['CONFIG']['dataDir'] . $filename);
		}
	}
	// delete from directory
	// clean up and back to main page
	$last_message = urlencode('Document successfully deleted');
	header('Location: out.php?last_message=' . $last_message);
?>
