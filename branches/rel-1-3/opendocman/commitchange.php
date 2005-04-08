<?php
session_start();
// check for valid session
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING'] ) );
	exit;
}
include('config.php');
$secureurl = new phpsecureurl;

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);

// connect to DB
// Submitted so insert data now
if(isset($_REQUEST['adduser']))
{
    if (!$user_obj->isAdmin()){
           header('Location:' . $secureurl->encode('error.php?ec=4'));
           exit;
    }
	// Check to make sure user does not already exist
    $query = "SELECT username FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE username = '" . addslashes($_POST['username']) . '\'';
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    // If the above statement returns more than 0 rows, the user exists, so display error
    if(mysql_num_rows($result) > 0)
    {
 	   header('Location:' . $secureurl->encode('error.php?ec=3'));
           exit;
    }
    else
    {     
    	$phonenumber = @$_REQUEST['phonenumber'];
	   // INSERT into user
       $query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "user (id, username, password, department, phone, Email,last_name, first_name) VALUES('', '". addslashes($_POST['username'])."', password('". addslashes(@$_REQUEST['password']) ."'), '" . addslashes($_REQUEST['department'])."' ,'" . addslashes($phonenumber) . "','". addslashes($_REQUEST['Email'])."', '" . addslashes($_REQUEST['last_name']) . "', '" . addslashes($_REQUEST['first_name']) . '\' )';
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       // INSERT into admin
       $userid = mysql_insert_id($GLOBALS['connection']);
        if (!isset($_REQUEST['admin']))
        {
                $_REQUEST['admin']='';
        }
       $query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "admin (id, admin) VALUES('$userid', '$_REQUEST[admin]')";
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	   if(isset($_REQUEST['reviewer']))
	   {
			for($i = 0; $i<sizeof($_REQUEST['department_review']); $i++)
			{
                                $dept_rev=$_REQUEST['department_review'][$i];
				$query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer (dept_id, user_id) values('$dept_rev', '$userid')";
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
			$mail_body.='Your randomly generated passowrd is: '.$_REQUEST['password']."\n\n";
			$mail_body.='If you would like to change this to something else once you log in, ';
			$mail_body.='you can do so by clicking on "Preferences" in the status bar.'."\n";
		}
		else 
			$mail_body.='Your password is your UC Davis campus kerberos password.';
		$mail_salute="\n\rSincerely,\n\r$full_name";
		$mail_to = $new_user_obj->getEmailAddress();
		mail($mail_to, $mail_subject, ($mail_greeting.' '.$mail_body.$mail_salute), $mail_headers);
		$_REQUEST['last_message'] = urlencode('User successfully added');
       	header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
    }
}
elseif(isset($_REQUEST['updateuser']))
{
    // Check to make sure they are either the user being modified or an admin
    if (($_REQUEST['id'] != $_SESSION['uid']) && !$user_obj->isAdmin()){
           header('Location:' . $secureurl->encode('error.php?ec=4'));
           exit;
    }
    if(!isset($_REQUEST['admin']) || $_REQUEST['admin'] == '')
    {
            $_REQUEST['admin'] = 'no';
    }
	if(!isset($_REQUEST['caller']) || $_REQUEST['caller'] == '')
	{
		$_REQUEST['caller'] = 'admin.php';
	}
	$user_obj = new User($_REQUEST['id'], $GLOBALS['connection'], $GLOBALS['database']);
	// UPDATE admin info
        $query = "UPDATE " . $GLOBALS['CONFIG']['table_prefix'] . "admin set admin='". $_REQUEST['admin'] . "' where id = '".$_REQUEST['id']."'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	// UPDATE into user
        $query = "UPDATE " . $GLOBALS['CONFIG']['table_prefix'] . "user SET username='". addslashes($_POST['username']) ."',";
	if (!empty($_REQUEST['password']))
	{
		$query .= "password = password('". addslashes($_REQUEST['password']) ."'), ";
	}
	if( isset( $_REQUEST['department'] ) )
	{	$query.= 'department="' . addslashes($_REQUEST['department']) . '",';	}
	if( isset( $_REQUEST['phonenumber'] ) )
	{	$query.= 'phone="' . addslashes($_REQUEST['phonenumber']) . '",';	}
	if( isset( $_REQUEST['Email'] ) )
	{	$query.= 'Email="' . addslashes($_REQUEST['Email']) . '" ,';	}
	if( isset( $_REQUEST['last_name'] ) )
	{	$query.= 'last_name="' . addslashes($_REQUEST['last_name']) . '",';	}
	if( isset( $_REQUEST['first_name'] ) )
	{	$query.= 'first_name="' . addslashes($_REQUEST['first_name']) . '" ';	}
	$query.= 'WHERE id="' . $_REQUEST['id'] . '"';
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
	// UPDATE into dept_reviewer
	$query = "DELETE FROM " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer where user_id = '$_REQUEST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());  
	if(isset($_REQUEST['reviewer']))
	{
		//Remove all entry for $id
		$query = "DELETE FROM " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer where user_id = '$_REQUEST[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
		$depts_rev = $_REQUEST['department_review'];
		for($i = 0; $i<sizeof($_REQUEST['department_review']); $i++)
		{
            $dept_rev=$depts_rev[$i];
			$query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer (dept_id, user_id) values('$dept_rev', '$_REQUEST[id]')";
			$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
		}
	}
	// back to main page
	if(!isset($_REQUEST['caller']))
	{	$_REQUEST['caller'] = 'admin.php';	}
    $_REQUEST['last_message'] = urlencode('User successfully updated');
    header('Location: ' . $_REQUEST['caller'] . '?last_message=' . $_REQUEST['last_message']);
}
// Delete USER
elseif(@$_REQUEST['submit'] == 'deleteuser' )
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        }
    
        // form has been submitted -> process data
        // DELETE admin info
        $query = "DELETE FROM " . $GLOBALS['CONFIG']['table_prefix'] . "admin WHERE id = '$_REQUEST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE user info
        $query = "DELETE FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE id = '$_REQUEST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE perms info
        $query = "DELETE FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user_perms WHERE uid = '$_REQUEST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // Change data info to nobody
        $query = "UPDATE " . $GLOBALS['CONFIG']['table_prefix'] . "data SET owner='99' where owner = '$_REQUEST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // back to main page
        $_REQUEST['last_message'] = urlencode($_REQUEST['id'] . ' User successfully deleted');
        header('Location:' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
//Add Departments
elseif(@$_REQUEST['submit'] == 'Add Department')
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 

		//Check to see if this department is already in DB
		$query = "SELECT name from " . $GLOBALS['CONFIG']['table_prefix'] . "department where name=\"" . addslashes($_REQUEST['department']) . '"';
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        if(mysql_num_rows($result) != 0)
        {
	       	header('Location:' . $secureurl->encode(' error.php?ec=3&message=' . $_REQUEST['department'] . ' already exist in the database'));
        	exit;
        }
		$query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "department (name) VALUES ('" . addslashes($_REQUEST['department']) . '\')';
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_REQUEST['last_message'] = urlencode('Department successfully added');
        /////////Give New Department data's default rights///////////
        ////Get all default rights////
        $query = "SELECT id, default_rights from " . $GLOBALS['CONFIG']['table_prefix'] . "data";
       	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
        $num_rows = mysql_num_rows($result);
        $data_array = array();
       	for($index = 0; $index< $num_rows; $index++)
       		list($data_array[$index][0], $data_array[$index][1]) = mysql_fetch_row($result);
       	mysql_free_result($result);
       	//////Get the new department's id////////////
       	$query = "SELECT id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "department WHERE name = '" . addslashes($_REQUEST['department']) . "'";
       	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       	$num_rows = mysql_num_rows($result);
       	if( $num_rows != 1 )
       	{
       		header('Location: ' . $secureurl->encode('error.php?ec=14&message=unable to identify ' . $_REQUEST['department']));
       		exit;	
       	}
        list($newly_added_dept_id) = mysql_fetch_row($result);
        ////Set default rights into department//////
        $num_rows = sizeof($data_array);
        for($index = 0; $index < $num_rows; $index++)
       	{
       		$query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "dept_perms (fid, dept_id, rights) values(".$data_array[$index][0].','. $newly_added_dept_id.','. $data_array[$index][1].')';
       		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());        
       	}
       	header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
