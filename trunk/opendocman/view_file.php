<?php
/*
view_file.php - draws screen which allows users to view files inline
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

session_cache_limiter('private');
session_start();
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING'] ));
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
    {
        // Fix by blackwes
        $prefix = (substr($realname,0,(strrpos($realname,"."))));
        $suffix = strtolower((substr($realname,((strrpos($realname,".")+1)))));    
    }
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
	echo 'To view your file in a new window <a class="body" style="text-decoration:none" target="_new" href="view_file.php?submit=view&id='.urlencode($lrequest_id).'&mimetype='.urlencode("$lmimetype").'">Click Here</a><br><br>';
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
    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($_REQUEST['id'], $file_obj->READ_RIGHT);
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
        header('Content-Disposition: inline; filename="' . $realname . '"');
		// Apache is sending Last Modified header, so we'll do it, too
        $modified=filemtime($filename);
        header('Last-Modified: '. date('D, j M Y G:i:s T',$modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
	readfile($filename);
}
elseif ($_GET['submit'] == 'Download')
{
	$file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
    // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
    checkUserPermission($_REQUEST['id'], $file_obj->READ_RIGHT);
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
	header ('Content-Disposition: attachment; filename="' . $realname . '"');
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
