<?php
/*
create-pdf-funcs.php - function to create a pdf version of an existing file.
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

include('odm-load.php');

/////////////////////////////////////////////////////
// Configuration
$DEBUG=False;

/**
 *  createPdf($id)
 *  Creates a PDF version of the current revision of file id number $id.  Uses an external PDF generator web service
 *   which is hard coded into this function at the moment.
 */
function createPdf($id) {
  global $DEBUG;
    // Determine filename of our document.
    $lfilename = $GLOBALS['CONFIG']['dataDir'] . $id .'.dat';
    if ($DEBUG) echo "lfilename=".$lfilename."<br/>";

    // if our file does not exist, exit with a non-zero error code.
    if (!file_exists($lfilename)) {
      echo "Error - ".$lfilename." does not exist.<br/>";
      return (1);
    }
    else {
	if ($DEBUG) echo "phew - ".$lfilename." exists.<br/>";
    }

    // create a link to the file in a temporary file with the correct suffix, 
    // so that the pdf generator has a fighting chance of doing the conversion
    // properly.
    $suffix = getSuffix($id);
    $ltmpfilename = sys_get_temp_dir().'/'.$id.'.'.$suffix;
    if ($DEBUG) echo "ltmpfilename=".$ltmpfilename."<br/>";

    // if our temporary file already exists, delete it.
    if (file_exists($ltmpfilename))
      unlink($ltmpfilename);

    // Actually create the symbolic link.
    if (!symlink($lfilename,$ltmpfilename)) {
      echo "Error creating symbolic link ".$ltmpfilename.".<br/>";
      return(1);   // exit if creating symlink fails.
    } else {
      if ($DEBUG) echo "Symbolic link ".$ltmpfilename." created ok.<br/>";
    }

    // Submit file to an external pdf generator service using curl.
    $postData = array('file'=>'@'.$ltmpfilename,'submit'=>'True'); 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,"http://maps.webhop.net/odm/pdfgen/pdfgen.php"); 
    curl_setopt($ch, CURLOPT_POST,1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); 
    if ($DEBUG) echo "Using web service to generate PDF.....<br/>";
    $result=curl_exec ($ch); 
    curl_close ($ch); 
    if ($DEBUG) echo "result=".$result."<br/>"; 

    if ($result == False) {
      echo "Error from PDF creation web service.<br/>";
      return(1);
    }

    // delete the symbolic link to the temporary file - no longer needed.
    unlink($ltmpfilename);

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
    if ($result == False) {
      echo "Error downloading pdf from PDF creation web service.<br/>";
      return(1);
    }

    // Save result to PDF file in correct place
    // copy temporary file to DATADIR and give it the correct suffix.
    $pdfFname = $GLOBALS['CONFIG']['dataDir'] . $id .'.pdf';
    $lfhandler = fopen ($pdfFname, "w");
    if ($lfhandler == False) {
      echo "Error opening file ".$pdfFname." for writing.<br/>";
      return(1);
    }
    if (!fwrite($lfhandler, $result)) {
      echo "Error writing data to file ".$pdfFname.".<br/>";
      return(1);
    }
    fclose ($lfhandler);

    return(0);
}

?>