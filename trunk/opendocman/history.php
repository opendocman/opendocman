<?php

// history.php - display revision history

// check session and $id
session_start();
if (!session_is_registered('uid'))
{
header('Location:index.php?redirection=' . urlencode($_SERVER['REQUEST_URI']));
exit;
}
include('config.php');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '')
{
header('Location:error.php?ec=2');
exit;
}

// includes
if( !isset($_REQUEST['title']) )
{	draw_header('');	}
else 
{ draw_header( $_REQUEST['title'] ); }
draw_menu($_SESSION['uid']);
draw_status_bar('History', @$_REQUEST['last_message']);
//revision parsing
if(strchr($_REQUEST['id'], '_') )
{
	list($_REQUEST['id'], $lrevision_id) = split('_' , $_REQUEST['id']);
}
$datafile = new FileData($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
// verify
if ($datafile->getError() != NULL)
{
	header('Location:error.php?ec=2');
	exit;
}
else
{
// obtain data from resultset

$owner_fullname = $datafile->getOwnerFullName();
$owner = $owner_fullname[1].', '.$owner_fullname[0];
$realname = $datafile->getRealName();
$category = $datafile->getCategoryName();
$created = $datafile->getCreatedDate();
$description = $datafile->getDescription();
$comments = $datafile->getComment();
$status = $datafile->getStatus();

// corrections
if ($description == '') 
    { 
        $description = 'No description available'; 
    }
if ($comments == '') 
    { 
        $comment = 'No author comments available'; 
    }
if($datafile->isArchived())
{	$filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . '.dat';	}
else
{	$filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . '.dat';	}
?>
<center>
<table border="0" width="400" cellspacing="4" cellpadding="1">

<tr>
<td>
<?php
// check file status, display appropriate icon
if ($status == 0) 
    { 
        echo '<img src="images/file_unlocked.png" alt="" border=0 align="absmiddle">';
    } 
else 
    { 
        echo '<img src="images/file_locked.png"  alt="" border=0 align="absmiddle">';
    }
echo '&nbsp;&nbsp;<font size="+1">'.$realname.'</font></td>';
?>
</tr>

<tr>
<td>Category: <?php echo $category; ?></td>
</tr>

<tr>
<td>File size: <?php echo filesize($filename); ?> bytes</td>
</tr>

<tr>
<td>Created on: <?php echo fix_date($created); ?></td>
</tr>

<tr>
<td>Owned by: <?php echo $owner; ?></td>
</tr>

<tr>
<td>Description of contents: <?php echo $description; ?></td>
</tr>

<tr>
<td>Author comment: <?php echo $comments; ?></td>
</tr>
<tr>
<td>Revision: 
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
<tr>
<td>&nbsp;</td>
</tr>

<!-- history table -->
<tr>
<td>
<img src="images/revision.png" width=40 height=40 alt="" border="0" align="absmiddle">&nbsp;&nbsp;Revision History
</td>
</tr>

<tr>
<td colspan=2>
	<table border="0" cellspacing="5" cellpadding="5">
	<tr>
	<td><font size="-1"><b>Revision Number</b></font>
	<td><font size="-1"><b>Modified on</b></font>
	<td><font size="-1"><b>By</b></font>
	<td><font size="-1"><b>Note</b></font>	</td>
	</tr>
<?php
	// query to obtain a list of modifications
	
	if( isset($lrevision_id) )
	{
		$query = "SELECT user.last_name, user.first_name, log.modified_on, log.note, log.revision FROM log, user WHERE log.id = '$_REQUEST[id]' AND user.username = log.modified_by AND log.revision <= $lrevision_id ORDER BY log.modified_on DESC";
	}
	else
	{
		$query = "SELECT user.last_name, user.first_name, log.modified_on, log.note, log.revision FROM log, user WHERE log.id = '$_REQUEST[id]' AND user.username = log.modified_by ORDER BY log.modified_on DESC";
	}
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	$current_revision = mysql_num_rows($result) - 1;
	// iterate through resultset
	while(list($last_name, $first_name, $modified_on, $note, $revision_id) = mysql_fetch_row($result))
	{
?>
	<tr>
<?php
	$extra_message = '';
	if( is_file($GLOBALS['CONFIG']['revisionDir'] . $_REQUEST['id'] . '/' . $_REQUEST['id'] . "_$revision_id.dat") )
	{	echo '<td><font size="-1"> <a href="details.php?id=' . $_REQUEST['id'] . "_$revision_id" . '&state=' . ($_REQUEST['state']-1) . '">' . $revision_id . '</a>' . $extra_message; }
	else
	{	echo '<td><font size="-1">' . $revision_id . $extra_message; 	}
?>
	</font></td>
	<td><font size="-1"><?php echo fix_date($modified_on); ?></font></td>
	<td><font size="-1"><?php echo $last_name.', '.$first_name; ?></font></td>
	<td><font size="-1"><?php echo $note; ?></font></td>
	</tr>
<?php
	}
	// clean up
	mysql_free_result($result);
?>
	</table>
</td>
</tr>

</table>
</center>
<?php
draw_footer();
}
?>
