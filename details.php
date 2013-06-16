<?php

/*
  details.php - display file information  check for session
  Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
  Copyright (C) 2008-2013 Stephen Lawrence Jr.

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

session_start();
if (!isset($_SESSION['uid'])) {
    header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));
    exit;
}
include('odm-load.php');
include('udf_functions.php');

$last_message = isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '';

$full_requestId = $_REQUEST['id'];

// in case this file is accessed directly - check for $_REQUEST['id']
if (!isset($_REQUEST['id']) || $_REQUEST['id'] == "") {
    header('Location:error.php?ec=2');
    exit;
}

if (strchr($_REQUEST['id'], '_')) {
    list($_REQUEST['id'], $lrevision_id) = explode('_', $_REQUEST['id']);
    $pageTitle = msg('area_file_details') . ' ' . msg('revision') . ' #' . $lrevision_id;
    $filesize = display_filesize($GLOBALS['CONFIG']['revisionDir'] . $_REQUEST['id'] . '/' . $_REQUEST['id'] . '_' . $lrevision_id . '.dat');
} else {
    $pageTitle = msg('area_file_details');
}

draw_header(msg('area_file_details'), $last_message);

$lrequest_id = $_REQUEST['id']; //save an original copy of id

$filedata = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);
checkUserPermission($_REQUEST['id'], $filedata->VIEW_RIGHT, $filedata);
$user = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

$userPermObj = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
$user_obj = new user($filedata->getOwner(), $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;

// display details
$owner_id = $filedata->getOwner();
$category = $filedata->getCategoryName();
$owner_fullname = $filedata->getOwnerFullName();
$owner = $owner_fullname[1] . ', ' . $owner_fullname[0];
$realname = $filedata->getName();
$created = $filedata->getCreatedDate();
$description = $filedata->getDescription();
$comment = $filedata->getComment();
$status = $filedata->getStatus();
$reviewer = $filedata->getReviewerName();
// corrections
if ($description == '') {
    $description = msg('message_no_description_available');
}

if ($comment == '') {
    $comment = msg('message_no_author_comments_available');
}

$reviewer_comments_str = $filedata->getReviewerComments();
$reviewer_comments_fields = explode(';', $reviewer_comments_str);

for ($i = 0; $i < sizeof($reviewer_comments_fields); $i++) {
    $reviewer_comments_fields[$i] = str_replace('"', '&quot;', $reviewer_comments_fields[$i]);
    $reviewer_comments_fields[$i] = str_replace('\\', '', $reviewer_comments_fields[$i]);
}

// No subject? Give them the default
if (isset($reviewer_comments_fields[1]) && strlen($reviewer_comments_fields[1]) <= strlen('Subject=')) {
    $reviewer_comments_fields[1] = 'Subject=Comments regarding the review for your documentation';
}

// No To? Give them the default
if (isset($reviewer_comments_fields[0]) && strlen($reviewer_comments_fields[0]) <= strlen('to=')) {
    $reviewer_comments_fields[0] = 'To=Author(s)';
}

if ($filedata->isArchived()) {
    $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . '.dat';
    $filesize = display_filesize($filename);
} else {
    $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . '.dat';

    if (!isset($filesize)) {
        $filesize = display_filesize($filename);
    }
}

// display red or green icon depending on file status
if ($status == 0 && $user->canView($_REQUEST['id'])) {
    $file_unlocked = true;
} else {
    $file_unlocked = false;
}
//chm sahar
if (isset($lrevision_id)) {
    $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}user.last_name, 
        {$GLOBALS['CONFIG']['db_prefix']}user.first_name, 
        {$GLOBALS['CONFIG']['db_prefix']}log.modified_on, 
        {$GLOBALS['CONFIG']['db_prefix']}log.note, 
        {$GLOBALS['CONFIG']['db_prefix']}log.revision 
        FROM {$GLOBALS['CONFIG']['db_prefix']}log, {$GLOBALS['CONFIG']['db_prefix']}user 
        WHERE {$GLOBALS['CONFIG']['db_prefix']}log.id = '{$_REQUEST['id']}' 
        AND {$GLOBALS['CONFIG']['db_prefix']}user.username = {$GLOBALS['CONFIG']['db_prefix']}log.modified_by 
        AND {$GLOBALS['CONFIG']['db_prefix']}log.revision = $lrevision_id 
        ORDER BY {$GLOBALS['CONFIG']['db_prefix']}log.modified_on DESC";
} else {
    $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}user.last_name, 
        {$GLOBALS['CONFIG']['db_prefix']}user.first_name, 
        {$GLOBALS['CONFIG']['db_prefix']}log.modified_on, 
        {$GLOBALS['CONFIG']['db_prefix']}log.note, 
        {$GLOBALS['CONFIG']['db_prefix']}log.revision 
        FROM {$GLOBALS['CONFIG']['db_prefix']}log, {$GLOBALS['CONFIG']['db_prefix']}user 
        WHERE {$GLOBALS['CONFIG']['db_prefix']}log.id = '{$_REQUEST['id']}' 
        AND {$GLOBALS['CONFIG']['db_prefix']}user.username = {$GLOBALS['CONFIG']['db_prefix']}log.modified_by 
        ORDER BY {$GLOBALS['CONFIG']['db_prefix']}log.modified_on DESC";
}

$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
$rows = mysql_num_rows($result);
$revisionData = mysql_fetch_assoc($result);

if ($rows == 1 && !(isset($lrevision_id))) {
    $revision = "1";
} elseif (isset($lrevision_id)) {
    $revision = $lrevision_id + 1;
} else {
    $revision = "$rows";
}

$file_under_review = ( ($filedata->isPublishable() == -1) ? true : false);

$to_value = (isset($reviewer_comments_fields[0]) ? (substr($reviewer_comments_fields[0], 3)) : '');
$subject_value = (isset($reviewer_comments_fields[1]) ? (substr($reviewer_comments_fields[1],8)) : '');
$comments_value = (isset($reviewer_comments_fields[2]) ? (substr($reviewer_comments_fields[2], 9)) : '');

$file_detail = array(
    'file_unlocked' => $file_unlocked,
    'to_value' => $subject_value,
    'subject_value' => $subject_value,
    'comments_value' => $comments_value,
    'realname' => $realname,
    'category' => $category,
    'filesize' => $filesize,
    'created' => fix_date($created),
    'owner_email' => $user_obj->getEmailAddress(),
    'owner' => $owner,
    'owner_fullname' => $owner_fullname,
    'description' => wordwrap($description, 50, '<br />'),
    'comment' => wordwrap($comment, 50, '<br />'),
    'udf_details_display' => udf_details_display($lrequest_id),
    'revision' => $revision,
    'file_under_review' => $file_under_review,
    'reviewer' => $reviewer,
    'status' => $status
);

if ($status > 0) {
    // status != 0 -> file checked out to another user. status = uid of the check-out person
    // query to find out who...
    $checkout_person_obj = $filedata->getCheckerOBJ();
    $fullname = $checkout_person_obj->getFullName();

    $GLOBALS['smarty']->assign('checkout_person_full_name', $fullname);
    $GLOBALS['smarty']->assign('checkout_person_email', $checkout_person_obj->getEmailAddress());
}

// Can they Read?
if ($userPermObj->getAuthority($_REQUEST['id'], $filedata) >= $userPermObj->READ_RIGHT) {
    $view_link = $secureurl->encode("view_file.php?id=$full_requestId" . '&state=' . ($_REQUEST['state'] + 1));
    $GLOBALS['smarty']->assign('view_link', $view_link);
}

// Lets figure out which buttons to show
if ($status == 0 || ($status == -1 && $filedata->isOwner($_SESSION['uid']) )) {
    // status = 0 -> file available for checkout
    // check if user has modify rights
    $query2 = "SELECT status FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE {$GLOBALS['CONFIG']['db_prefix']}user_perms.fid = '$_REQUEST[id]' AND {$GLOBALS['CONFIG']['db_prefix']}user_perms.uid = '$_SESSION[uid]' AND {$GLOBALS['CONFIG']['db_prefix']}user_perms.rights = '2' AND {$GLOBALS['CONFIG']['db_prefix']}data.status = '0' AND {$GLOBALS['CONFIG']['db_prefix']}data.id = {$GLOBALS['CONFIG']['db_prefix']}user_perms.fid";
    $result2 = mysql_query($query2, $GLOBALS['connection']) or die("Error in query: $query2. " . mysql_error());
    $user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    if ($user_perms->getAuthority($_REQUEST['id'], $filedata) >= $user_perms->WRITE_RIGHT && !isset($lrevision_id) && !$filedata->isArchived()) {
        // if so, display link for checkout
        $check_out_link = $secureurl->encode("check-out.php?id=$lrequest_id" . '&state=' . ($_REQUEST['state'] + 1) . '&access_right=modify');
        $GLOBALS['smarty']->assign('check_out_link', $check_out_link);
    }

    mysql_free_result($result2);

    if ($userPermObj->getAuthority($_REQUEST['id'], $filedata) >= $userPermObj->ADMIN_RIGHT && !@isset($lrevision_id) && !$filedata->isArchived()) {
        // if user is also the owner of the file AND file is not checked out
        // additional actions are available 
        $edit_link = $secureurl->encode("edit.php?id=$_REQUEST[id]&state=" . ($_REQUEST['state'] + 1));
        $GLOBALS['smarty']->assign('edit_link', $edit_link);
    }
}

////end if ($status == 0)
// ability to view revision history is always available 
// put it outside the block
$history_link = $secureurl->encode("history.php?id=$lrequest_id&state=" . ($_REQUEST['state'] + 1));
$comments_link = $secureurl->encode('toBePublished.php?submit=comments&id=' . $_REQUEST['id']);
$my_delete_link = $secureurl->encode('delete.php?mode=tmpdel&id0=' . $_REQUEST['id']);

$GLOBALS['smarty']->assign('history_link', $history_link);
$GLOBALS['smarty']->assign('comments_link', $comments_link);
$GLOBALS['smarty']->assign('my_delete_link', $my_delete_link);

// Call the plugin API
callPluginMethod('onDuringDetails', $filedata->id);

$GLOBALS['smarty']->assign('file_detail', $file_detail);
display_smarty_template('details.tpl');

// Call the plugin API
callPluginMethod('onAfterDetails', $filedata->id);

draw_footer();
