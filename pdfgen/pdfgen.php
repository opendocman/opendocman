<?php
/*
pdfgen.php - creates a pdf of an uploaded file using libreOffice.
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

INSTALLATION NOTES
==================
For this to work, $DATADIR (defined below) must be writable by the 
web server process owner (usually www-data).
Also ~www-data/.config must exist and be writable by www-data 
(often /var/www/.config)

The .config requirement is necessary to avoid an obscure '77' return code 
with no helpful error message!
*/
error_reporting(E_ALL);
#$DATADIR = "/home/graham/odm_git/pdfgen/data";
$DATADIR = "/home/disk2/graham/opendocman-git/pdfgen/data";

// open connection
if (!isset($_POST['submit']))
{
    // form not yet submitted, display initial form

  ?>
  <form 
    action="<?php echo $_SERVER['PHP_SELF']; ?>" 
    method="POST" 
    enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
    <tr>
    <td><b>File:</b></td>
            <td><input name="file" type="file"></td>
    </tr>
    <tr>
    <td colspan="4" align="center"><button type="submit" name="submit" value="Convert File">Convert File</button></td>
    </tr>
    </table>
    </form>

<script type="text/javascript">
    function check(select, send_dept, send_all)
    {
        if(send_dept.checked || select.options[select.selectedIndex].value != "0")
            send_all.disabled = true;
        else
        {
            send_all.disabled = false;
            for(var i = 1; i < select.options.length; i++)
                select.options[i].selected = false;
        }
    }
</script>
        <?php

}//end if (!$submit)
else
{
    $realname = $_FILES['file']['name'];
    $rootname = (substr($realname,0,(strrpos($realname,"."))));
    $suffix = strtolower((substr($realname,((strrpos($realname,".")+1)))));
    $tmpfilepath = $_FILES['file']['tmp_name'];
    // no file!
    if ($_FILES['file']['size'] <= 0)
    {
	echo "<h1>Failed!!!! empty file?</h1>";
        exit;
    }

    $tmpfilename = (substr($tmpfilepath,((strrpos($tmpfilepath,"/")+1))));
    $nativefname = $DATADIR . "/" . $tmpfilename. '.' . $suffix;
    $pdffname = $DATADIR . "/" . $tmpfilename. '.pdf';

    echo "<h1>It worked!!! </h1>";
    echo "<p>Filename = ".$realname."</p>";
    echo "<p>Root Filename = ".$rootname." - suffix= ".$suffix."</p>";
    echo "<p>Tmpfilename = ".$tmpfilename."</p>";
    echo "<p>Nativefilename = ".$nativefname."</p>";
    echo "<p>Pdffilename = ".$pdffname."</p>";

    // copy temporary file to DATADIR and give it the correct suffix.
    $lfhandler = fopen ($tmpfilepath, "r");
    $lfcontent = fread($lfhandler, filesize ($tmpfilepath));
    fclose ($lfhandler);
    //write and close
    $lfhandler = fopen ($nativefname, "w");
    fwrite($lfhandler, $lfcontent);
    fclose ($lfhandler);

    //system("ls");
    //echo "<br/>";
    // Do conversion to pdf using the libreoffice 'soffice' application.
    $cmdline = "/usr/bin/soffice --headless --convert-to pdf --outdir ".$DATADIR." ".$nativefname;
    //$cmdline = "sh ".dirname(__FILE__)."/pdfgen.sh ".$DATADIR." ".$nativefname;
    echo $cmdline."<br/>";
    $retStr = system($cmdline, $retval);
    echo "output=".$retStr.", retval=".var_dump($retval)."<br/>";

    echo "<p>Output will appear <a href='data/".$tmpfilename.".pdf'>here</a></p>";

   echo "<p>Using ", memory_get_peak_usage(1), " bytes of ram.</p>"; 
}