// UPDATE Department
elseif(isset($_REQUEST['updatedepartment']))
{ 
    // Make sure they are an admin
    if (!$user_obj->isAdmin()){
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    } 
    //Check to see if this department is already in DB
	$query = "SELECT name from " . $GLOBALS['CONFIG']['table_prefix'] . "department where name=\"" . addslashes($_REQUEST['name']) . '" and id!=' . $_REQUEST['id'];
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 0)
    {
       	header('Location: ' . $secureurl->encode('error.php?ec=3&last_message=' . $_REQUEST['name'] . ' already exist in the database'));
    	exit;
    }    
	$query = "UPDATE " . $GLOBALS['CONFIG']['table_prefix'] . "department SET name='" . addslashes($_REQUEST['name']) ."' where id='$_REQUEST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // back to main page
    $_REQUEST['last_message'] = urlencode('Department successfully updated - name=' . $_REQUEST['name'] . '- id=' . $_REQUEST['id']);
    header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
elseif(isset($_REQUEST['deletedepartment']))
{
    
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
	// Delete department
        $query = "DELETE from " . $GLOBALS['CONFIG']['table_prefix'] . "department where id='$_REQUEST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $last_message = urlencode('Department (' . $_REQUEST['id'] . ') successfully deleted');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}

// Add Category
elseif(@$_REQUEST['submit']=='Add Category')
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
        $query = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "category (name) VALUES ('". addslashes($_REQUEST['category']) ."')";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_REQUEST['last_message'] = urlencode('Category successfully added');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
// Delete department
elseif(isset($_REQUEST['deletecategory']))
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
        $query = "DELETE from " . $GLOBALS['CONFIG']['table_prefix'] . "category where id='$_REQUEST[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_REQUEST['last_message'] = urlencode('Category (' . $_REQUEST['id'] . ') successfully deleted');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
// UPDATE Category
elseif(isset($_REQUEST['updatecategory']))
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
        $query = "UPDATE " . $GLOBALS['CONFIG']['table_prefix'] . "category SET name='". addslashes($_REQUEST['name']) ."' where id='$_REQUEST[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_REQUEST['last_message'] = urlencode('Category ' . $_REQUEST['name'] . ' successfully updated');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}

else
{
	echo 'Nothing to do';
}
?>
