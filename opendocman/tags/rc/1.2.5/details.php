<?php
/*
details.php - display file information  check for session
Copyright (C) 2002-2007  Stephen Lawrence, Khoa Nguyen, Jon Miner

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
if (!session_is_registered('uid'))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
	exit;
}
include('config.php');
include('udf_functions.php');

// in case this file is accessed directly - check for $_REQUEST['id']
if (!isset($_REQUEST['id']) || $_REQUEST['id'] == "")
{
	header('Location:error.php?ec=2');
	exit;
}

draw_header('File Detail');
draw_menu($_SESSION['uid']);
$lrequest_id = $_REQUEST['id']; //save an original copy of id
if(strchr($_REQUEST['id'], '_') )
{
	list($_REQUEST['id'], $lrevision_id) = split('_' , $_REQUEST['id']);
	@draw_status_bar('Rev.' . $lrevision_id . ' - Details',$_REQUEST['last_message']);
        $filesize = display_filesize($GLOBALS['CONFIG']['revisionDir'] . $_REQUEST['id'] . '/' . $_REQUEST['id'] . '_' . $lrevision_id . '.dat'); 
}
else 
	@draw_status_bar('File Details',$_REQUEST['last_message']);
$filedata = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
checkUserPermission($_REQUEST['id'], $filedata->VIEW_RIGHT);
$user = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);

$userPermObj = new UserPermission($_SESSION['uid'] , $GLOBALS['connection'], $GLOBALS['database']);
$user_obj = new user($filedata->getOwner(), $GLOBALS['connection'], $GLOBALS['database']);
$secureurl = new phpsecureurl;

?>
<center>
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
	$description = 'No description available'; 
}
if ($comment == '') 
{ 
	$comment = 'No author comments available'; 
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

if(isset($reviewer_comments_fields[1]) && strlen($reviewer_comments_fields[1]) <= strlen('Subject='))
{
	$reviewer_comments_fields[1] = 'Subject=Comments regarding the review for you documentation';
}

if(isset($reviewer_comments_fields[0]) && strlen($reviewer_comments_fields[0]) <= strlen('to='))
{
	$reviewer_comments_fields[0] = 'To=Author(s)';
}
if($filedata->isArchived())
{	
        $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . '.dat';	
        $filesize = display_filesize($filename);	
}
else
{	$filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . '.dat';	

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
if ($status == 0  && $user->canWrite($_REQUEST['id'])) 
{ 
?> 
	<img src="images/file_unlocked.png" alt="" border="0" align="absmiddle">
<?php 
} 
else 
{ 
?>
	<img src="images/file_locked.png" alt="" border="0" align="absmiddle"> 
<?php 
} 
?> 
</td>
<td align="left"><font size="+1"><?php echo $realname; ?></font></td>
</tr>
<tr>
<th valign=top align=right>Category:</th><td><?php echo $category; ?></td>
</tr>
<?php
	udf_details_display($lrequest_id);
?>
<tr>

</tr>
<th valign=top align=right>File&nbsp;size:</th><td> <?php echo $filesize; ?></td>

<tr>
<th valign=top align=right>Creation&nbsp;Date:</th><td> <?php echo fix_date($created); ?></td>
</tr>

<tr>
<th valign=top align=right>Owner:</th><td>
<?php echo ' <A href="mailto:' . $user_obj->getEmailAddress() . ' ?Subject=Regarding%20your%20document:  ' . $realname . ' &Body=Hello%20 ' . $owner_fullname[0] . '"> ' . $owner . '</A> ';?></td>
</tr>

<tr>
<th valign=top align=right>Description:</th><td> <?php echo $description; ?></td>
</tr>

<tr>
<th valign=top align=right>Comment:</th><td> <?php echo $comment; ?></td>
</tr>

<tr>
<th valign=top align=right>Revision:</th><td>
    <?php
if(isset($lrevision_id))
{
    if( $lrevision_id == 0)
        echo 'original revision';
    else
        echo $lrevision_id;
}
else echo 'latest'; ?>
</td>
</tr>
<?php

if($filedata->isPublishable() ==-1 )
{
    echo('<tr><th valign=top align=right>Reviewer:</th><td>');
    echo $reviewer;
    echo(" (<A HREF='javascript:showMessage()'>reviewer's comments regarding the rejection</A>)");
}
?></td>
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
?>

<!-- available actions -->
<tr>
<td colspan="2" align="center">
<table border="0" cellspacing="5" cellpadding="5">
<tr>
<!-- inner table begins -->
<!-- view option available at all time, place it outside the block -->
<?php 
if($userPermObj->getAuthority($_REQUEST['id']) >= $userPermObj->READ_RIGHT)
{?>
<td align="center"><a href="<?php echo $secureurl->encode("view_file.php?id=$lrequest_id" . '&state=' . ($_REQUEST['state']+1)); ?>"><img src="images/view.png" title="View" alt="View" border="0"></a></td>
<?php
}		
if ($status == 0 || ($status == -1 && $filedata->isOwner($_SESSION['uid']) ) )
{
	// status = 0 -> file available for checkout
	// check if user has modify rights
	$query2 = "SELECT status FROM data, user_perms WHERE user_perms.fid = '$_REQUEST[id]' AND user_perms.uid = '$_SESSION[uid]' AND user_perms.rights = '2' AND data.status = '0' AND data.id = user_perms.fid";
	$result2 = mysql_query($query2, $GLOBALS['connection']) or die ("Error in query: $query2. " . mysql_error());
	$user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
	if($user_perms->getAuthority($_REQUEST['id'])>=$user_perms->WRITE_RIGHT && !isset($lrevision_id) && !$filedata->isArchived())
	{
		// if so, display link for checkout
?>
<td align="center"><a href="<?php echo $secureurl->encode("check-out.php?id=$lrequest_id" . '&state=' . ($_REQUEST['state']+1) . '&access_right=modify');?>"><img src="images/check-out.png" title="Check Out" alt="Check Out" border="0"></a></td>
<?php
	}
	mysql_free_result($result2);
	
	if ($userPermObj->getAuthority($_REQUEST['id']) >= $userPermObj->ADMIN_RIGHT && !@isset($lrevision_id)  && !$filedata->isArchived())
	{
		// if user is also the owner of the file AND file is not checked out
		// additional actions are available 
?>
<td align="center"><a href="<?php echo $secureurl->encode("edit.php?id=$_REQUEST[id]&state=" . ($_REQUEST['state']+1));?>"><img src="images/edit.png" title="Edit" alt="Edit" border="0"></a></td>
       <td align="center"><a href="javascript:my_delete()"><img src="images/delete.png" title="Delete" alt="Delete" border="0"></a></td>
<?php
	}
}//end if ($status == 0)
// ability to view revision history is always available 
// put it outside the block
?>
<td align="center"><a href="<?php echo $secureurl->encode("history.php?id=$lrequest_id&state=" . ($_REQUEST['state']+1)); ?>"><img src="images/revision.png" title="History" alt="History" border="0"><br></a></td>

</tr>
<!-- inner table ends -->
</table>
</td>
</tr>
</table>
</center>
<?php
draw_footer();
?>

<SCRIPT LANGUAGE="JAVASCRIPT">
	var message_window;
	var mesg_window_frm;
	function my_delete()
	{
		if(window.confirm("Are you sure?"))
		{	window.location = "<?php echo $secureurl->encode('delete.php?mode=tmpdel&id0=' . $_REQUEST['id']); ?>";	}
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
</SCRIPT>
	
<?php
// clean up
?>
