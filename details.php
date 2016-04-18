<?php
use Aura\Html\Escaper as e;
/*
  details.php - display file information  check for session
  Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
  Copyright (C) 2008-2015 Stephen Lawrence Jr.

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

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

include('udf_functions.php');

$last_message = isset($_GET['last_message']) ? $_GET['last_message'] : '';

// in case this file is accessed directly - check for $_GET['id']
if (!isset($_GET['id']) || $_GET['id'] == "") {
    header('Location:error.php?ec=2');
    exit;
}

$full_requestId = $_GET['id'];

if (strchr($_GET['id'], '_')) {
    list($_GET['id'], $revision_id) = explode('_', $_GET['id']);
    $pageTitle = msg('area_file_details') . ' ' . msg('revision') . ' #' . $revision_id;
    $file_size = display_filesize($GLOBALS['CONFIG']['revisionDir'] . $_GET['id'] . '/' . $_GET['id'] . '_' . $revision_id . '.dat');
} else {
    $pageTitle = msg('area_file_details');
}

draw_header(msg('area_file_details'), $last_message);

$request_id = (int) $_GET['id']; //save an original copy of id
$state = isset($_GET['state']) ? (int) $_GET['state'] : 0;

$file_data_obj = new FileData($request_id, $pdo);
checkUserPermission($request_id, $file_data_obj->VIEW_RIGHT, $file_data_obj);
$user_perms_obj = new User_Perms($_SESSION['uid'], $pdo);

$user_permission_obj = new UserPermission($_SESSION['uid'], $pdo);
$user_obj = new User($file_data_obj->getOwner(), $pdo);

$owner_full_name = $file_data_obj->getOwnerFullName();

// display details
$owner_id = $file_data_obj->getOwner();
$category = $file_data_obj->getCategoryName();
$owner_last_first = $owner_full_name[1] . ', ' . $owner_full_name[0];
$owner_first_last = $owner_full_name[0] . ' ' . $owner_full_name[1];
$real_name = $file_data_obj->getName();
$created = $file_data_obj->getCreatedDate();
$description = $file_data_obj->getDescription();
$comment = $file_data_obj->getComment();
$status = $file_data_obj->getStatus();
$reviewer = $file_data_obj->getReviewerName();
// corrections
if ($description == '') {
    $description = msg('message_no_description_available');
}

if ($comment == '') {
    $comment = msg('message_no_author_comments_available');
}

$reviewer_comments_str = $file_data_obj->getReviewerComments();
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

if ($file_data_obj->isArchived()) {
    $filename = $GLOBALS['CONFIG']['archiveDir'] . $request_id . '.dat';
    $file_size = display_filesize($filename);
} else {
    $filename = $GLOBALS['CONFIG']['dataDir'] . $request_id . '.dat';

    if (!isset($file_size)) {
        $file_size = display_filesize($filename);
    }
}

// display red or green icon depending on file status
if ($status == 0 && $user_perms_obj->canView($request_id)) {
    $file_unlocked = true;
} else {
    $file_unlocked = false;
}
//chm sahar
if (!empty($revision_id)) {
    $query = "
        SELECT
          u.last_name,
          u.first_name,
          l.modified_on,
          l.note,
          l.revision
        FROM
          {$GLOBALS['CONFIG']['db_prefix']}log l,
          {$GLOBALS['CONFIG']['db_prefix']}user u
        WHERE
          l.id = :log_id
        AND
          u.username = l.modified_by
        AND
          l.revision = :revision_id
        ORDER BY
          l.modified_on DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':log_id' => $request_id,
        ':revision_id' => $revision_id
    ));
    $revisionData = $stmt->fetchAll();
} else {
    $query = "
      SELECT
        u.last_name,
        u.first_name,
        l.modified_on,
        l.note,
        l.revision
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}log l,
        {$GLOBALS['CONFIG']['db_prefix']}user u
        WHERE
          l.id = :log_id
        AND
          u.username = l.modified_by
        ORDER BY
          l.modified_on DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':log_id' => $request_id
    ));
    $revisionData = $stmt->fetchAll();
}

$rows = $stmt->rowCount();

if ($rows == 1 && !(isset($revision_id))) {
    $revision = "1";
} elseif (isset($revision_id)) {
    $revision = $revision_id + 1;
} else {
    $revision = "$rows";
}

$file_under_review = (($file_data_obj->isPublishable() == -1) ? true : false);

$to_value = (isset($reviewer_comments_fields[0]) ? (substr($reviewer_comments_fields[0], 3)) : '');
$subject_value = (isset($reviewer_comments_fields[1]) ? (substr($reviewer_comments_fields[1], 8)) : '');
$comments_value = (isset($reviewer_comments_fields[2]) ? (substr($reviewer_comments_fields[2], 9)) : '');

$file_detail_array = array(
    'file_unlocked' => $file_unlocked,
    'to_value' => $to_value,
    'subject_value' => $subject_value,
    'comments_value' => $comments_value,
    'realname' => $real_name,
    'category' => $category,
    'filesize' => $file_size,
    'created' => fix_date($created),
    'owner_email' => $user_obj->getEmailAddress(),
    'owner' => $owner_last_first,
    'owner_fullname' => $owner_first_last,
    'description' => wordwrap($description, 50, '<br />'),
    'comment' => wordwrap($comment, 50, '<br />'),
    'udf_details_display' => udf_details_display($request_id),
    'revision' => $revision,
    'file_under_review' => $file_under_review,
    'reviewer' => $reviewer,
    'status' => $status
);

if ($status > 0) {
    // status != 0 -> file checked out to another user. status = uid of the check-out person
    // query to find out who...
    $checkout_person_obj = $file_data_obj->getCheckerOBJ();
    $full_name = $checkout_person_obj->getFullName();

    $GLOBALS['smarty']->assign('checkout_person_full_name', $full_name);
    $GLOBALS['smarty']->assign('checkout_person_email', $checkout_person_obj->getEmailAddress());
}

// Can they Read?
if ($user_permission_obj->getAuthority($request_id, $file_data_obj) >= $user_permission_obj->READ_RIGHT) {
    $view_link = 'view_file.php?id=' . e::h($full_requestId) . '&state=' . ($state + 1);
    $GLOBALS['smarty']->assign('view_link', $view_link);
}

// Lets figure out which buttons to show
if ($status == 0 || ($status == -1 && $file_data_obj->isOwner($_SESSION['uid']))) {
    // check if user has modify rights

    $user_perms = new UserPermission($_SESSION['uid'], $GLOBALS['pdo']);
    if ($user_perms->getAuthority($request_id, $file_data_obj) >= $user_perms->WRITE_RIGHT && !isset($revision_id) && !$file_data_obj->isArchived()) {
        // if so, display link for checkout
        $check_out_link = "check-out.php?id=$request_id" . '&state=' . ($state + 1) . '&access_right=modify';
        $GLOBALS['smarty']->assign('check_out_link', $check_out_link);
    }


    if ($user_permission_obj->getAuthority($request_id, $file_data_obj) >= $user_permission_obj->ADMIN_RIGHT && !@isset($revision_id) && !$file_data_obj->isArchived()) {
        // if user is also the owner of the file AND file is not checked out
        // additional actions are available 
        $edit_link = "edit.php?id=$request_id&state=" . ($state + 1);
        $GLOBALS['smarty']->assign('edit_link', $edit_link);
    }
}

////end if ($status == 0)
// ability to view revision history is always available 
// put it outside the block
$history_link = "history.php?id=$request_id&state=" . ($state + 1);
$comments_link = 'toBePublished.php?submit=comments&id=' . $request_id;
$my_delete_link = 'delete.php?mode=tmpdel&id0=' . $request_id;

$GLOBALS['smarty']->assign('history_link', $history_link);
$GLOBALS['smarty']->assign('comments_link', $comments_link);
$GLOBALS['smarty']->assign('my_delete_link', $my_delete_link);

// Call the plugin API
callPluginMethod('onDuringDetails', $file_data_obj->id);

$GLOBALS['smarty']->assign('file_detail', $file_detail_array);
display_smarty_template('details.tpl');

// Call the plugin API
callPluginMethod('onAfterDetails', $file_data_obj->id);

draw_footer();
