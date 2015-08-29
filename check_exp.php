<?php
/*
check_exp.php - check to see if files need to be re-authorized
Copyright (C) 2004 Stephen Lawrence, Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.

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
$start_time = time();
session_start();

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

// includes
draw_header(msg('area_file_expiration'), $last_message);

// Look up user
$user_query = "
  SELECT
    id
  FROM
    {$GLOBALS['CONFIG']['db_prefix']}user
  WHERE
    id = :root_id
";
$stmt = $pdo->prepare($user_query);
$stmt->execute(array(
    ':root_id' => $GLOBALS['CONFIG']['root_id']
));
$user_result = $stmt->fetch();

if ($stmt->rowCount() != 1) {
    header('location:error.php?ec=22');
} else {
    $root_id = $user_result['id'];
}
// calculate current date
$current_date = date('Y-m-d');
$current_year = intval(date('Y)'));
$current_month = intval(date('m'));
$current_day = intval(date('d'));

// calculate revision_exp into year, month, and day
$exp_years = floor($GLOBALS['CONFIG']['revision_expiration']/365);
$remainder = $GLOBALS['CONFIG']['revision_expiration'] - $exp_years*365;
$exp_months = floor($remainder/30);
$exp_days = $remainder -  $exp_months*30;

// calculate oldest non-expired date
if ($current_day < $exp_days) {
    --$current_month;
    $current_day += 30;
}
$ok_day = $current_day - $exp_days;
if ($current_month < $exp_months) {
    --$current_year;
    $current_month += 12;
}
$ok_month = $current_month - $exp_months;
$ok_year = $current_year - $exp_years;

$expired_revision = date('Y-m-d', mktime(0, 0, 0, $ok_month, $ok_day, $ok_year));

//get expired file
$data_query = "
  SELECT
    d.id,
    d.reviewer_comments
  FROM
    {$GLOBALS['CONFIG']['db_prefix']}data d,
    {$GLOBALS['CONFIG']['db_prefix']}log l
  WHERE
    d.id = l.id
  AND
    l.revision = 'current'
  AND
    modified_on < :expired_revision
  AND (
    d.publishable != -1
  AND
    d.status != -1
  )
";
$stmt = $pdo->prepare($data_query);
$stmt->execute(array(
    ':expired_revision' => $expired_revision
));
$data_result = $stmt->fetchAll();

echo msg('message_rejecting_files'). ' ' . $expired_revision . '<br>';
echo msg('message_rejected') . ' ' . $stmt->rowCount() . ' file(s)<br>';
$count = 0;
foreach ($data_result as $row) {
    echo '&nbsp;&nbsp;' . $count . ' File ID: ' . $row['id'] . '<br>';
    $count++;
}
// Notify owner
if ($GLOBALS['CONFIG']['file_expired_action'] != 4) {
    $reviewer_comments = 'To=' . msg('author') . ';Subject=' . msg('message_file_expired') . ';Comments=' . msg('email_file_was_rejected_because'). ' ' . $GLOBALS['CONFIG']['revision_expiration'] . ' ' .msg('days') . ';';
    $user_obj = new user($root_id, $pdo);
    $date = date("D F d Y");
    $time = date("h:i A");
    $get_full_name = $user_obj->getFullName();
    $full_name = $get_full_name[0].' '.$get_full_name[1];
    $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: $mail_from";
    $mail_subject=msg('email_subject_review_status');
    $mail_greeting=msg('email_greeting') . ":". PHP_EOL . "\t" . msg('email_i_would_like_to_inform');
    $mail_body = msg('email_was_declined_for_publishing_at') . ' ' .$time.' on '.$date.' ' . msg('email_because_you_did_not_revise') . ' ' . $GLOBALS['CONFIG']['revision_expiration'] . ' '. msg('days');
    $mail_salute=PHP_EOL . PHP_EOL . msg('email_salute') . ",". PHP_EOL . $full_name;
    foreach($data_result as $row) {
        $file_obj = new FileData($row['id'], $pdo);
        $user_obj = new User($file_obj->getOwner(), $pdo);
        $mail_to = $user_obj->getEmailAddress();
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($mail_to, $mail_subject . $file_obj->getName(), ($mail_greeting . $file_obj->getName() . ' ' . $mail_body . $mail_salute), $mail_headers);
        }
    }
}

//do not show file
if ($GLOBALS['CONFIG']['file_expired_action'] == 1) {
    $reviewer_comments = 'To=' . msg('author') . ';Subject=' . msg('message_file_expired') . ';Comments=' . msg('email_file_was_rejected_because'). ' ' .$GLOBALS['CONFIG']['revision_expiration'] . ' ' . msg('days');
    foreach ($data_result as $row) {
        $file_obj = new FileData($row['id'], $pdo);
        $file_obj->Publishable(-1);
        $file_obj->setReviewerComments($reviewer_comments);
    }
}

//lock file, not check-outable
if ($GLOBALS['CONFIG']['file_expired_action'] == 2) {
    foreach ($data_result as $row) {
        $file_obj = new FileData($row['id'], $pdo);
        $file_obj->setStatus(-1);
    }
}
echo msg('message_all_actions_successfull');
draw_footer();
