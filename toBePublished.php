<?php
use Aura\Html\Escaper as e;

/*
toBePublished.php -  Display list of publishable files to reviewer
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen
Copyright (C) 2005-2013 Stephen Lawrence Jr.

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

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

require_once("AccessLog_class.php");

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isReviewer()) {
    header('Location:out.php?last_message=Access+denied');
}

$comments = isset($_REQUEST['comments']) ? stripslashes($_REQUEST['comments']) : '';

if (!isset($_REQUEST['submit'])) {
    draw_header(msg('message_documents_waiting'), $last_message);
    $userpermission = new UserPermission($_SESSION['uid'], $pdo);
  
    if ($user_obj->isAdmin()) {
        $id_array = $user_obj->getAllRevieweeIds();
    } else {
        $id_array = $user_obj->getRevieweeIds();
    }

    $list_status = list_files($id_array, $userpermission, $GLOBALS['CONFIG']['dataDir'], true);
    if ($list_status != -1) {
        display_smarty_template('toBePublished.tpl');
    }
} elseif (isset($_REQUEST['submit']) && ($_REQUEST['submit'] =='commentAuthorize' || $_REQUEST['submit'] == 'commentReject')) {
    if (!isset($_REQUEST['checkbox'])) {
        header('Location: toBePublished.php?last_message=' . urlencode(msg('message_you_did_not_enter_value')));
    }

    draw_header(msg('label_comment'), $last_message);
    
    $checkbox = isset($_REQUEST['checkbox']) ? $_REQUEST['checkbox'] : '';
/*    if($mode == 'reviewer')
    {
        $access_mode = 'enabled';
    }
    else
    {
        $access_mode = 'disabled';
    }

*/
    if ($_REQUEST['submit'] == 'commentReject') {
        $submit_value='Reject';
    } elseif ($_REQUEST['submit'] == 'commentAuthorize') {
        $submit_value='Authorize';
    } else {
        $submit_value='None';
    }

    $query = "
      SELECT
        id,
        first_name,
        last_name
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}user
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array());
    $result = $stmt->fetchAll();

    $GLOBALS['smarty']->assign('user_info', $result);
    $GLOBALS['smarty']->assign('submit_value', $submit_value);
    $GLOBALS['smarty']->assign('checkbox', $checkbox);
    display_smarty_template('commentform.tpl');
} elseif (isset($_POST['submit']) && $_POST['submit'] == 'Reject') {
    $to = isset($_POST['to']) ? e::h($_POST['to']) : '';
    $subject = isset($_POST['subject']) ? e::h($_POST['subject']) : '';
    $checkbox = isset($_POST['checkbox']) ? e::h($_POST['checkbox']) : '';

    $mail_break = '--------------------------------------------------'.PHP_EOL;
    $reviewer_comments = "To=$to;Subject=$subject;Comments=$comments;";
    $user_obj = new user($_SESSION['uid'], $pdo);
    $date = date('Y-m-d H:i:s T'); //locale insensitive
    $get_full_name = $user_obj->getFullName();
    $full_name = e::h($get_full_name[0]) .' '. e::h($get_full_name[1]);
    $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: " . e::h($mail_from) . PHP_EOL;
    $mail_headers .="Content-Type: text/plain; charset=UTF-8".PHP_EOL;
    $mail_subject= (!empty($_REQUEST['subject']) ? stripslashes(e::h($_REQUEST['subject'])) : msg('email_subject_review_status'));
    $mail_greeting=msg('email_greeting'). ":" . PHP_EOL . "\t" . msg('email_i_would_like_to_inform');
    $mail_body = $comments . PHP_EOL . PHP_EOL;
    $mail_body .= msg('email_was_declined_for_publishing_at') . ' ' .$date. ' ' . msg('email_for_the_following_reasons') . ':'. PHP_EOL . PHP_EOL . $mail_break . e::h($_REQUEST['comments']) . PHP_EOL . $mail_break;
    $mail_salute=PHP_EOL . PHP_EOL . msg('email_salute') . ",". PHP_EOL . $full_name;

    if ($user_obj->isAdmin()) {
        $id_array = $user_obj->getAllRevieweeIds();
    } else {
        $id_array = $user_obj->getRevieweeIds();
    }

    $id_field = explode(' ', trim($checkbox));
    foreach ($id_field as $key=>$value) {
        // Check to make sure the current file_id is in their list of rejectable ID's
        if (in_array($value, $id_array)) {
            $fileid = $value;
            $file_obj = new FileData($fileid, $pdo);
            $user_obj = new User($file_obj->getOwner(), $pdo);
            $mail_to = $user_obj->getEmailAddress();
            $dept_id = $file_obj->getDepartment();
            // Build email for author notification
            if (isset($_POST['send_to_users'][0]) && in_array('owner', $_POST['send_to_users'])) {
                // Lets unset this now so the new array will just be user_id's
                $_POST['send_to_users'] = array_slice($_POST['send_to_users'], 1);
                $mail_body1 = e::h($comments) . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_was_rejected_from_repository') . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_status') . ': ' . msg('message_authorized') . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_reviewer') . ': ' . e::h($full_name) . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
                $mail_body1.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;

                if ($GLOBALS['CONFIG']['demo'] == 'False') {
                    mail($mail_to, $mail_subject . ' ' . $file_obj->getName(), ($mail_greeting . $file_obj->getName() . ' ' . $mail_body1 . $mail_salute), $mail_headers);
                }
            }
            
            $file_obj->Publishable(-1);
            $file_obj->setReviewerComments($reviewer_comments);
            AccessLog::addLogEntry($fileid, 'R', $pdo);
            // Set up rejected email message to sent out
            $mail_subject = (!empty($_REQUEST['subject']) ? stripslashes(e::h($_REQUEST['subject'])) : msg('email_a_new_file_has_been_rejected'));
            $mail_body = e::h($comments) . PHP_EOL . PHP_EOL;
            $mail_body.=msg('email_a_new_file_has_been_rejected').PHP_EOL . PHP_EOL;
            $mail_body.=msg('label_filename'). ':  ' .$file_obj->getName() . PHP_EOL . PHP_EOL;
            $mail_body.=msg('label_status').': ' .msg('message_rejected'). PHP_EOL . PHP_EOL;
            $mail_body.=msg('date'). ': ' .$date. PHP_EOL . PHP_EOL;
            $mail_body.=msg('label_reviewer'). ': ' . e::h($full_name) . PHP_EOL . PHP_EOL;
            $mail_body.=msg('email_thank_you'). ','. PHP_EOL . PHP_EOL;
            $mail_body.=msg('email_automated_document_messenger'). PHP_EOL . PHP_EOL;
            $mail_body.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;

            if (isset($_POST['send_to_all'])) {
                email_all($mail_subject, $mail_body, $mail_headers);
            }

            if (isset($_POST['send_to_dept'])) {
                email_dept($dept_id, $mail_subject, $mail_body, $mail_headers);
            }

            if (isset($_POST['send_to_users']) && is_array($_POST['send_to_users']) && isset($_POST['send_to_users'][0])) {
                email_users_id($_POST['send_to_users'], $mail_subject, $mail_body, $mail_headers);
            }
        } else {
            // If their user cannot reject this file_id, display error
            header("Location:toBePublished.php?last_message=" .urlencode(msg('message_error_performing_action')));
        }
    }
    header("Location: out.php?last_message=" .urlencode(msg('message_file_rejected')));
} elseif (isset($_POST['submit']) && $_POST['submit'] == 'Authorize') {
    $checkbox = isset($_REQUEST['checkbox']) ? e::h($_REQUEST['checkbox']) : '';
    $reviewer_comments = "To= " . e::h($_POST['to']) . ";Subject=" . e::h($_POST['subject']) . ";Comments=" . e::h($_POST['comments']) . ";";
    $user_obj = new User($_SESSION['uid'], $pdo);
    $date = date('Y-m-d H:i:s T'); //locale insensitive
    $get_full_name = $user_obj->getFullName();
    $full_name = $get_full_name[0].' '.$get_full_name[1];
    $mail_subject = (!empty($_REQUEST['subject']) ? stripslashes(e::h($_REQUEST['subject'])) : msg('email_subject_review_status'));
    $mail_from= e::h($full_name) . ' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: ". e::h($mail_from) .PHP_EOL.PHP_EOL;
    $mail_headers .="Content-Type: text/plain; charset=UTF-8".PHP_EOL . PHP_EOL;

    if ($user_obj->isAdmin()) {
        $id_array = $user_obj->getAllRevieweeIds();
    } else {
        $id_array = $user_obj->getRevieweeIds();
    }


    $id_field=explode(' ', trim($checkbox));
    foreach ($id_field as $key=>$value) {
        // Check to make sure the current file_id is in their list of reviewable ID's
        if (in_array($value, $id_array)) {
            $fileid = $value;
            $file_obj = new FileData($fileid, $pdo);
            $user_obj = new User($file_obj->getOwner(), $pdo);
            $mail_to = $user_obj->getEmailAddress();
            $dept_id = $file_obj->getDepartment();
            
            // Build email for author notification
            if (isset($_POST['send_to_users'][0]) && in_array('owner', $_POST['send_to_users'])) {
                // Lets unset this now so the new array will just be user_id's
                $_POST['send_to_users'] = array_slice($_POST['send_to_users'], 1);

                $mail_body1 = e::h($comments) . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_your_file_has_been_authorized') . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_status') . ': ' . msg('message_authorized') . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('label_reviewer') . ': ' . e::h($full_name) . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
                $mail_body1.=msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
                $mail_body1.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
                if ($GLOBALS['CONFIG']['demo'] == 'False')
                {
                    mail($mail_to, $mail_subject . " " . $file_obj->getName(), $mail_body1, $mail_headers);
                }
            }
            
            $file_obj->Publishable(1);
            $file_obj->setReviewerComments($reviewer_comments);
            AccessLog::addLogEntry($fileid, 'Y', $pdo);
            
            // Build email for general notices
            $mail_subject = (!empty($_REQUEST['subject']) ? stripslashes(e::h($_REQUEST['subject'])) : $file_obj->getName().' ' .msg('email_added_to_repository'));
            $mail_body2=$comments . PHP_EOL . PHP_EOL;
            $mail_body2.=msg('email_a_new_file_has_been_added'). PHP_EOL . PHP_EOL;
            $mail_body2.=msg('label_filename'). ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
            $mail_body2.=msg('label_status'). ': New'. PHP_EOL . PHP_EOL;
            $mail_body2.=msg('date'). ': ' . $date . PHP_EOL . PHP_EOL;
            $mail_body2.=msg('label_reviewer'). ': ' . e::h($full_name) . PHP_EOL . PHP_EOL;
            $mail_body2.=msg('email_thank_you'). ','. PHP_EOL . PHP_EOL;
            $mail_body2.=msg('email_automated_document_messenger'). PHP_EOL . PHP_EOL;
            $mail_body2.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;

            if (isset($_POST['send_to_all'])) {
                email_all($mail_subject, $mail_body2, $mail_headers);
            }
            
            if (isset($_POST['send_to_dept'])) {
                email_dept($dept_id, $mail_subject, $mail_body2, $mail_headers);
            }
            if (!empty($_POST['send_to_users'][0]) && is_array($_POST['send_to_users']) && $_POST['send_to_users'][0] > 0) {
                email_users_id($_POST['send_to_users'], $mail_subject, $mail_body2, $mail_headers);
            }
        } else {
            // If their user cannot authorize this file_id, display error
            header("Location:toBePublished.php?last_message=" .urlencode(msg('message_error_performing_action')));
        }
    }
    header('Location: out.php?last_message=' .urlencode(msg('message_file_authorized')));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'comments' && isset($_REQUEST['id'])) {
    /*
     * Used to display the reviewer comments in a popup
     */
    $file_id = (int) $_REQUEST['id'];
    $file_obj = new FileData($file_id, $pdo);
    echo $file_obj->getReviewerComments();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Cancel') {
    $last_message=urlencode(msg('message_action_cancelled'));
    header('Location: toBePublished.php?last_message=' . urlencode($last_message));
}
    draw_footer();
