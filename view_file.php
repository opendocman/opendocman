<?php
/*
view_file.php - draws screen which allows users to view files inline
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
Copyright (C) 2013 Graham Jones

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

/*
 * view_file.php
 * Parameters (GET query strings):
 *   id: id number of file to be viewed
 *   submit: must be set to trigger download, otherwise form is displayed.  If submit='Download', the file is downloaded
 *          rather than viewed in the browser window.
 *   native: If set, the native (e.g. .doc) file is provided.  If native is not set or is 'False', the PDF version is provided.
 */

session_cache_limiter('private');
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

include('odm-load.php');
require_once("AccessLog_class.php");

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

$secureurl_obj = New phpsecureurl();

$lrequest_id = $_REQUEST['id']; //save an original copy of id
if(strchr($_REQUEST['id'], '_') )
{
    list($_REQUEST['id'], $lrevision_id) = explode('_' , $_REQUEST['id']);
    $lrevision_dir = $GLOBALS['CONFIG']['revisionDir'] . '/'. $_REQUEST['id'] . '/';
}


if(!isset($_GET['submit']))
  {
    draw_header(msg('view') . ' ' . msg('file'),$last_message);
    $file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);
    $file_name = $file_obj->getName();
    $file_id = $file_obj->getId();
    $realname = $file_obj->getName();

    echo '<form action="view_file.php" name="view_file_form" method="get">';
    echo '<INPUT type="hidden" name="id" value="'.$lrequest_id.'">';
    echo '<BR>';
    echo "<INPUT type='checkbox' name='native'> Download native (non-PDF) file?<br/>";
    // Present a link to allow for inline viewing
    echo msg('message_to_view_your_file') . ' <a class="body" style="text-decoration:none" target="_new" href="view_file.php?submit=view&id=' . urlencode($lrequest_id) . '">' . msg('button_click_here') . '</a><br><br>';
    echo '<div class="buttons"><button class="regular" type="submit" name="submit" value="Download">';
    echo msg('message_if_you_are_unable_to_view2');
    echo '</button></div>';
    echo msg('message_if_you_are_unable_to_view1');
    echo msg('message_if_you_are_unable_to_view2');
    echo msg('message_if_you_are_unable_to_view3');
    echo '</form>';

    draw_footer();
  }
else {  // Submit is set
  $file_obj = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);
  // Added this check to keep unauthorized users from downloading - Thanks to Chad Bloomquist
  checkUserPermission($_REQUEST['id'], $file_obj->READ_RIGHT, $file_obj);
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
  $lmimetype = File::mime_by_ext($suffix);

  // Check if we have asked for the native (e.g. .doc) file.  If not, provide the PDF version.
  // Select the correct filename depending on the file requested.
  if (isset($_REQUEST['native'])) 
    if ($_REQUEST['native'] == 'False')
      $native = False;
    else
      $native = True;
  else
    $native = False;
  if( isset($lrevision_id) )
    {
      $filename_native = $lrevision_dir . $lrequest_id . ".dat";
      $filename_pdf = $lrevision_dir . $lrequest_id . ".pdf";
    }
  elseif( $file_obj->isArchived() )
    {
      $filename_native = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";
      $filename_pdf = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".pdf";
    }
  else
    {
      $filename_native = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";
      $filename_pdf = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".pdf";
    }
  
  // Select the native or PDF filename.
  if ($native)
    $filename = $filename_native;
  else
    $filename = $filename_pdf;
  
  // If the file exists, send it to the browser.
  if ( file_exists($filename) )
    {
      // send headers to browser to initiate file download
      header('Content-Length: '.filesize($filename));
      // Pass the mimetype so the browser can open it
      header ('Cache-control: private');
      if ($native) {
	header('Content-Type: ' . $lmimetype);
	if ($_GET['submit'] == 'Download')
	  header('Content-Disposition: inline; filename="' . rawurlencode($realname) . '"');
	else
	  header('Content-Disposition: attachment; filename="' . rawurlencode($realname) . '"');
      }
      else {
	header('Content-Type: application/pdf');
	if ($_GET['submit'] == 'Download')
	  header('Content-Disposition: inline; filename="' . rawurlencode($_REQUEST['id'].".pdf") . '"');
	else
	  header('Content-Disposition: attachment; filename="' . rawurlencode($_REQUEST['id'].".pdf") . '"');
      }
      // Apache is sending Last Modified header, so we'll do it, too
      $modified=filemtime($filename);
      header('Last-Modified: '. date('D, j M Y G:i:s T',$modified));   // something like Thu, 03 Oct 2002 18:01:08 GMT
      readfile($filename);
    }
  else
    {
      echo msg('message_file_does_not_exist');
    }
}
?>
