<?php
include('config.php');
$start_time = time();
session_start();

if (!isset($_REQUEST['last_message']))
{
	    $_REQUEST['last_message']='';
}

// includes
draw_header('Check Expirations');
draw_menu(@$_SESSION['uid']);
draw_status_bar('Check Expirations', $_REQUEST['last_message']);

// Look up user
$lquery = 'SELECT user.id FROM user where user.username="' . $GLOBALS['CONFIG']['root_username'] . '"';
$lresult = mysql_query($lquery) or die('Error querying' . mysql_error());
if(mysql_num_rows($lresult) != 1)
{	header('location:error.php?ec=22');	}
else 
{	list($lroot_id) = mysql_fetch_row($lresult);	}
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
$lquery = "SELECT data.id, data.reviewer_comments FROM data, log WHERE data.id = log.id AND log.revision='current' AND modified_on<'$lexpired_revision' AND (data.publishable!=-1 and data.status!=-1)";
$lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
echo 'Rejecting files last edited before ' . $lexpired_revision . '<br>';
echo 'Rejecting ' . mysql_num_rows($lresult) . ' file(s)<br>';
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
	$reviewer_comments = 'To=Author;Subject=File expired;Comments=Your file was rejected because you did not revise it for more than ' . $GLOBALS['CONFIG']['revision_expiration'] . ' days;';
	$user_obj = new user($lroot_id, $GLOBALS['connection'], $GLOBALS['database']);
	$date = date("D F d Y");
	$time = date("h:i A");
	$get_full_name = $user_obj->getFullName();
	$full_name = $get_full_name[0].' '.$get_full_name[1];
	$mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
	$mail_headers = "From: $mail_from";
	$mail_subject='Review status for document ';
	$mail_greeting="Dear author:\n\r\tI would like to inform you that ";
	$mail_body = 'was declined for publishing at '.$time.' on '.$date.' because you did not revise it for more than ' . $GLOBALS['CONFIG']['revision_expiration'] . ' days.';
	$mail_salute="\n\r\n\rSincerely,\n\r$full_name";
	for($i = 0; $i<mysql_num_rows($lresult); $i++)
	{	
		list($lid) = mysql_fetch_row($lresult);	
		$file_obj = new FileData($lid, $GLOBALS['connection'], $GLOBALS['database']);
		$user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], $GLOBALS['database']);
		$mail_to = $user_obj->getEmailAddress();
		mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
	}
}
if($GLOBALS['CONFIG']['file_expired_action'] == 1 ) //do not show file
{
	$lresult = mysql_query($lquery) or die('Error querying: ' . $lquery . mysql_error());
	$reviewer_comments = 'To=Author;Subject=File expired;Comments=Your file was rejected because you did not revise it for more than ' . $GLOBALS['CONFIG']['revision_expiration'] . ' days;';
	for($i = 0; $i<mysql_num_rows($lresult); $i++)
	{
		list($lid) = mysql_fetch_row($lresult);
		$file_obj = new FileData($lid, $GLOBALS['connection'], $GLOBALS['database']);
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
		$file_obj = new FileData($lid, $GLOBALS['connection'], $GLOBALS['database']);
		$file_obj->setStatus(-1);
	}
}
echo 'All proccesses are completed successfully';
?>
