<?php
/*
toBePublished.php -  Display list of publishable files to reviewer
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen
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

session_start();

include('odm-load.php');
require_once("AccessLog_class.php");

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset ($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
    exit;
}

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
if(!$user_obj->isReviewer() && !$user_obj->isAdmin())
{
    header('Location:out.php?last_message=Access+denied');
}

$lcomments = isset($_REQUEST['comments']) ? stripslashes($_REQUEST['comments']) : '';

if(!isset($_REQUEST['submit']))
{
    draw_header(msg('message_documents_waiting'), $last_message);
    $userpermission = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
  
    if($user_obj->isAdmin())
    {
        $id_array = $user_obj->getAllRevieweeIds();
    }
    else
    {
        $id_array = $user_obj->getRevieweeIds();
    }

    $list_status = list_files($id_array, $userpermission, $GLOBALS['CONFIG']['dataDir'], true);
    if( $list_status != -1 )
    {
        display_smarty_template('toBePublished.tpl');
    }
}
elseif(isset($_REQUEST['submit']) && ($_REQUEST['submit'] =='commentAuthorize' || $_REQUEST['submit'] == 'commentReject'))
{
    if(!isset($_REQUEST['checkbox']))
    {
        header('Location: ' .$_SERVER['PHP_SELF'] . '?last_message=' . urlencode(msg('message_you_did_not_enter_value')));
    }

    draw_header(msg('label_comment'), $last_message);
    
    $lcheckbox = isset($_REQUEST['checkbox']) ? $_REQUEST['checkbox'] : '';
/*    if($mode == 'reviewer')
    {
        $access_mode = 'enabled';
    }
    else
    {
        $access_mode = 'disabled';
    }

*/
    if($_REQUEST['submit'] == 'commentReject')
    {
        $submit_value='Reject';
    }
    elseif ($_REQUEST['submit'] == 'commentAuthorize')
    {
        $submit_value='Authorize';
    }
    else
    {
        $submit_value='None';
    }

    $query = "SELECT id, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query . " . mysql_error());
    $count = mysql_num_rows($result);
    $index = 0;
    while($index < $count)
    {
        $user_info[] = list($id, $first_name, $last_name) = mysql_fetch_array($result);
        $index++;
    }

    $GLOBALS['smarty']->assign('user_info',$user_info);
    $GLOBALS['smarty']->assign('submit_value',$submit_value);
    $GLOBALS['smarty']->assign('checkbox',$lcheckbox);
    display_smarty_template('commentform.tpl');

}
elseif (isset($_POST['submit']) && $_POST['submit'] == 'Reject')
{  
    $lto = isset($_POST['to'])?$_POST['to'] : '';
    $lsubject = isset($_POST['subject'])?$_POST['subject'] : '';
    $lcheckbox = isset($_POST['checkbox'])?$_POST['checkbox'] : '';

    $mail_break = '--------------------------------------------------'."\n";
    $reviewer_comments = "To=$lto;Subject=$lsubject;Comments=$lcomments;";
    $user_obj = new user($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    $date = date('Y-m-d H:i:s T'); //locale insensitive
    $get_full_name = $user_obj->getFullName();
    $dept_id = $user_obj->getDeptId();
    $full_name = $get_full_name[0].' '.$get_full_name[1];
    $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: $mail_from"."\r\n";
    $mail_headers .="Content-Type: text/plain; charset=UTF-8"."\r\n";
    $mail_subject= (isset($_REQUEST['subject']) ? stripslashes($_REQUEST['subject']) : msg('email_subject_review_status'));
    $mail_greeting=msg('email_greeting'). ":\n\r\t" . msg('email_i_would_like_to_inform');
    $mail_body = $lcomments . "\n\n";
    $mail_body .= msg('email_was_declined_for_publishing_at') . ' ' .$date. ' ' . msg('email_for_the_following_reasons') . ':'."\n\n".$mail_break.$_REQUEST['comments']."\n".$mail_break;
    $mail_salute="\n\r\n\r" . msg('email_salute') . ",\n\r$full_name";

    if($user_obj->isAdmin())
    {
        $id_array = $user_obj->getAllRevieweeIds();
    }
    else
    {
        $id_array = $user_obj->getRevieweeIds();
    }

    $idfield=explode(' ',trim($lcheckbox));
    foreach($idfield as $key=>$value)
    {
        // Check to make sure the current file_id is in their list of rejectable ID's
        if(in_array($value, $id_array))
        {
            $fileid = $value;
            $file_obj = new FileData($fileid, $GLOBALS['connection'], DB_NAME);
            $user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], DB_NAME);
            $mail_to = $user_obj->getEmailAddress();
            mail($mail_to, $mail_subject . ' ' . $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
            $file_obj->Publishable(-1);
            $file_obj->setReviewerComments($reviewer_comments);
            AccessLog::addLogEntry($fileid,'R');
            // Set up rejected email message to sent out
            $mail_subject= isset($_REQUEST['subject']) ? stripslashes($_REQUEST['subject']) : $file_obj->getName().' ' . msg('email_was_rejected_from_repository');
            $mail_body = $lcomments . "\n\n";
            $mail_body.=msg('email_a_new_file_has_been_rejected')."\n\n";
            $mail_body.=msg('label_filename'). ':  ' .$file_obj->getName() . "\n\n";
            $mail_body.=msg('label_status').': ' .msg('message_rejected'). "\n\n";
            $mail_body.=msg('date'). ': ' .$date. "\n\n";
            $mail_body.=msg('label_reviewer'). ': ' .$full_name. "\n\n";
            $mail_body.=msg('email_thank_you'). ','. "\n\n";
            $mail_body.=msg('email_automated_document_messenger'). "\n\n";
            $mail_body.=$GLOBALS['CONFIG']['base_url'] . "\n\n";

            if(isset($_POST['send_to_all']))
            {
                email_all($mail_from,$mail_subject,$mail_body,$mail_headers);
            }

            if(isset($_POST['send_to_dept']))
            {
                email_dept($mail_from, $dept_id,$mail_subject ,$mail_body,$mail_headers);
            }

            if(isset($_POST['send_to_users']) && sizeof($_POST['send_to_users']) > 0 && $_POST['send_to_users'][0]!= 0)
            {
                email_users_id($mail_from, $_POST['send_to_users'], $mail_subject,$mail_body,$mail_headers);
            }
        }
        else
        {
            // If their user cannot reject this file_id, display error
            header("Location:$_SERVER[PHP_SELF]?last_message=" .urlencode(msg('message_error_performing_action')));
        }
    }
    header("Location: out.php?last_message=" .urlencode(msg('message_file_rejected')));
}
elseif (isset($_POST['submit']) && $_POST['submit'] == 'Authorize')
{         
    $lcheckbox = isset($_REQUEST['checkbox']) ? $_REQUEST['checkbox'] : '';
    $reviewer_comments = "To=$_POST[to];Subject=$_POST[subject];Comments=$_POST[comments];";
    $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    $date = date('Y-m-d H:i:s T'); //locale insensitive
    $get_full_name = $user_obj->getFullName();
    $full_name = $get_full_name[0].' '.$get_full_name[1];
    $mail_subject = (isset($_REQUEST['subject']) ? stripslashes($_REQUEST['subject']) : msg('email_subject_review_status'));
    $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: $mail_from"."\r\n";
    $mail_headers .="Content-Type: text/plain; charset=UTF-8"."\r\n";
    $dept_id = $user_obj->getDeptId();

    if($user_obj->isAdmin())
    {
        $id_array = $user_obj->getAllRevieweeIds();
    }
    else
    {
        $id_array = $user_obj->getRevieweeIds();
    }


    $idfield=explode(' ',trim($lcheckbox));
    foreach($idfield as $key=>$value)
    {
        // Check to make sure the current file_id is in their list of reviewable ID's
        if(in_array($value, $id_array))
        {
            $fileid = $value;
            $file_obj = new FileData($fileid, $GLOBALS['connection'], DB_NAME);            
            $user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], DB_NAME);
            $mail_to = $user_obj->getEmailAddress();
            
            // Build email for author notification
            if(isset($_POST['send_to_users'][0]) && in_array('owner', $_POST['send_to_users']))
            {
                // Lets unset this now so the new array will just be user_id's
                $_POST['send_to_users'] = array_slice($_POST['send_to_users'], 1);
                $mail_body1 = $lcomments . "\n\n";
                $mail_body1.=msg('email_your_file_has_been_authorized') . "\n\n";
                $mail_body1.=msg('label_filename') . ':  ' . $file_obj->getName() . "\n\n";
                $mail_body1.=msg('label_status') . ': ' . msg('message_authorized') . "\n\n";
                $mail_body1.=msg('date') . ': ' . $date . "\n\n";
                $mail_body1.=msg('label_reviewer') . ': ' . $full_name . "\n\n";
                $mail_body1.=msg('email_thank_you') . ',' . "\n\n";
                $mail_body1.=msg('email_automated_document_messenger') . "\n\n";
                $mail_body1.=$GLOBALS['CONFIG']['base_url'] . "\n\n";
                mail($mail_to, $mail_subject . " " . $file_obj->getName(), $mail_body1, $mail_headers);               
            }
            
            $file_obj->Publishable(1);
            $file_obj->setReviewerComments($reviewer_comments);
            AccessLog::addLogEntry($fileid,'Y');
            
            // Build email for general notices
            $mail_subject = (isset($_REQUEST['subject']) ? stripslashes($_REQUEST['subject']) : $file_obj->getName().' ' .msg('email_added_to_repository'));
            $mail_body2=$lcomments . "\n\n";
            $mail_body2.=msg('email_a_new_file_has_been_added'). "\n\n";
            $mail_body2.=msg('label_filename'). ':  ' . $file_obj->getName() . "\n\n";
            $mail_body2.=msg('label_status'). ': New'. "\n\n";
            $mail_body2.=msg('date'). ': ' . $date . "\n\n";
            $mail_body2.=msg('label_reviewer'). ': ' . $full_name . "\n\n";
            $mail_body2.=msg('email_thank_you'). ','. "\n\n";
            $mail_body2.=msg('email_automated_document_messenger'). "\n\n";
            $mail_body2.=$GLOBALS['CONFIG']['base_url'] . "\n\n";

            if(isset($_POST['send_to_all']))
            {
                email_all($mail_from,$mail_subject,$mail_body2,$mail_headers);
            }
            if(isset($_POST['send_to_dept']))
            {
                email_dept($mail_from, $dept_id,$mail_subject ,$mail_body2,$mail_headers);
            }
            if(isset($_POST['send_to_users']) && sizeof($_POST['send_to_users']) > 0)
            {                     
                email_users_id($mail_from, $_POST['send_to_users'], $mail_subject,$mail_body2,$mail_headers);
            }           
        }
        else
        {
            // If their user cannot authorize this file_id, display error
            header("Location:$_SERVER[PHP_SELF]?last_message=" .urlencode(msg('message_error_performing_action')));
        }
    }
    header('Location: out.php?last_message=' .urlencode(msg('message_file_authorized')));
}
elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Cancel')
{
    $last_message=urlencode(msg('message_action_cancelled'));
    header ('Location: toBePublished.php?last_message=' . $last_message);
}
    draw_footer();