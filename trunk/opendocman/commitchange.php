<?php
session_start();
// check for valid session
if (!isset($_SESSION['uid']))
{
	header('Location:error.php?ec=1');
	exit;
}
include('config.php');
// connect to DB
// Submitted so insert data now
if(isset($_POST['adduser']))
{
	// Check to make sure user does not already exist
    $query = "SELECT username FROM user WHERE username = '" . addslashes($_POST['username']) . '\'';
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    // If the above statement returns more than 0 rows, the user exists, so display error
    if(mysql_num_rows($result) > 0)
    {
 	   header('Location:error.php?ec=3');
           exit;
    }
    else
    {     
    	if(strcmp(substr($_POST['phonenumber'],0,1), "(") !=0)
    	{
	    		
	    	$phonenumber=ereg_replace(' ', '', $_POST['phonenumber']);
	    	$areacode=substr($phonenumber,0,3);
	    	$firstthree=substr($phonenumber,3,3);
	    	$lastfour=substr($phonenumber,6,4);
	    	$phonenumber='(' . $areacode . ') ' . $firstthree . '-' . $lastfour;
    	}

	   // INSERT into user
       $query = "INSERT INTO user (id, username, password, department, phone, Email,last_name, first_name) VALUES('', '". addslashes($_POST['username'])."', password('". addslashes($_POST['password']) ."'), '" . addslashes($_POST['department'])."' ,'" . addslashes($phonenumber) . "','". addslashes($_POST['Email'])."', '" . addslashes($_POST['last_name']) . "', '" . addslashes($first_name) . '\' )';
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       // INSERT into admin
       $userid = mysql_insert_id($GLOBALS['connection']);
       $query = "INSERT INTO admin (id, admin) VALUES('$userid', '$_POST[admin]')";
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	   if(isset($_POST['reviewer']))
	   {
			for($i = 0; $i<sizeof($_POST['department_review']); $i++)
			{
                                $dept_rev=$_POST['department_review'][$i];
				$query = "INSERT INTO dept_reviewer (dept_id, user_id) values('$dept_rev', $userid)";
			   	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
			}
	   }
	   
	   // mail user telling him/her that his/her account has been created.
       	$user_obj = new user($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
       	$new_user_obj = new User($userid, $GLOBALS['connection'], $GLOBALS['database']);
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
			$mail_body.='Your randomly generated passowrd is: '.$_POST['password']."\n\n";
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
    }
}
elseif(isset($_POST['updateuser']))
{
	
        if(!isset($_POST['admin']) || $_POST['admin'] == '')
        {
                $_POST['admin'] = 'no';
        }
	if(!isset($_POST['callee']) || $_POST['callee'] == '')
	{
		$callee='admin.php';
	}
	$user_obj = new User($_POST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	// UPDATE admin info
        $query = "UPDATE admin set admin='". $_POST['admin'] . "' where id = '".$_POST['id']."'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    
        if(strcmp(substr($_POST['phonenumber'],0,1), '(') !=0)
	   	{
	    	$phonenumber=ereg_replace(' ', '', $_POST['phonenumber']);
	    	$areacode=substr($phonenumber,0,3);
	    	$firstthree=substr($phonenumber,3,3);
	    	$lastfour=substr($phonenumber,6,4);
	    	$_POST['phonenumber']='(' . $areacode . ') ' . $firstthree . '-' . $lastfour;
	   	}

	// UPDATE into user
        $query = "UPDATE user SET username='". addslashes($_POST['username']) ."',";
	if (!empty($_POST['password']))
	{
		$query .= "password = password('". addslashes($_POST['password']) ."'), ";
	}
	$query.= 'department="' . addslashes($_POST['department']) . '",';
	$query.= 'phone="' . addslashes($_POST['phonenumber']) . '",';
	$query.= 'Email="' . addslashes($_POST['Email']) . '" ,';
	$query.= 'last_name="' . addslashes($_POST['last_name']) . '",';
	$query.= 'first_name="' . addslashes($_POST['first_name']) . '" ';
	$query.= 'WHERE id="' . $_POST['id'] . '"';
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
	// UPDATE into dept_reviewer
	$query = "DELETE FROM dept_reviewer where user_id = '$_POST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());  
	if(isset($_POST['reviewer']))
	{
		//Remove all entry for $id
		$query = "DELETE FROM dept_reviewer where user_id = $_POST[id]";
		$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
		for($i = 0; $i<sizeof($_POST['department_review']); $i++)
		{
                        $dept_rev=$_POST['department_review'][$i];
			$query = "INSERT INTO dept_reviewer (dept_id, user_id) values('$dept_rev', $_POST[id])";
			$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
		}
	}
	// back to main page
    $last_message = urlencode('User successfully updated');
    header('Location: ' . $_POST['callee'] . '?last_message=' . $last_message);
}
// Delete USER
elseif(isset($_POST['deleteuser']))
{
        // form has been submitted -> process data
        // DELETE admin info
        $query = "DELETE FROM admin WHERE id = '$_GET[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE user info
        $query = "DELETE FROM user WHERE id = '$_GET[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE perms info
        $query = "DELETE FROM user_perms WHERE uid = '$_GET[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // Change data info to nobody
        $query = "UPDATE data SET owner='99' where owner = '$_GET[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // back to main page
        $last_message = urlencode($_GET['id'] . ' User successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
}
//Add Departments
elseif(isset($adddepartment))
{
		//Check to see if this department is already in DB
		$query = "SELECT department.name from department where department.name=\"" . addslashes($department) . '"';
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        if(mysql_num_rows($result) != 0)
        {
	       	header('Location: error.php?ec=3&message=' . $department . ' already exist in the database');
        	exit;
        }
		$query = "INSERT INTO department (name) VALUES ('" . addslashes($department) . '\')';
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department successfully added');
        /////////Give New Department data's default rights///////////
        ////Get all default rights////
        $query = "SELECT id, default_rights from data";
       	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
        $num_rows = mysql_num_rows($result);
        $data_array = array();
       	for($index = 0; $index< $num_rows; $index++)
       		list($data_array[$index][0], $data_array[$index][1]) = mysql_fetch_row($result);
       	mysql_free_result($result);
       	//////Get the new department's id////////////
       	$query = "SELECT id FROM department WHERE name = '" . addslashes($department) . "'";
       	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
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
       		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());        
       	}
       	header('Location: admin.php?last_message=' . $last_message);
}
// UPDATE Department
elseif(isset($updatedepartment))
{
    //Check to see if this department is already in DB
	$query = "SELECT department.name from department where department.name=\"" . addslashes($name) . '" and department.id!=' . $id;
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 0)
    {
       	header('Location: error.php?ec=3&message=' . $department . ' already exist in the database');
    	exit;
    }    
	$query = "UPDATE department SET name='" . addslashes($name) ."' where id='$id'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // back to main page
    $last_message = urlencode('Department successfully updated - name=' . $name . '- id=' . $id);
    header('Location: admin.php?last_message=' . $last_message);
}
elseif(isset($deletedepartment))
{
	// Delete department
        $query = "DELETE from department where id='$id'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department (' . $id . ') successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
}

// Add Category
elseif(isset($addcategory))
{
        $query = "INSERT INTO category (name) VALUES ('". addslashes($category) ."')";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category successfully added');
        header('Location: admin.php?last_message=' . $last_message);
}
// Delete department
elseif(isset($deletecategory))
{
        $query = "DELETE from category where id='$id'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category (' . $id . ') successfully deleted');
        header('Location: admin.php?last_message=' . $last_message);
}
// UPDATE Category
elseif(isset($updatecategory))
{
        $query = "UPDATE category SET name='". addslashes($name) ."' where id='$id'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Category ' . $name . ' successfully updated');
        header('Location: admin.php?last_message=' . $last_message);
}

else
{
	echo 'Nothing to do';
	display_array($_POST);
}
?>
