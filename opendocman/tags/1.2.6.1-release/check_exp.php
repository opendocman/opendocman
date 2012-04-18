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
$lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}user where id='{$GLOBALS['CONFIG']['root_id']}'";
$lresult = mysql_query($lquery) or die('Error querying' . mysql_error());
if(mysql_num_rows($lresult) != 1)
{	
    header('location:error.php?ec=22');
}
else
{	
    list($lroot_id) = mysql_fetch_row($lresult);
}
// calculate current date
$lcurrent_date = date ('Y-m-d');
$lcurrent_year = intval(date('Y)'));
$lcurrent_month = intval(date('m'));
$lcurrent_day = intval(date('d'));

// calculate revision_exp into year, month, and day
$lexp_years = floor($GLOBALS['CONFIG']['revision_expiration']/365);
$lremainder = $GLOBALS['CONFIG']['revision_expiration'] - $lexp_years*365;
$lexp_months = floor($lremainder/30);
$lexp_days = $lremainder -  $lexp_months*30;

// calculate oldest non-expired date
if($lcurrent_day < $lexp_days)
{
    --$lcurrent_month;
    $lcurrent_day += 30;
}
$lok_day = $lcurrent_day - $lexp_days;
if($lcurrent_month < $lexp_months)
{
    --$lcurrent_year;
    $lcurrent_month += 12;
}
$lok_month = $lcurrent_month - $lexp_months;
$lok_year = $lcurrent_year - $lexp_years;

$lexpired_revision = date('Y-m-d', mktime(0, 0, 0, $lok_month, $lok_day, $lok_year));

//get expired file
$lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id, {$GLOBALS['CONFIG']['db_prefix']}data.reviewer_comments FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}log WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = {$GLOBALS['CONFIG']['db_prefix']}log.id AND {$GLOBALS['CONFIG']['db_prefix']}log.revision='current' AND modified_on<'$lexpired_revision' AND ({$GLOBALS['CONFIG']['db_prefix']}data.publishable!=-1 and {$GLOBALS['CONFIG']['db_prefix']}data.status!=-1)";
$lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
echo msg('message_rejecting_files'). ' ' . $lexpired_revision . '<br>';
echo msg('message_rejected') . ' ' . mysql_num_rows($lresult) . ' file(s)<br>';
for($i = 0; $i<mysql_num_rows($lresult); $i++)
{
    list($lid) = mysql_fetch_row($lresult);
    echo '&nbsp;&nbsp;' . $i . ' File ID: ' . $lid . '<br>';
}
// Notify owner
if($GLOBALS['CONFIG']['file_expired_action'] != 4)
{
    //get root's id
    $lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
    $reviewer_comments = 'To=' . msg('author') . ';Subject=' . msg('message_file_expired') . ';Comments=' . msg('email_file_was_rejected_because'). ' ' . $GLOBALS['CONFIG']['revision_expiration'] . ' ' .msg('days') . ';';
    $user_obj = new user($lroot_id, $GLOBALS['connection'], DB_NAME);
    $date = date("D F d Y");
    $time = date("h:i A");
    $get_full_name = $user_obj->getFullName();
    $full_name = $get_full_name[0].' '.$get_full_name[1];
    $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
    $mail_headers = "From: $mail_from";
    $mail_subject=msg('email_subject_review_status');
    $mail_greeting=msg('email_greeting') . ":\n\r\t" . msg('email_i_would_like_to_inform');
    $mail_body = msg('email_was_declined_for_publishing_at') . ' ' .$time.' on '.$date.' ' . msg('email_because_you_did_not_revise') . ' ' . $GLOBALS['CONFIG']['revision_expiration'] . ' '. msg('days');
    $mail_salute="\n\r\n\r" . msg('email_salute') . ",\n\r$full_name";
    for($i = 0; $i<mysql_num_rows($lresult); $i++)
    {
        list($lid) = mysql_fetch_row($lresult);
        $file_obj = new FileData($lid, $GLOBALS['connection'], DB_NAME);
        $user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], DB_NAME);
        $mail_to = $user_obj->getEmailAddress();
        mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
    }
}
if($GLOBALS['CONFIG']['file_expired_action'] == 1 ) //do not show file

{
    $lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
    $reviewer_comments = 'To=' . msg('author') . ';Subject=' . msg('message_file_expired') . ';Comments=' . msg('email_file_was_rejected_because'). ' ' .$GLOBALS['CONFIG']['revision_expiration'] . ' ' . msg('days');
    for($i = 0; $i<mysql_num_rows($lresult); $i++)
    {
        list($lid) = mysql_fetch_row($lresult);
        $file_obj = new FileData($lid, $GLOBALS['connection'], DB_NAME);
        $file_obj->Publishable(-1);
        $file_obj->setReviewerComments($reviewer_comments);
    }
}
if( $GLOBALS['CONFIG']['file_expired_action'] == 2 ) //lock file, not check-outable

{
    $lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
    for($i = 0; $i<mysql_num_rows($lresult); $i++)
    {
        list($lid) = mysql_fetch_row($lresult);
        $file_obj = new FileData($lid, $GLOBALS['connection'], DB_NAME);
        $file_obj->setStatus(-1);
    }
}
echo msg('message_all_actions_successfull');
draw_footer();