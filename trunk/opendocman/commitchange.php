<?php
session_start();
// check for valid session
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}
include('config.php');
// connect to DB
$connection = mysql_connect($hostname, $user, $pass) or die ("Unable to connect!");

// Submitted so insert data now
if($adduser)
{
	// Check to make sure user does not already exist
    $query = "SELECT username FROM user WHERE username = '" . addslashes($username) . '\'';
    $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

    // If the above statement returns more than 0 rows, the user exists, so display error
    if(mysql_num_rows($result) > 0)
    {
 	   header('Location:error.php?ec=3');
       exit;
    }
    else
    {     
    	if(strcmp(substr($phonenumber,0,1), "(") !=0)
    	{
	    		
	    	$phonenumber=ereg_replace(' ', '', $phonenumber);
	    	$areacode=substr($phonenumber,0,3);
	    	$firstthree=substr($phonenumber,3,3);
	    	$lastfour=substr($phonenumber,6,4);
	    	$phonenumber='(' . $areacode . ') ' . $firstthree . '-' . $lastfour;
    	}

	   // INSERT into user
       $query = "INSERT INTO user (id, username, password, department, phone, Email,last_name, first_name) VALUES('', '". addslashes($username)."', password('". addslashes($password) ."'), '$department' ,'" . addslashes($phonenumber) . "','". addslashes($Email)."', '" . addslashes($last_name) . "', '" . addslashes($first_name) . '\' )';
       $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
       // INSERT into admin
       $userid = mysql_insert_id($connection);
       $query = "INSERT INTO admin (id, admin) VALUES('$userid', '$admin')";
       $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	   if($reviewer)
	   {
			for($i = 0; $i<sizeof($department_review); $i++)
			{
				$query = "INSERT INTO dept_reviewer (dept_id, user_id) values($department_review[$i], $userid)";
			   	$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query". mysql_error());
			}
	   }
	   
	   // mail user telling him/her that his/her account has been created.
       	$user_obj = new user($SESSION_UID, $connection, $database);
       	$new_user_obj = new User($userid, $connection, $database);
       	$date = date('D F d Y');
		$time = date('h:i A');
		$get_full_name = $user_obj->getFullName();
		$full_name = $get_full_name[0].' '.$get_full_name[1];
		$get_full_name = $new_user_obj->getFullName();
		$new_user_full_name = $get_full_name[0].' '.$get_full_name[1];
		$mail_from= $full_name.' <'.$user_obj->getEmailAddress().'>';
		$mail_headers = "From: $mail_from"; 
		$mail_subject='Your account has been created';
		$mail_greeting='Dear '.$new_user_full_name.":\n\r\tI would like to inform you that ";
		$mail_body = 'your document management account was created at '.$time.' on '.$date.'.  You can now log into your account at this page:'."\n\r";
                $mail_body.= $GLOBALS['CONFIG']['base_url']."\n\n";
		$mail_body.= 'Your login name is: '.$new_user_obj->getName()."\n\n";
		if($GLOBALS['CONFIG']['authen'] == 'mysql')
		{
			$mail_body.='Your randomly generated passowrd is: '.$password."\n\n";
			$mail_body.='If you would like to change this to something else once you log in, ';
			$mail_body.='you can do so by clicking on "Preferences" in the status bar.'."\n";
		}
		else 
			$mail_body.='Your password is your UC Davis campus kerberos password.';
		$mail_salute="\n\rSincerely,\n\r$full_name";
		$mail_to = $new_user_obj->getEmailAddress();
		mail($mail_to, $mail_subject, ($mail_greeting.' '.$mail_body.$mail_salute), $mail_headers);
		$last_message = urlencode('User successfully added');
       	header('Location: admin.php?last_message=' . $last_message);
       	mysql_close($connection);
    }
}
elseif($updateuser)
{
	
	if(!$callee || $callee == '')
	{
		$callee='admin.php';
	}
	$user_obj = new User($id, $connection, $database);
	// UPDATE admin info
    $query = "UPDATE admin set admin='$admin' where id = '$id'";
    $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
    
        if(strcmp(substr($phonenumber,0,1), '(') !=0)
	   	{
	    	$phonenumber=ereg_replace(' ', '', $phonenumber);
	    	$areacode=substr($phonenumber,0,3);
	    	$firstthree=substr($phonenumber,3,3);
	    	$lastfour=substr($phonenumber,6,4);
	    	$phonenumber='(' . $areacode . ') ' . $firstthree . '-' . $lastfour;
	   	}

	// UPDATE into user
    $query = "UPDATE user SET username='". addslashes($username) ."',";
	if (!empty($password))
	{
		$query .= "password = password('". addslashes($password) ."'), ";
	}
	$query.= 'department="' . addslashes($department) . '",';
	$query.= 'phone="' . addslashes($phonenumber) . '",';
	$query.= 'Email="' . addslashes($Email) . '" ,';
	$query.= 'last_name="' . addslashes($last_name) . '",';
	$query.= 'first_name="' . addslashes($first_name) . '" ';
	$query.= 'WHERE id="' . $id . '"';
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	
	// UPDATE into dept_reviewer
	$query = "DELETE FROM dept_reviewer where user_id = $id";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());  
	if($reviewer)
	{
		//Remove all entry for $id
		$query = "DELETE FROM dept_reviewer where user_id = $id";
		$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query". mysql_error());
		for($i = 0; $i<sizeof($department_review); $i++)
		{
			$query = "INSERT INTO dept_reviewer (dept_id, user_id) values($department_review[$i], $id)";
			$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query". mysql_error());
		}
	}
	// back to main page
    $last_message = urlencode('User successfully updated');
    header('Location: ' . $callee . '?last_message=' . $last_message);
    mysql_close($connection);
}
// Delete USER
elseif($deleteuser){
        // form has been submitted -> process data
        // DELETE admin info
        $query = "DELETE FROM admin WHERE id = $id";
        $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

        // DELETE user info
        $query = "DELETE FROM user WHERE id = $id";
        $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

        // DELETE perms info
        $query = "DELETE FROM user_perms WHERE uid = $id";
        $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // Change data info to nobody
        $query = "UPDATE data SET owner='99' where owner = '$id'";
        $result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

        // back to main page
        $last_message = urlencode($id . ' User successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}
//Add Departments
elseif($adddepartment)
{
		//Check to see if this department is already in DB
		$query = "SELECT department.name from department where department.name=\"" . addslashes($department) . '"';
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        if(mysql_num_rows($result) != 0)
        {
	       	header('Location: error.php?ec=3&message=' . $department . ' already exist in the database');
        	exit;
        }
		$query = "INSERT INTO department (name) VALUES ('" . addslashes($department) . '\')';
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department successfully added');
        /////////Give New Department data's default rights///////////
        ////Get all default rights////
        $query = "SELECT id, default_rights from data";
       	$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query. " . mysql_error());
        $num_rows = mysql_num_rows($result);
        $data_array = array();
       	for($index = 0; $index< $num_rows; $index++)
       		list($data_array[$index][0], $data_array[$index][1]) = mysql_fetch_row($result);
       	mysql_free_result($result);
       	//////Get the new department's id////////////
       	$query = "SELECT id FROM department WHERE name = '" . addslashes($department) . "'";
       	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
       	$num_rows = mysql_num_rows($result);
       	if( $num_rows != 1 )
       	{
       		header('Location: error.php?ec=14&message=unable to identify ' . $department);
       		exit;	
       	}
        list($newly_added_dept_id) = mysql_fetch_row($result);
        ////Set default rights into department//////
        $num_rows = sizeof($data_array);
        for($index = 0; $index < $num_rows; $index++)
       	{
       		$query = "INSERT INTO dept_perms (fid, dept_id, rights) values(".$data_array[$index][0].','. $newly_added_dept_id.','. $data_array[$index][1].')';
       		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());        
       	}
       	header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}
// UPDATE Department
elseif($updatedepartment){
        $query = "UPDATE department SET name='" . addslashes($name) ."' where id='$id'";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department successfully updated - name=' . $name . '- id=' . $id);
        header('Location: admin.php?last_message=' . $last_message);
	
        mysql_close($connection);
}
elseif($deletedepartment){
	// Delete department
        $query = "DELETE from department where id='$id'";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department (' . $id . ') successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}

// Add Category
elseif($addcategory){
        $query = "INSERT INTO category (name) VALUES ('". addslashes($category) ."')";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category successfully added');
        header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}
// Delete department
elseif($deletecategory){
        $query = "DELETE from category where id='$id'";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category (' . $id . ') successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}
// UPDATE Category
elseif($updatecategory){
        $query = "UPDATE category SET name='". addslashes($name) ."' where id='$id'";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category ' . $name . ' successfully updated');
        header('Location: admin.php?last_message=' . $last_message);
        mysql_close($connection);
}

else
{
	echo 'Nothing to do';
	display_array($HTTP_POST_VARS);
}
?>




