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
require_once("AccessLog_class.php");

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');


// includes

// open connection
if (!isset($_POST['submit']))
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
    $id = (int) $_POST['id'];
    if ($DEBUG) echo "id=".$id."<br/>";

    // Determine filename of our document.
    $lfilename = $GLOBALS['CONFIG']['dataDir'] . $id .'.dat';
    if ($DEBUG) echo "lfilename=".$lfilename."<br/>";
    // TODO:  Check that the file actually exists!!

    // Submit file to an external pdf generator service using curl.
    $postData = array('file'=>'@'.$lfilename,'submit'=>'True'); 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,"http://maps.webhop.net/odm/pdfgen/pdfgen.php"); 
    curl_setopt($ch, CURLOPT_POST,1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); 
    $result=curl_exec ($ch); 
    curl_close ($ch); 
    if ($DEBUG) echo "result=".$result."<br/>"; 

    // The result contains a URL to the PDF file.
    // Extract the 'href' from the html using regular expression
    // from http://stackoverflow.com/questions/5397531/parsing-html-source-to-extract-anchor-and-link-tags-href-value
    preg_match_all('/href=[\'"]?([^\s\>\'"]*)[\'"\>]/', $result, $matches);
    $hrefs = ($matches[1] ? $matches[1] : false);
    if ($DEBUG) var_dump($hrefs);

    // Now use CURL to download the PDF file.
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$hrefs[0]); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); 
    $result=curl_exec ($ch); 
    curl_close ($ch); 
    if ($DEBUG) echo $result;

    // Save result to PDF file in correct place
    // copy temporary file to DATADIR and give it the correct suffix.
    $pdfFname = $GLOBALS['CONFIG']['dataDir'] . $id .'.pdf';
    $lfhandler = fopen ($pdfFname, "w");
    fwrite($lfhandler, $result);
    fclose ($lfhandler);

    echo "<h1>Done!</h1>";

}

?>