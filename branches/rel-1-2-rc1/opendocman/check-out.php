<?php
// check-out.php - performs checkout and updates database
// check for session and $_REQUEST['id']
session_start();
if (!session_is_registered('uid'))
{
	header('Location:index.php?redirection=' . $_SERVER['REQUEST_URI']);
	exit;
}
if(strchr($_REQUEST['id'], '_') )
{
	    header('Location:error.php?ec=20');
}
if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '')
{
	header('Location:error.php?ec=2');
	exit;
}
include('config.php');
/* if the user has read-only authority on the file, his check out 
will be the same as the person with admin or modify right except that the DB will not have any recored of him checking out this file.  Therefore, he will not be able to check-in the file on
the server
*/
$fileobj = new FileData($_GET['id'], $GLOBALS['connection'], $GLOBALS['database']);
$fileobj->setId($_GET['id']);
if ($fileobj->getError() != NULL || $fileobj->getStatus() > 0  || $fileobj->isArchived())
{
	header('Location:error.php?ec=2');
	exit;
}
if (!isset($_GET['submit']))
{
	draw_header('Checkout');
	draw_menu($_SESSION['uid']);
	draw_status_bar('Check Out Document');
	// form not yet submitted
	// display information on how to initiate download
?>
	
	
	<p>
	
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
	<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
	<input type="hidden" name="access_right" value="<?php echo $_GET['access_right'];?>">
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
	if($_GET['access_right'] == 'modify')
	{	
		// since this user has checked it out and will modify it
		// update db to reflect new status
		$query = "UPDATE data SET status = '$_SESSION[uid]' WHERE id = '$_GET[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	}
	// calculate filename
	$filename = $GLOBALS['CONFIG']['dataDir'] . $_GET['id'] . '.dat';
		
	// send headers to browser to initiate file download
	header ('Content-Type: application/octet-stream'); 
	header ('Content-Disposition: attachment; filename=' . $realname); 
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	readfile($filename); 
	}
// clean up
?>
