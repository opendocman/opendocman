<?php
// check-out.php - performs checkout and updates database
// check for session and $id
session_start();
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}
if (!isset($id) || $id == '')
{
	header('Location:error.php?ec=2');
	exit;
}
include('config.php');
/* if the user has read-only authority on the file, his check out 
will be the same as the person with admin or modify right except that the DB will not have any recored of him checking out this file.  Therefore, he will not be able to check-in the file on
the server
*/
$fileobj = new FileData($id, $GLOBALS['connection'], $GLOBALS['database']);
$fileobj->setId($id);
if ($fileobj->getError() != NULL || $fileobj->getStatus() != 0 )
{
	header('Location:error.php?ec=2');
	exit;
}
if (!isset($submit))
{
	draw_header('Checkout');
	draw_menu($SESSION_UID);
	draw_status_bar('Check Out Document', $lastmessage);
	// form not yet submitted
	// display information on how to initiate download
?>
	
	
	<p>
	
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
	<input type="hidden" name="id" value="<?php echo $id; ?>">
	<input type="hidden" name="access_right" value="<?php echo $HTTP_GET_VARS['access_right'];?>">
	<input type="submit" name="submit" value="Click here"> to check out the selected document and begin downloading it to your local workstation.
	</form>
	Once the document has completed downloading, you may <a href="out.php">continue browsing</a> The Vault.
<?php
draw_footer();
}
	// form submitted - download
else
{
	$realname = $fileobj->getName();
	if($access_right == 'modify')
	{	
		// since this user has checked it out and will modify it
		// update db to reflect new status
		$query = "UPDATE data SET status = '$SESSION_UID' WHERE id = '$id'";
		$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	}
	// calculate filename
	$filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
		
	// send headers to browser to initiate file download
	header ('Content-Type: application/octet-stream'); 
	header ('Content-Disposition: attachment; filename=' . $realname); 
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	readfile($filename); 
	}
// clean up
?>
