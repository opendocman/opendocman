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

/////////////////////////////////////////////////////
// Configuration
$DEBUG=False;

/**
 *  createPdf($id)
 *  Creates a PDF version of the current revision of file id number $id.  Uses an external PDF generator web service
 *   which is hard coded into this function at the moment.
 */
function createPdf($id) {
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

    // TODO - Add error checking to determine if this has worked or not!

    return(0);
}

?>