<?php
// check-in.php - uploads a new version of a file

// check for valid session and $id
session_start();
if (!isset($_SESSION['uid']))
{
        $last_message='Failed';
        header('Location:error.php?ec=1&last_message=' . urlencode($last_message));
        exit;
}

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '')
{
        $last_message='Failed';
        header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
        exit;
}

// includes
include('config.php');

// open connection
if (!isset($_POST['submit']))
{
	draw_menu($_SESSION['uid']);
	@draw_status_bar('Check Document In',$_REQUEST['last_message']);
	// form not yet submitted, display initial form

	// pre-fill the form with some information so that user knows which file is being updated
	$query = "SELECT description, realname from data WHERE id = '$_REQUEST[id]' AND status = '$_SESSION[uid]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
	// in case script is directly accessed, query above will return 0 rows
	if (mysql_num_rows($result) <= 0)
	{
                $last_message='Failed';
		header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
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
		
		<table border="0" cellspacing="5" cellpadding="5">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
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
	$query = "select realname from data where data.id = '$_POST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: ".$mysql_error());
	
        // 
        if(mysql_num_rows($result) != 1)
	{	
                $last_message='Failed';
                header('Location:error.php?ec=16&last_message=' . urlencode($last_message)); 
                exit;	
        }
	
        list($realname) = mysql_fetch_row($result);
	
        if($_FILES['file']['name'] != $realname)
        {
                $last_message='Failed';
                header('Location:error.php?ec=15&last_message=' . urlencode($last_message)); 
                exit;	
        }
	
        // no file!
	if ($_FILES['file']['size'] <= 0)
	{ 
                $last_message='Failed';
		header('Location:error.php?ec=11&last_message=' . urlencode($last_message));
		exit;
	}
	
	// check file type
	foreach($GLOBALS['allowedFileTypes'] as $this)
	{
		if ($_FILES['file']['type'] == $this) 
		{ 
			$allowedFile = 1;
		    break; 
		} 
        else
        {       
            $allowedFile = 0;
        }
	}
	// illegal file type!
	if ($allowedFile != 1) 
	{ 
        $last_message='Failed';
		header('Location:error.php?ec=13&last_message=' . urlencode($last_message)); 
		exit; 
	}
	
	// query to ensure that user has modify rights
	$fileobj = new FileData($_POST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	if($fileobj->getError() == '' and $fileobj->getStatus() == $_SESSION['uid'])
	{
		// all OK, proceed!
  		$query = "SELECT username FROM user WHERE id='$_SESSION[uid]'";
    	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
		list($username) = mysql_fetch_row($result);
				
	 	// update revision log
		$query = "INSERT INTO log (id, modified_on, modified_by, note) VALUES('$_POST[id]', NOW(), '" . addslashes($username) . "', '". addslashes($_POST['note']) ."')";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
		// update file status
		$query = "UPDATE data SET status = '0', publishable='0' WHERE id='$_POST[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
		// rename and save file
		$newFileName = $_POST['id'] . '.dat';
		//copy($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . $newFileName);
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . $newFileName))
                {
                        $last_message='Check-in Failed';
                        header('Location:error.php?ec=18&last_message=' . urlencode($last_message));
                        exit;
                }
                                
		
		//Send email
		$date = date('D F d Y');
		$time = date('h:i A');
		$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
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

		if(isset($send_to_users) && sizeof($send_to_users) > 0)
		{
                        $mail_body='Filename: '. $fileobj->getName(). "\n\n";
                        $mail_body.='Date: ' . $date . "\n\n";
                        $mail_body.='Time: ' . $time . "\n\n";
                        $mail_body.='Action: Updated'."\n\n";
			
                        email_users_id($mail_from, $send_to_users, $fileobj->getName().' was updated in OpenDocMan',$mail_body, $mail_headers);
		}

		// clean up and back to main page
		$last_message = 'Document successfully checked in';
		header('Location: out.php?last_message=' . urlencode($last_message));
	}
}
?>
