<?php
include 'config.php';
$_GET['starting_index'] = 0;
$_GET['stoping_index'] = 100;

if(@$_REQUEST['mode'] == 'showall')
{
	$l_query = 'SELECT id FROM data WHERE anonymous = 1';
	$l_result = mysql_query($l_query) or die(mysql_error());
	$array_id = array();
	for($i = 0; $i<mysql_num_rows($l_result); $i++)
		list($array_id[$i]) = mysql_fetch_row($l_result);
	list_files($array_id, 'ANONYMOUS', $_SERVER['PHP_SELF'],  $GLOBALS['CONFIG']['dataDir'], @$_GET['sort_order'], @$_GET['sort_by'], @$_GET['starting_index'], @$_GET['stoping_index'], 'false','false');
}
if(@$_REQUEST['mode'] == 'view_file')
{
	$file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
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

	if( isset($lrevision_id) )
	{   $filename = $lrevision_dir . $lrequest_id . ".dat";
	}
	elseif( $file_obj->isArchived() )
	{   $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";   }
	else
	{   $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";   }
	// send headers to browser to initiate file download
	header('Content-Length: '.filesize($filename));
	// Pass the mimetype so the browser can open it
	header ('Cache-control: private');
	header('Content-Type: ' . $lmimetype);
	header('Content-Disposition: inline; filename=' . $realname);
	// Apache is sending Last Modified header, so we'll do it, too
	$modified=filemtime($filename);
	header('Last-Modified: '. date('D, j M Y G:i:s T',$modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
	readfile($filename);

}
