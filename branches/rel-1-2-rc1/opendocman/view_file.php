<?php
session_cache_limiter('private');
session_start();
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode($_SERVER['REQUEST_URI']));
	exit;
}
include('config.php');
$lrequest_id = $_REQUEST['id']; //save an original copy of id
if(strchr($_REQUEST['id'], '_') )
{
	    list($_REQUEST['id'], $lrevision_id) = split('_' , $_REQUEST['id']);
		$lrevision_dir = $GLOBALS['CONFIG']['revisionDir'] . '/'. $_REQUEST['id'] . '/';
}
if( !isset ($_REQUEST['last_message']) )
{	
        $_REQUEST['last_message']='';	
}
if(!isset($_GET['submit']))
{
	draw_header('View File');
	draw_menu($_SESSION['uid']);
	draw_status_bar('File View',$_REQUEST['last_message']);
	$file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	$file_name = $file_obj->getName();
	$file_id = $file_obj->getId();
	$realname = $file_obj->getName();
	
	// Get the suffix of the file so we can look it up
	// in the $mimetypes array
	$suffix = '';
	if(strchr($realname, '.'))
		list($prefix , $suffix)= split ("\.", $realname);
	if( !isset($GLOBALS['mimetypes']["$suffix"]) )
	{	
                $lmimetype = $GLOBALS['mimetypes']['default'];	
        }
	else 
	{	
                $lmimetype = $GLOBALS['mimetypes']["$suffix"];	
        }
	//echo "Realname is $realname<br>";
	//echo "prefix = $prefix<br>";
	//echo "suffix = $suffix<br>";
	//echo "mime:$lmimetype";	
	echo '<form action="'.$_SERVER['PHP_SELF'].'" name="view_file_form" method="get">';
	echo '<INPUT type="hidden" name="id" value="'.$lrequest_id.'">';
	echo '<BR>';
	// Present a link to allow for inline viewing
	echo 'To view your file in a new window <a class="body" style="text-decoration:none" href="view_file.php?submit=view&id='.urlencode($lrequest_id).'&mimetype='.urlencode("$lmimetype").'">Click Here</a><br><br>';
	echo 'If you are not able to do so for some reason, ';
	echo 'click <input type="submit" name="submit" value="Download"> to download the selected document and begin downloading it to your local workstation for local view.';
	echo '</form>';

    draw_footer();
}
elseif ($_GET['submit'] == 'view')
{
	//echo "mimetype = $mimetype<br>";
	//exit;
	//echo "ID is $_REQUEST['id']";
	$file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	$realname = $file_obj->getName();
	if( isset($lrevision_id) )
	{	$filename = $lrevision_dir . $lrequest_id . ".dat";
	}
	elseif( $file_obj->isArchived() )
	{	$filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";   }
	else
	{	$filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";	}
	// send headers to browser to initiate file download
	header('Content-Length: '.filesize($filename));
	// Pass the mimetype so the browser can open it
        header ('Cache-control: private');
        header('Content-Type: ' . $_GET['mimetype']);
        header('Content-Disposition: inline; filename=' . $realname);
		// Apache is sending Last Modified header, so we'll do it, too
        $modified=filemtime($filename);
        header('Last-Modified: '. date('D, j M Y G:i:s T',$modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
	readfile($filename);
}
elseif ($_GET['submit'] == 'Download')
{
	$file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	$realname = $file_obj->getName();
	if( isset($lrevision_id) )
	{   $filename = $lrevision_dir . $lrequest_id . ".dat";
	}
	elseif( $file_obj->isArchived() )
	{   $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";   }
	else
	{   $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";   }
	// send headers to browser to initiate file download
	header('Cache-control: private');
	header ('Content-Type: application/octet-stream');
	header ('Content-Disposition: attachment; filename=' . $realname);
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	readfile($filename);

}
else
{
echo 'Nothing to do ';
echo 'submit is ' . $_GET['submit'];
}
?>
