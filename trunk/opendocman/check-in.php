<?php
/*
All source code copyright and proprietary Melonfire, 2001. All content, brand names and trademarks copyright and proprietary Melonfire, 2001. All rights reserved. Copyright infringement is a violation of law.

This source code is provided with NO WARRANTY WHATSOEVER. It is meant for illustrative purposes only, and is NOT recommended for use in production environments. 

Read more articles like this one at http://www.melonfire.com/community/columns/trog/ and http://www.melonfire.com/
*/

// check-in.php - uploads a new version of a file

// check for valid session and $id
session_start();
if (!session_is_registered('SESSION_UID'))
{
header('Location:error.php?ec=1');
exit;
}

if (!isset($id) || $id == '')
{
header('Location:error.php?ec=2');
exit;
}

// includes
include('config.php');

// open connection
if (!isset($submit))
{
	draw_menu($SESSION_UID);
	// form not yet submitted, display initial form

	// pre-fill the form with some information so that user knows which file is being updated
	$query = "SELECT description, realname from data WHERE id = '$id' AND status = '$SESSION_UID'";
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
	// in case script is directly accessed, query above will return 0 rows
	if (mysql_num_rows($result) <= 0)
	{
		header('Location:error.php?ec=2');
		exit;
	}
	else
	{
		// get result data
		list($description, $realname) = mysql_fetch_row($result);
		
		// correction
		if($description == '') 
		{ 
			$description = 'No description available';
		}
	
		// clean up
		mysql_free_result($result);
		// start displaying form
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="3">
		<tr>
		<td bgcolor="#0000A0">
		<b><font face="Arial" color="White">Document Check In</font></b>
		</td>
		</tr>
		</table>
		
		<p>
		
		<table border="0" cellspacing="5" cellpadding="5">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="id" value="<?php echo $id; ?>">
		<tr>
		<td><b>Document</b></td>
		<td><b><?php echo $realname; ?></b></td>
		</tr>
		
		<tr>
		<td><b>Description</b></td>
		<td><?php echo $description; ?></td>
		</tr>
	
		<tr>
		<td><b>Location</b></td>
		<td><input name="file" type="file"></td>
		</tr>
		
		<tr>
		<td>Note (for revision log)</td>
		<td><textarea name="note"></textarea></td>
		</tr>
		
		
			<tr>
		<td colspan="4" align="center"><input type="Submit" name="submit" value="Check  Document In"></td>
		</tr>
		</form>
		</table>
		</center>
<?php
		draw_footer();
?>
		</body>
		</html>
		<SCRIPT language="JAVASCRIPT">
		function check(select, send_dept, send_all)
		{
			if(send_dept.checked || select.options[select.selectedIndex].value != "0")
				send_all.disabled = true;
			else
			{
				send_all.disabled = false;
				for(var i = 1; i < select.options.length; i++)
					select.options[i].selected = false;
			}
		}
		</SCRIPT>
<?php
	}//end else
}//end if (!$submit)
else
{
// form has been submitted, process data
	
	// checks
	$query = "select realname from data where data.id = '$id'";
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die("Error in query: ".$mysql_error());
	if(mysql_num_rows($result) != 1)
	{	header('Location:error.php?ec=16'); exit;	}
	list($realname) = mysql_fetch_row($result);
	if($HTTP_POST_FILES['file']['name'] != $realname)
    { header('Location:error.php?ec=15'); exit;	}
	
    // no file!
	if ($file_size <= 0)
	{ 
		header('Location:error.php?ec=11');
		exit;
	}
	
	// check file type
	foreach($allowedFileTypes as $this)
	{
		if ($file_type == $this) 
		{ 
		$allowedFile = 1;
		break; 
		} 
	}
	// illegal file type!
	if ($allowedFile != 1) 
	{ 
		header('Location:error.php?ec=13'); 
		exit; 
	}
	
	// query to ensure that user has modify rights
	$fileobj = new FileData($id, $GLOBALS['connection'], $GLOBALS['database']);
	if($fileobj->getError() == '' and $fileobj->getStatus() == $SESSION_UID)
	{
		// all OK, proceed!
  		$query = "SELECT username FROM user WHERE id='$SESSION_UID'";
    	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
		list($username) = mysql_fetch_row($result);
				
	 	// update revision log
		$query = "INSERT INTO log (id, modified_on, modified_by, note) VALUES('$id', NOW(), '" . addslashes($username) . "', '". addslashes($note) ."')";
		$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
		// update file status
		$query = "UPDATE data SET status = '0', publishable='0' WHERE id='$id'";
		$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
		// rename and save file
		$newFileName = $id . '.dat';
		copy($file, $GLOBALS['CONFIG']['dataDir'] . $newFileName);
		
		//Send email
		$date = date('D F d Y');
		$time = date('h:i A');
		$user_obj = new User($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
		$get_full_name = $user_obj->getFullName();
		$full_name = $get_full_name[0].' '.$get_full_name[1];
		$mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
		$mail_headers = 'From: ' . $mail_from;
		$dept_id = $user_obj->getDeptId();
		if(isset($send_to_all))
		{
                        $mail_body='Filename: '. $fileobj->getName(). "\n\n";
                        $mail_body.='Date: ' . $date . "\n\n";
                        $mail_body.='Time: ' . $time . "\n\n";
                        $mail_body.='Action: Updated'."\n\n";

			email_all($mail_from, $fileobj->getName().' was updated in OpenDocMan',$mail_body,$mail_headers);
		}

		if(isset($send_to_dept))
		{
                        $mail_body='Filename: '. $fileobj->getName(). "\n\n";
                        $mail_body.='Date: ' . $date . "\n\n";
                        $mail_body.='Time: ' . $time . "\n\n";
                        $mail_body.='Action: Updated'."\n\n";
			
                        email_dept($mail_from, $dept_id, $fileobj->getName().' was updated in OpenDocMan',$mail_body,$mail_headers);
		}

		if(sizeof($send_to_users) > 0)
		{
                        $mail_body='Filename: '. $fileobj->getName(). "\n\n";
                        $mail_body.='Date: ' . $date . "\n\n";
                        $mail_body.='Time: ' . $time . "\n\n";
                        $mail_body.='Action: Updated'."\n\n";
			
                        email_users_id($mail_from, $send_to_users, $fileobj->getName().' was updated in OpenDocMan',$mail_body, $mail_headers);
		}

		// clean up and back to main page
		$last_message = 'Document successfully checked in';
		header('Location: out.php?last_message=' . $last_message);
	}
}
?>
