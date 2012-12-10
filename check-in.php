<?php
/*
check-in.php - uploads a new version of a file
Copyright (C) 2002-2011 Stephen Lawrence Jr.

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


// check for valid session and $id
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}
include('odm-load.php');
require_once("AccessLog_class.php");

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '')
{
    $last_message='Failed';
    header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
    exit;
}

// includes

// open connection
if (!isset($_POST['submit']))
{
    // form not yet submitted, display initial form

    // pre-fill the form with some information so that user knows which file is being updated
    $query = "SELECT description, realname FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = '$_REQUEST[id]' AND status = '$_SESSION[uid]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    // in case script is directly accessed, query above will return 0 rows
    if (mysql_num_rows($result) <= 0)
    {
        $last_message='Failed';
        header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
        exit;
    }
    else
    {
        // get result data
        list($description, $realname) = mysql_fetch_row($result);
        draw_header(msg('button_check_in'),$last_message);
        // correction
        if($description == '')
        {
            $description = msg('message_no_description_available');
        }

        // clean up
        mysql_free_result($result);
        // start displaying form
        ?>
<table border="0" cellspacing="5" cellpadding="5">
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
        <tr>
            <td><b><?php echo msg('label_filename');?></b></td>
            <td><b><?php echo $realname; ?></b></td>
        </tr>

        <tr>
            <td><b><?php echo msg('label_description');?></b></td>
            <td><?php echo $description; ?></td>
        </tr>

        <tr>
            <td><b><?php echo msg('label_file_location');?></b></td>
            <td><input name="file" type="file"></td>
        </tr>

        <tr>
            <td><?php echo msg('label_note_for_revision_log');?></td>
            <td><textarea name="note"></textarea></td>
        </tr>


        <tr>
            <td colspan="4" align="center"><div class="buttons"><button class="positive" type="submit" name="submit" value="Check  Document In"><?php echo msg('button_check_in')?></button></div></td>
        </tr>
    </form>
</table>
        <?php
        draw_footer();
        ?>
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
    }//end else
}//end if (!$submit)
else
{
    if ($GLOBALS['CONFIG']['authorization'] == 'True')
    {
        $lpublishable = '0';
    }
    else
    {
        $lpublishable= '1';
    }
    // form has been submitted, process data

    // checks
    $id = (int) $_POST['id'];

    $query = "SELECT realname FROM {$GLOBALS['CONFIG']['db_prefix']}data where id = '$id'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: ".$mysql_error());

    //
    if(mysql_num_rows($result) != 1)
    {
        $last_message='Failed';
        header('Location:error.php?ec=16&last_message=' . urlencode($last_message));
        exit;
    }

    list($realname) = mysql_fetch_row($result);

    if($_FILES['file']['name'] != $realname)
    {
        $last_message='Failed';
        header('Location:error.php?ec=15&last_message=' . urlencode($last_message));
        exit;
    }

    // no file!
    if ($_FILES['file']['size'] <= 0)
    {
        $last_message='Failed';
        header('Location:error.php?ec=11&last_message=' . urlencode($last_message));
        exit;
    }

    // check file type
    foreach($GLOBALS['CONFIG']['allowedFileTypes'] as $thistype)
    {
        if ($_FILES['file']['type'] == $thistype)
        {
            $allowedFile = 1;
            break;
        }
        else
        {
            $allowedFile = 0;
        }
    }
    // illegal file type!
    if ($allowedFile != 1)
    {
        $last_message='MIMETYPE: ' . $_FILES['file']['type'] . ' Failed';
        header('Location:error.php?ec=13&last_message=' . urlencode($last_message));
        exit;
    }

    // query to ensure that user has modify rights
    $fileobj = new FileData($id, $GLOBALS['connection'], DB_NAME);
    if($fileobj->getError() == '' and $fileobj->getStatus() == $_SESSION['uid'])
    {
        //look to see how many revision are there
        $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = '$id'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        $lrevision_num = mysql_num_rows($result);
        // if dir not available, create it
        if( !is_dir($GLOBALS['CONFIG']['revisionDir']) )
        {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'], 0775))
            {
                $last_message=msg('message_directory_creation_failed'). ': ' . $GLOBALS['CONFIG']['revisionDir'] ;
                header('Location:error.php?ec=23&last_message=' . urlencode($last_message));
                exit;
            }
        }
        if( !is_dir($GLOBALS['CONFIG']['revisionDir'] . $id) )
        {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'] . $id, 0775))
            {
                $last_message=msg('message_directory_creation_failed') . ': ' . $GLOBALS['CONFIG']['revisionDir'] .  $id;
                header('Location:error.php?ec=23&last_message=' . urlencode($last_message));
                exit;
            }

        }
        $lfilename = $GLOBALS['CONFIG']['dataDir'] . $id .'.dat';
        //read and close
        $lfhandler = fopen ($lfilename, "r");
        $lfcontent = fread($lfhandler, filesize ($lfilename));
        fclose ($lfhandler);
        //write and close
        $lfhandler = fopen ($GLOBALS['CONFIG']['revisionDir'] . $id . '/' . $id . '_' . ($lrevision_num - 1) . '.dat', "w");
        fwrite($lfhandler, $lfcontent);
        fclose ($lfhandler);
        // all OK, proceed!
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id='{$_SESSION['uid']}'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        list($username) = mysql_fetch_row($result);
        // update revision log
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log set revision='" . intval((intval($lrevision_num) - 1)) . "' WHERE id = '{$id}' and revision = 'current'";
        mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES('$id', NOW(), '" . addslashes($username) . "', '". addslashes($_POST['note']) ."', 'current')";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // update file status
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET status = '0', publishable='$lpublishable' WHERE id='$id'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // rename and save file
        $newFileName = $id . '.dat';
        copy($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . $newFileName);
    
        AccessLog::addLogEntry($id,'I');
    
        // clean up and back to main page
        $last_message = msg('message_document_checked_in');        
        header('Location: out.php?last_message=' . urlencode($last_message));
    }
}