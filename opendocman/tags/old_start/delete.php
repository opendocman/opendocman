<?php
// delete.php - delete a file(s0 from the respository and the db

// check sessio
session_start();
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}
if(!$num_checkboxes)
	$num_checkboxes =1;
include('config.php');
$userperm_obj = new User_Perms($SESSION_UID, $connection, $database);
// all ok, proceed!
//mysql_free_result($result);
for($i = 0; $i<$num_checkboxes; $i++)
	if($_REQUEST['id' . $i])
	{
		$id = $_REQUEST['id' . $i];
		if($userperm_obj->canAdmin($id))
		{
			// delete from db
			$query = "DELETE FROM data WHERE id = '$id'";
			$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
		
			// delete from db
			$query = "DELETE FROM dept_perms WHERE fid = '$id'";
			$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
		
			$query = "DELETE FROM user_perms WHERE fid = '$id'";
			$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
		
			$query = "DELETE FROM log WHERE id = '$id'";
			$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
			$filename = $id . ".dat";
			unlink($GLOBALS['CONFIG']['dataDir'] . $filename);
		}
	}
	// delete from directory
	// clean up and back to main page
	mysql_close($connection);
	$last_message = urlencode('Document successfully deleted');
	header('Location: out.php?last_message=' . $last_message);
?>
