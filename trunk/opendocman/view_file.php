<?php
session_start();
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}
include('config.php');
if(!isset($submit))
{
	draw_header('View File');
	draw_menu($SESSION_UID);
	draw_status_bar('File View',$last_message);
	$file_obj = new FileData($id, $connection, $database);
	$file_name = $file_obj->getName();
	$file_id = $file_obj->getId();
	$realname = $file_obj->getName();
	
	// Get the suffix of the file so we can look it up
	// in the $mimetypes array
	list($prefix,$suffix)= split ('[.]', $realname);
	$mimetype = $mimetypes["$suffix"];
	//echo "Realname is $realname<br>";
	//echo "prefix = $prefix<br>";
	//echo "suffix = $suffix<br>";
	//echo "mime:$mimetype";	
	echo '<form action="'.$_SERVER['PHP_SELF'].'" name="view_file_form" method="get">';
	echo '<INPUT type="hidden" name="id" value="'.$id.'">';
	echo '<BR>';
	// Present a link to allow for inline viewing
	echo 'To view your file in a new window <a class="body" style="text-decoration:none" href="view_file.php?submit=view&id='.urlencode($id).'&mimetype='.urlencode("$mimetype").'">Click Here</a><br><br>';
	echo 'If you are not able to do so for some reason, ';
	echo 'click <input type="submit" name="submit" value="Download"> to download the selected document and begin downloading it to your local workstation for local view.';
	echo '</form>';

    draw_footer();
}
elseif ($submit == 'view')
{
	//echo "mimetype = $mimetype<br>";
	//exit;
	//echo "ID is $id";
	$file_obj = new FileData($id, $connection, $database);
	$realname = $file_obj->getName();
	$filename = $GLOBALS['CONFIG']['dataDir'] . $id . ".dat";
	// send headers to browser to initiate file download
	header('Content-Length: '.filesize($filename));
	// Pass the mimetype so the browser can open it
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: inline; filename=' . $realname);
        // Apache is sending Last Modified header, so we'll do it, too
        $modified=filemtime($filename);
        header('Last-Modified: '. date('D, j M Y G:i:s T',$modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
	readfile($filename);
}
elseif ($submit=='Download')
{
	$file_obj = new FileData($id, $connection, $database);
	$realname = $file_obj->getName();
	$filename = $GLOBALS['CONFIG']['dataDir'] . $id . ".dat";
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
echo 'submit is ' . $submit;
}
?>
