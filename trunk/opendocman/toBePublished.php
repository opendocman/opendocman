<?php

include('config.php');

session_start();
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}

// get a list of documents the user has "view" permission for
// get current user's information-->department
$user_obj = new User($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
$flag = 0;

if(!$user_obj->isReviewer())
{
    header('Location:out.php?last_message=Access denied');
}

if(!isset($starting_index))
{
    $starting_index = 0;
}

if(!isset($stoping_index))
{
    $stoping_index = $starting_index+$GLOBALS['CONFIG']['page_limit']-1;
    if(!isset($sort_by))
    {
            $sort_by = 'id';
    }
}
if(!isset($sort_order))
{
    $sort_order = 'a-z';
}
if(!isset($page))
{
    $page = 0;
}

if(!isset($submit))
{
        if (!isset($last_message))
        {
                $last_message='';
        }
	draw_header('Files Review');
	draw_menu($SESSION_UID);
	draw_status_bar('Document Listing for Review',  $last_message);
	$page_url = $_SERVER['PHP_SELF'] . '?';
	$userpermission = new UserPermission($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
	$obj_array = $user_obj->getReviewee();
	$sorted_obj_array = obj_array_sort_interface($obj_array, $sort_order, $sort_by);
	$flag=0;
	echo '<FORM name="table" method="POST" action="' . $_SERVER['PHP_SELF'] . '">'. "\n";
	echo '<TABLE border="1"><TR><TD>';
	list_files($sorted_obj_array, $userpermission, 'toBePublished.php?', $GLOBALS['CONFIG']['dataDir'], $sort_order, $sort_by, $starting_index, $stoping_index, true);
	list_nav_generator(sizeof($obj_array), $GLOBALS['CONFIG']['page_limit'], $page_url, $page, $sort_by, $sort_order);
?>
	</TD>
      </TR>
      <TR>
        <TD>
          <CENTER>
            <INPUT type="button" name="submit" value="Authorize" onClick="checkedBoxesNumber(); authcomment()">
	    <INPUT type="button" name="submit" value="Reject" onClick="checkedBoxesNumber(); rejectcomment()">
	    <INPUT type="hidden" name="subject" value="Comments regarding the review for you documentation">
	    <INPUT type="hidden" name="comments" value="">
	    <INPUT type="hidden" name="to" value="Author(s)">
	    <INPUT type="hidden" name="isopen" value=0>
	    <INPUT type="hidden" name="childStatus" value=1>
	    <INPUT type="hidden" name="Docflag" value=-1>
	    <INPUT type="hidden" name="checkedboxes" value="">
	    <INPUT type="hidden" name="checkednumber" value=0>
	    <INPUT type="hidden" name="fileid" value="">
	</TABLE>
      </FORM>
	<SCRIPT LANGUAGE='JAVASCRIPT'>
	function checkedBoxesNumber()
	{
		counter=0;
		record="";
		for(j=0; j<document.forms[0].elements.length; j++)
		{
			if(document.forms[0].elements[j].type == "checkbox")
			{	
				counter++;
			}
		}
		counter -=1;
	
		for(i=0; i<counter; i++)
		{
			if(eval('document.forms[0].checkbox' + i + '.checked') == true)
			{
				id=(eval('document.forms[0].checkbox' + i + '.value'));			
					document.table.fileid.value +="" + id +" ";
					record +="" + i +" ";
			}
		} 
			document.table.checkedboxes.value = record;
			document.table.checkednumber.value = counter;
		//alert("boxes " + document.table.checkedboxes.value  + " are selected");
		
	}
		
	function sendFields()
	{
		child_form = comment_window.document.author_note_form;
		child_form.subject.value = document.table.subject.value;
		child_form.to.value = document.table.to.value;
		child_form.comments.value = document.table.comments.value;
	}
	var comment_window;
	var comment_form;
	var checkboxes;
	function getComments()
	{
	
		if(document.table.isopen==1)
		{

			comment_window.focus();
		}
		else
		{
			box=document.table.checkedboxes.value;
			file=document.table.fileid.value;
			num_checkedbox = document.table.checkednumber.value;
			if(document.table.Docflag.value == 1)
			{
				comment_window = window.open('<?php echo $PHP_SELF; ?>?submit=comments&num='+ num_checkedbox +'&idfield='+ file +'&number='+ box +'&mode=reviewer 1', 'comment_wins', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=400');
			}
			else if(document.table.Docflag.value == 0)
			{
				comment_window = window.open('<?php echo $PHP_SELF; ?>?submit=comments&num='+ num_checkedbox +'&idfield='+ file +'&number='+ box +'&mode=reviewer 0', 'comment_wins', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=400');
			}
			else
			{
				comment_window = window.open('<?php echo $PHP_SELF; ?>?submit=comments&num='+ num_checkedbox +'&idfield='+ file +'&number='+ box +'&mode=reviewer 2', 'comment_wins', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=400');
			}
			
			comment_window.focus();
			document.table.isopen.value=1;
			setTimeout("sendFields();", 500);
			document.table.isopen.value=0;
		}
	}
	function rejectcomment()
	{
		//add self.name="Parent";
		self.name="Parent";
		if(document.table.isopen.value != 1)
		{
			//alert("Please Provide Reasons Of Why The Document(s) Is Rejected");
			document.table.Docflag.value = 1;

			getComments();

		}
	}
	function authcomment()
	{
	//add self.name="Parent";
		self.name="Parent";
		if(document.table.isopen.value != 1)
		{
			document.table.Docflag.value = 0;

			getComments();
		}
		
		
	}

	</SCRIPT>	
<?php
draw_footer();

}
if(isset($submit) && $submit =='comments')
{

	$idfield=explode(' ',trim($idfield));
	$number=explode(' ',trim($number));
	$boxes;
	$filenums;
	foreach($number as $key=>$value)
	{
		$boxes[]="checkbox".$value;
	}
	foreach ($idfield as $key=>$value)
	{
		$filenums[]=$value;
	}

	$type = substr($mode,9,1);
	$mode= ereg_replace(" [[:digit:]]", "", $mode);
		
	if($mode == 'reviewer')
		$access_mode = 'enabled';
	else
		$access_mode = 'disabled';

	if($type == 1)
	{
		$submit_value='Reject';
	}
	elseif ($type == 0)
	{
		$submit_value='Authorize';
	}
	else
	{
		$submit_value='None';
	}
		
	?>

	<HEAD><TITLE>Notes to Author(s)</TITLE>
	<base target="Parent"></HEAD>
	<FORM name='author_note_form' action='<?php echo $PHP_SELF;?>' onsubmit="closeWindow(1250);" method="POST">
	<TABLE name="author_note_table">
	<TR>
	<TD>To:</TD>
	<TD><INPUT type="text" name="to" value="Author(s)" size='15' <?php echo $access_mode; ?>></TD>
	</TR><TR>
	<TD>Subject</TD>
	<TD><INPUT type="text" name="subject" size=50 value="" size='30'<?php echo $access_mode; ?>></TD></TR>
	</TABLE>
	<BR>&nbsp&nbsp<TEXTAREA name="comments" cols=45 rows=7 size='220'<?php echo $access_mode; ?>></TEXTAREA>
	

	
	<TR><input type="hidden" name="num_checkboxes" value="<?php echo $num; ?>"></TR>
	<?php
	foreach ($boxes as $key=>$value)
	{
		echo '<TR><INPUT type="hidden" name="' . $value .'" value="' . $filenums[$key] . '"></TR>';
	}
	?>
		<TABLE border="0">
		<TR><TD>Email all users</TD><TD><INPUT type="checkbox" name="send_to_all" onchange="send_to_dept.disabled = !send_to_dept.disabled; author_note_form.elements['send_to_users[]'].disabled = !author_note_form.elements['send_to_users[]'].disabled;"></TD></TR>
		<TR><TD>Email whole department</TD><TD><INPUT type="checkbox" name="send_to_dept" onchange="check(this.form.elements['send_to_users[]'], this, send_to_all);"></TD></TR>
		<TR><TD valign="top">Email these users:</TD><TD><SELECT name="send_to_users[]" multiple onchange="check(this, send_to_dept, send_to_all);">
		<?php
			$query = "SELECT user.id, user.first_name, user.last_name from user";
			$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query . " . mysql_error());
			echo "\n\t\t\t\t<OPTION value=\"0\" selected>no one</OPTION>";			
			while( list($id, $first_name, $last_name) = mysql_fetch_row($result) )
			{
				echo "\n\t\t\t\t<OPTION value=\"$id\">$last_name, $first_name</OPTION>";
			}
			echo "\n";
		?>
		</SELECT></TD></TR></TABLE>
	<?php
	if($mode == 'reviewer')
	{?>
	<CENTER><BR><INPUT type="Submit" name="submit" value="<?php echo $submit_value; ?>"  onClick='updateInfo();'>
	<INPUT type="button" name="submit" value="Cancel" onClick="fastclose();"></CENTER>
	<?php
	}
	?>
	</FORM>
	<SCRIPT LANGUAGE='JAVASCRIPT'>
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
	function closeWindow(close_window_in_ms)
	{
		setTimeout(window.close, close_window_in_ms);
	}
	function fastclose()
	{
		
		window.close();
	}
	function updateInfo()
	{
		this_form = document.author_note_form;
		parent_form = window.opener.document.table;
		parent_form.to.value = this_form.to.value;
		parent_form.subject.value = this_form.subject.value;
		parent_form.comments.value = this_form.comments.value;
		
		window.opener.document.table.isopen.value=0;
		window.opener.document.table.to.value = document.author_note_form.to.value;
		window.opener.document.table.subject.value = document.author_note_form.subject.value;
		window.opener.document.table.comments.value = document.author_note_form.comments.value;
		
		//self.close();
	}
	
	
	</SCRIPT>
	<?php
}
elseif (isset($submit) && $submit == 'Reject')
{
	$mail_break = '--------------------------------------------------'."\n";
	$reviewer_comments = "To=$to;Subject=$subject;Comments=$comments;";
	$user_obj = new user($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
	$date = date("D F d Y");
	$time = date("h:i A");
	$get_full_name = $user_obj->getFullName();
	$full_name = $get_full_name[0].' '.$get_full_name[1];
	$mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
	$mail_headers = "From: $mail_from"; 
	$mail_subject='Review status for document ';
	$mail_greeting="Dear author:\n\r\tI would like to inform you that ";
	$mail_body = 'was declined for publishing at '.$time.' on '.$date.' for the following reason(s):'."\n\n".$mail_break.$comments."\n".$mail_break;
	$mail_salute="\n\r\n\rSincerely,\n\r$full_name";
	for($i = 0; $i<$num_checkboxes; $i++)
		if(isset($HTTP_POST_VARS["checkbox$i"]))
		{
			$fileid = $HTTP_POST_VARS["checkbox$i"];
			$file_obj = new FileData($fileid, $GLOBALS['connection'], $GLOBALS['database']);
			$user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], $GLOBALS['database']);
			$mail_to = $user_obj->getEmailAddress();
			mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);	
			$file_obj->Publishable(-1);
			$file_obj->setReviewerComments($reviewer_comments);
		}
	$flag=1;
	header("Location:$PHP_SELF?last_message=File rejection completed successfully");	
	
	
}
elseif (isset($submit) && $submit == 'Authorize')
{
        $reviewer_comments = "To=$to;Subject=$subject;Comments=$comments;";
        $user_obj = new User($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
        $date = date("D F d Y");
        $time = date("h:i A");
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0].' '.$get_full_name[1];
        $mail_subject='Review status for ';
        $mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
        $mail_headers = "From: $mail_from";
        $dept_id = $user_obj->getDeptId();
        for($i = 0; $i<$num_checkboxes; $i++)
                if(isset($HTTP_POST_VARS["checkbox$i"]))
                {
                        
                        $fileid = $HTTP_POST_VARS["checkbox$i"];
                        $file_obj = new FileData($fileid, $GLOBALS['connection'], $GLOBALS['database']);
                        $user_obj = new User($file_obj->getOwner(), $GLOBALS['connection'], $GLOBALS['database']);
                        $mail_to = $user_obj->getEmailAddress();
                        
                        $mail_body='Your file has been authorized for publication.'."\n\n";
                        $mail_body.='Filename:  ' . $file_obj->getName() . "\n\n";
                        $mail_body.='Status: Authorized'. "\n\n";
                        $mail_body.='Date: ' . $date . "\n\n";
                        $mail_body.='Time: ' . $time . "\n\n";
                        $mail_body.='Reviewer: ' . $full_name . "\n\n";
                        $mail_body.='Thank you,'. "\n\n";
                        $mail_body.='Automated Document Messenger'. "\n\n";
                        $mail_body.=$GLOBAL['CONFIG']['base_url'] . "\n\n";
                        
                        mail($mail_to, $mail_subject. $file_obj->getName(), $mail_body, $mail_headers);
                        $file_obj->Publishable(1);
                        $file_obj->setReviewerComments($reviewer_comments);

                        if(isset($send_to_all))
                        {
                                $mail_subject=$file_obj->getName().' added to repository';
                                
                                $mail_body='A new file has been added.'."\n\n";
                                $mail_body.='Filename:  ' . $file_obj->getName() . "\n\n";
                                $mail_body.='Status: New'. "\n\n";
                                $mail_body.='Date: ' . $date . "\n\n";
                                $mail_body.='Time: ' . $time . "\n\n";
                                $mail_body.='Reviewer: ' . $full_name . "\n\n";
                                $mail_body.='Thank you,'. "\n\n";
                                $mail_body.='Automated Document Messenger'. "\n\n";
                                $mail_body.=$GLOBAL['CONFIG']['base_url'] . "\n\n";
                                
                                email_all($mail_from,$mail_subject,$mail_body,$mail_headers);
                        }

                        if(isset($send_to_dept))
                        {
                                 $mail_subject=$file_obj->getName().' added to repository';
                                
                                $mail_body='A new file has been added.'."\n\n";
                                $mail_body.='Filename:  ' . $file_obj->getName() . "\n\n";
                                $mail_body.='Status: New'. "\n\n";
                                $mail_body.='Date: ' . $date . "\n\n";
                                $mail_body.='Time: ' . $time . "\n\n";
                                $mail_body.='Reviewer: ' . $full_name . "\n\n";
                                $mail_body.='Thank you,'. "\n\n";
                                $mail_body.='Automated Document Messenger'. "\n\n";
                                $mail_body.=$GLOBAL['CONFIG']['base_url'] . "\n\n";
                                
                                email_dept($mail_from, $dept_id,$mail_subject ,$mail_body,$mail_headers);
                        }
                        if(sizeof($send_to_users) > 0 && $send_to_users[0]!= 0)
                        {
                                $mail_subject=$file_obj->getName().' added to repository';

                                $mail_body='A new file has been added.'."\n\n";
                                $mail_body.='Filename:  ' . $file_obj->getName() . "\n\n";
                                $mail_body.='Status: New'. "\n\n";
                                $mail_body.='Date: ' . $date . "\n\n";
                                $mail_body.='Time: ' . $time . "\n\n";
                                $mail_body.='Reviewer: ' . $full_name . "\n\n";
                                $mail_body.='Thank you,'. "\n\n";
                                $mail_body.='Automated Document Messenger'. "\n\n";
                                $mail_body.=$GLOBAL['CONFIG']['base_url'] . "\n\n";

                                email_users_id($mail_from, $send_to_users, $mail_subject,$mail_body,$mail_headers);
                        }
                }
        header('Location:' . $_SERVER['PHP_SELF'] . '?last_message=File authorization completed successfully');
?>
                <BODY onload="closeifdown();"></BODY>
<?php	
}
?>
