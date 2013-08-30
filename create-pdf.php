<?php
/*
create-pdf.php - creates a pdf version of an existing file.
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
  The process is:
  if we have posted an ID string, use that as the id of the file to generate the
PDF for.
  if not, show a form to ask for which file to use.
  
  the PDF creation does the following:
    - check that there is a .DAT file for the requested file ID.
    - if so, generate a http POST of the file data to the external pdf
      generator service.
    - the service returns a URL to the PDF.
    - do a http GET to download the PDF and put it in the data directory.
 */
/////////////////////////////////////////////////////
// Configuration
$DEBUG=False;

// check for valid session and $id
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}
include('odm-load.php');
include('create-pdf-funcs.php');
require_once("AccessLog_class.php");

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');


// includes

// open connection
if (!isset($_REQUEST['submit']))
{
    // form not yet submitted, display initial form
?>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
    Document ID: <input name="id" value="">
    <br/>
    <input type="submit" name="submit" value="Submit">
    </form>
        <?php
        draw_footer();
        ?>
<?php
}//end if (!$submit)
else
{
    // form has been submitted, process data
    $id = (int) $_REQUEST['id'];
    if ($DEBUG) echo "id=".$id."<br/>";

    createPdf($id);

    // clean up and back to main page
    // TODO - check if it was successful or not!!!
    $last_message = "PDF Generated ok.";        
    header('Location: out.php?last_message=' . urlencode($last_message));

}

?>