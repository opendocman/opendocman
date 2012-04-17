<?php
/*
details.php - display file information  check for session
Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
Copyright (C) 2008-2012 Stephen Lawrence Jr.

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

//$_SESSION['uid']=102; $_REQUEST['id']=75;
session_start();
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
	exit;
}
include('odm-load.php');
include('udf_functions.php');

$last_message = isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '';

$full_requestId = $_REQUEST['id'];

// in case this file is accessed directly - check for $_REQUEST['id']
if (!isset($_REQUEST['id']) || $_REQUEST['id'] == "")
{
	header('Location:error.php?ec=2');
	exit;
}

if(strchr($_REQUEST['id'], '_') )
{
	list($_REQUEST['id'], $lrevision_id) = explode('_' , $_REQUEST['id']);
	$pageTitle = msg('area_file_details') . ' ' . msg('revision'). ' #' . $lrevision_id;
        $filesize = display_filesize($GLOBALS['CONFIG']['revisionDir'] . $_REQUEST['id'] . '/' . $_REQUEST['id'] . '_' . $lrevision_id . '.dat'); 
}
else
{
	$pageTitle = msg('area_file_details');
}

draw_header(msg('area_file_details'), $last_message);

$lrequest_id = $_REQUEST['id']; //save an original copy of id

$filedata = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);
checkUserPermission($_REQUEST['id'], $filedata->VIEW_RIGHT, $filedata);
$user = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

$userPermObj = new UserPermission($_SESSION['uid'] , $GLOBALS['connection'], DB_NAME);
$user_obj = new user($filedata->getOwner(), $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;

?>
<table border="0" width=80% cellspacing="4" cellpadding="1">
<?php
// display details
$ownerId = $filedata->getOwner();
$category = $filedata->getCategoryName();
$owner_fullname = $filedata->getOwnerFullName();
$owner = $owner_fullname[1].', '.$owner_fullname[0];
$realname = $filedata->getName();
$created = $filedata->getCreatedDate();
$description = $filedata->getDescription();
$comment = $filedata->getComment();
$status = $filedata->getStatus();
$reviewer = $filedata->getReviewerName();
// corrections
if ($description == '') 
{ 
	$description = msg('message_no_description_available');
}

if ($comment == '') 
{ 
	$comment = msg('message_no_author_comments_available');
}

$reviewer_comments_str = $filedata->getReviewerComments();
$reviewer_comments_fields = explode(';', $reviewer_comments_str);

for($i = 0; $i< sizeof($reviewer_comments_fields); $i++)
{
	$reviewer_comments_fields[$i] = str_replace('"', '&quot;', $reviewer_comments_fields[$i]);
	$reviewer_comments_fields[$i] = str_replace('\\', '', $reviewer_comments_fields[$i]);
}

if(isset($reviewer_comments_fields[2]) && strlen($reviewer_comments_fields[2]) <= strlen('Comments='))
{
	$reviewer_comments_fields[2] = 'Comments=This file does not meet the requirement.  Please fix it and resubmit for review again.';
}
else
{
    $reviewer_comments_fields[2] = '';
}

if(isset($reviewer_comments_fields[1]) && strlen($reviewer_comments_fields[1]) <= strlen('Subject='))
{
	$reviewer_comments_fields[1] = 'Subject=Comments regarding the review for you documentation';
}
else
{
    $reviewer_comments_fields[1] = '';
}

if(isset($reviewer_comments_fields[0]) && strlen($reviewer_comments_fields[0]) <= strlen('to='))
{
	$reviewer_comments_fields[0] = 'To=Author(s)';
}
else
{
    $reviewer_comments_fields[0] = '';
}

if($filedata->isArchived())
{	
        $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . '.dat';	
        $filesize = display_filesize($filename);	
}
else
{
    $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . '.dat';

    if (!isset($filesize))
    {
        $filesize = display_filesize($filename);
    }
}
?>
<FORM name="data">
<INPUT type="hidden" name="to" value="<?php echo substr($reviewer_comments_fields[0], 3) ?>">
<INPUT type="hidden" name="subject" value="<?php echo substr($reviewer_comments_fields[1], 8) ?>">
<INPUT type="hidden" name="comments" value="<?php echo substr($reviewer_comments_fields[2], 9) ?>">
</FORM>
<tr>
<td align="right">
<?php 
// display red or green icon depending on file status
if ($status == 0  && $user->canView($_REQUEST['id'])) 
{ 
    echo '<img src="images/file_unlocked.png" alt="" border="0" align="absmiddle">';
} 
else 
{ 
    echo '<img src="images/file_locked.png" alt="" border="0" align="absmiddle">';
} 
?> 
</td>
<td align="left"><font size="+1"><?php echo $realname; ?></font></td>
</tr>
<tr>
<th valign=top align=right><?php echo msg('category')?>:</th><td><?php echo $category; ?></td>
</tr>
<?php
	udf_details_display($lrequest_id);
?>
<tr>
<th valign=top align=right><?php echo msg('label_size')?>:</th><td> <?php echo $filesize; ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('label_created_date')?>:</th><td> <?php echo fix_date($created); ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('owner')?>:</th><td>
<?php echo ' <A href="mailto:' . $user_obj->getEmailAddress() . ' ?Subject=Regarding%20your%20document:  ' . $realname . ' &Body=Hello%20 ' . $owner_fullname[0] . '"> ' . $owner . '</A> ';?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('label_description')?>:</th><td> <?php echo $description; ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('label_comment')?>:</th><td> <?php echo $comment; ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('revision')?>:</th><td>
    <?php
    if(isset($lrevision_id))
    {
        if( $lrevision_id == 0)
        {
            echo msg('message_original_version');
        }
        else
        {
            echo $lrevision_id;
        }
    }
    else
    {
        echo msg('message_latest_version'); 
    }
    ?>
</td>
</tr>
<?php

if($filedata->isPublishable() ==-1 )
{
    echo('<tr><th valign=top align=right>Reviewer:</th><td>');
    echo $reviewer;
    echo(" (<A HREF='javascript:showMessage()'>" .msg('message_reviewers_comments_re_rejection') . "</A>)");
}
?>
</td>
</tr>
    <?php
if ($status > 0)
{
    // status != 0 -> file checked out to another user. status = uid of the check-out person
    // query to find out who...
    $checkout_person_obj = $filedata->getCheckerOBJ();

    ?>
        <tr>
        <th valign=top align=right>Checked out to:</th><td>
        <?php 
        $fullname = $checkout_person_obj->getFullName();
    echo ' <A href="mailto:' . $checkout_person_obj->getEmailAddress() . ' ?Subject=Regarding%20your%20checked-out%20document:  ' . $realname . ' &Body=Hello%20 ' . $fullname[0] . '"> ' . $fullname[1] . ', ' . $fullname[0] . '</A> ';
    ?></td>
        </tr>
        <?php
}

// Call the plugin API
callPluginMethod('onDuringDetails',$filedata->id);
?>
<!-- available actions -->
<tr>
<td colspan="2" align="center">
<table border="0" cellspacing="5" cellpadding="5">
<tr>
<!-- inner table begins -->
<!-- view option available at all time, place it outside the block -->
<?php 
if($userPermObj->getAuthority($_REQUEST['id'],$filedata) >= $userPermObj->READ_RIGHT)
{
?>
<td align="center"><div class="buttons"><a href="<?php echo $secureurl->encode("view_file.php?id=$full_requestId" . '&state=' . ($_REQUEST['state']+1)); ?>" class="positive"><img src="images/view.png" alt="view"/><?php echo msg('detailspage_view')?></a></div></td>
<?php
}		

if ($status == 0 || ($status == -1 && $filedata->isOwner($_SESSION['uid']) ) )
{
	// status = 0 -> file available for checkout
	// check if user has modify rights
	$query2 = "SELECT status FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE {$GLOBALS['CONFIG']['db_prefix']}user_perms.fid = '$_REQUEST[id]' AND {$GLOBALS['CONFIG']['db_prefix']}user_perms.uid = '$_SESSION[uid]' AND {$GLOBALS['CONFIG']['db_prefix']}user_perms.rights = '2' AND {$GLOBALS['CONFIG']['db_prefix']}data.status = '0' AND {$GLOBALS['CONFIG']['db_prefix']}data.id = {$GLOBALS['CONFIG']['db_prefix']}user_perms.fid";
	$result2 = mysql_query($query2, $GLOBALS['connection']) or die ("Error in query: $query2. " . mysql_error());
	$user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
	if($user_perms->getAuthority($_REQUEST['id'], $filedata)>=$user_perms->WRITE_RIGHT && !isset($lrevision_id) && !$filedata->isArchived())
	{
		// if so, display link for checkout
?>
<td align="center"><div class="buttons"><a href="<?php echo $secureurl->encode("check-out.php?id=$lrequest_id" . '&state=' . ($_REQUEST['state']+1) . '&access_right=modify');?>" class="regular"><img src="images/check-out.png" alt="check out"/><?php echo msg('detailspage_check_out')?></a></div></td>
<?php
	}

	mysql_free_result($result2);
	
	if ($userPermObj->getAuthority($_REQUEST['id'], $filedata) >= $userPermObj->ADMIN_RIGHT && !@isset($lrevision_id)  && !$filedata->isArchived())
	{
		// if user is also the owner of the file AND file is not checked out
		// additional actions are available 
?>
<td align="center"><div class="buttons"><a href="<?php echo $secureurl->encode("edit.php?id=$_REQUEST[id]&state=" . ($_REQUEST['state']+1));?>" class="regular"><img src="images/edit.png" alt="edit"/><?php echo msg('detailspage_edit')?></a></div></td>
       <td align="center"><div class="buttons"><a href="javascript:my_delete()" class="negative"><img src="images/delete.png" alt="delete"/><?php echo msg('detailspage_delete')?></a></div></td>
<?php
	}
}
////end if ($status == 0)
// ability to view revision history is always available 
// put it outside the block
?>
<td align="center"><div class="buttons"><a href="<?php echo $secureurl->encode("history.php?id=$lrequest_id&state=" . ($_REQUEST['state']+1)); ?>" class="regular"><img src="images/history.png" alt="history"/><?php echo msg('detailspage_history')?></a></div></td>

</tr>
<!-- inner table ends -->
</table>
</td>
</tr>
</table>

<script type="text/javascript">
	var message_window;
	var mesg_window_frm;
	function my_delete()
	{
		if(window.confirm("<?php echo msg('detailspage_are_sure')?>")) {	
		window.location = "<?php echo $secureurl->encode('delete.php?mode=tmpdel&id0=' . $_REQUEST['id']); ?>";	
		}
	}
	function sendFields()
	{
		mesg_window_frm = message_window.document.author_note_form;
		mesg_window_frm.to.value = document.data.to.value;
		mesg_window_frm.subject.value = document.data.subject.value;
		mesg_window_frm.comments.value = document.data.comments.value;
	}
	function showMessage()
	{
		message_window = window.open('toBePublished.php?submit=comments', 'comment_wins', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=200');
		message_window.focus();
		setTimeout("sendFields();", 500);
	}
</script>
<?php
draw_footer();
