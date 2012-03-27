<?php
/*
rejects.php - Show rejected files
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.

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

//print_r($_REQUEST);
session_start();
if (!isset ($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}
include ('./odm-load.php');
// includes
$with_caption = false;

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if(!isset($_POST['submit']))
{
    draw_header(msg('message_documents_rejected'), $last_message);
    $page_url = $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'];

    $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    $userperms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    if($user_obj->isAdmin() && @$_REQUEST['mode'] == 'root')
    {
        $fileid_array = $user_obj->getAllRejectedFileIds();
    }
    else
    {
        $fileid_array = $user_obj->getRejectedFileIds();
    }

    if(@$_REQUEST['mode']=='root')
    {
        echo '<form name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '?mode=root"' . ' method="post">';
    }
    else
    {
        echo '<form name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '" method="post">';
    }
    ?>
<table border="0">
    <tr>
        <td>

<?php
$list_status = list_files($fileid_array, $userperms, $GLOBALS['CONFIG']['dataDir'], true, true);


?>
        </td>
    </tr>
<?php
            if($list_status != -1)
            {
?>
    <tr>
        <td>
                <div class="buttons">
                    <button class="positive" type="submit" name="submit" value="resubmit"><?php echo msg('button_resubmit_for_review'); ?></button>
                    <button class="negative" type="submit" name="submit" value="delete"><?php echo msg('button_delete'); ?></button>
                </div>
<?php
            }
?>
</table>
</form>

<?php
           draw_footer();
}
elseif(isset($_POST['submit']) && $_POST['submit'] == 'resubmit')
{
    if(!isset($_REQUEST['checkbox']))
    {
        header('Location: ' .$_SERVER['PHP_SELF'] . '?last_message=' . urlencode(msg('message_you_did_not_enter_value')));
        exit;
    }
    
    if(isset($_POST["checkbox"]))
    {
        foreach($_POST['checkbox'] as $cbox)
        {
            $fileid = $cbox;
            $file_obj = new FileData($fileid, $GLOBALS['connection'], DB_NAME);
            //$user_obj = new User($file_obj->getOwner(), $connection, DB_NAME);
            //$mail_to = $user_obj->getEmailAddress();
            //mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
            $file_obj->Publishable(0);
        }
    }
    header('Location:' . $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'] . '&last_message='. msg('message_file_authorized'));
}
elseif($_POST['submit'] == 'delete')
{
    if(!isset($_REQUEST['checkbox']))
    {
        header('Location: ' .$_SERVER['PHP_SELF'] . '?last_message=' . urlencode(msg('message_you_did_not_enter_value')));
        exit;
    }
    
    $url = 'delete.php?mode=tmpdel&';
    $id = 0;
    if(isset($_POST["checkbox"]))
    {
        $loop = 0;
        foreach($_POST['checkbox'] as $num=>$cbox)
        {
            $fileid = $cbox;
            $url .= 'id'.  $num . '='.$fileid.'&';
            $id ++;
            $loop++;
        }
        $url = substr($url, 0, strlen($url)-1);
    }
    header('Location:'.$url.'&num_checkboxes=' . $loop);
}

?>
<script type="text/javascript">
    function closeWindow(close_window_in_ms)
    {
    setTimeout(window.close, close_window_in_ms);
    }


function checkedBoxesNumber()
{
    counter=0;
    record="";
    for(j=0; j<document.forms[0].elements.length; j++)
    {
            if(document.forms[0].elements[j].type == "checkbox")
            {
                    counter++;
            }
    }
    for(i=1; i<counter; i++)
    {
            if(eval('document.forms[0].checkbox' + i + '.checked') == true)
            {
                    id=(eval('document.forms[0].checkbox' + i + '.value'));
                    document.table.fileid.value +="" + id +" ";
                    record +="" + i +" ";
            }
    }

    document.table.checkedboxes.value = record;
    document.table.checkednumber.value = counter;
    alert("boxes " + document.table.checkedboxes.value  + " are selected");

}

</script>