<?php
/*
commitchange.php - provides database commits for various admin tasks
Copyright (C) 2002-2007  Stephen Lawrence, Jon Miner

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
// check for valid session
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
	exit;
}
include('config.php');
include('udf_functions.php');
$secureurl = new phpsecureurl;

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);

// Code added by Chad Blomquist
// Check to make sure they should be here.
if (!$user_obj->isAdmin())
{
    // must be admin unless you are editing yourself.
    if (isset($_REQUEST['submit']) && $_REQUEST['submit'] != 'Update User')
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }
    elseif (isset($_REQUEST['id']) && $_REQUEST['id'] != $_SESSION['uid'])
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }
    $_REQUEST['admin']='no';
}

// Submitted so insert data now
if(isset($_POST['submit']) && 'Add User' == $_POST['submit'])
{
    if (!$user_obj->isAdmin()){
           header('Location:' . $secureurl->encode('error.php?ec=4'));
           exit;
    }
	// Check to make sure user does not already exist
    $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '" . addslashes($_POST['username']) . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    // If the above statement returns more than 0 rows, the user exists, so display error
    if(mysql_num_rows($result) > 0)
    {
 	   header('Location:' . $secureurl->encode('error.php?ec=3'));
           exit;
    }
    else
    {     
    	$phonenumber = @$_POST['phonenumber'];
	   // INSERT into user
       $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user (username, password, department, phone, Email,last_name, first_name) VALUES(". addslashes($_POST['username'])."', password('". addslashes(@$_POST['password']) ."'), '" . addslashes($_POST['department'])."' ,'" . addslashes($phonenumber) . "','". addslashes($_POST['Email'])."', '" . addslashes($_POST['last_name']) . "', '" . addslashes($_POST['first_name']) . '\' )';
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       // INSERT into admin
       $userid = mysql_insert_id($GLOBALS['connection']);
        if (!isset($_POST['admin']))
        {
                $_POST['admin']='';
        }
       $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES('$userid', '$_POST[admin]')";
       $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       if(isset($_POST['reviewer']))
       {
           for($i = 0; $i<sizeof($_POST['department_review']); $i++)
           {
               $dept_rev=$_POST['department_review'][$i];
               $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id, user_id) values('$dept_rev', '$userid')";
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
			$mail_body.='Your randomly generated password is: '.$_POST['password']."\n\n";
			$mail_body.='If you would like to change this to something else once you log in, ';
			$mail_body.='you can do so by clicking on "Preferences" in the status bar.'."\n";
		}
		else 
			$mail_body.='Your password is your UC Davis campus kerberos password.';
		$mail_salute="\n\rSincerely,\n\r$full_name";
		$mail_to = $new_user_obj->getEmailAddress();
		mail($mail_to, $mail_subject, ($mail_greeting.' '.$mail_body.$mail_salute), $mail_headers);
		$_POST['last_message'] = urlencode('User successfully added');
       	header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_POST['last_message']));
    }
}
elseif(isset($_POST['submit']) && 'Update User' == $_POST['submit'])
{
//echo "id=$_POST[id], $_SESSION[uid], " . $user_obj->isAdmin();exit;
    // Check to make sue they are either the user being modified or an admin
    if (($_POST['id'] != $_SESSION['uid']) && !$user_obj->isAdmin()){
           header('Location:' . $secureurl->encode('error.php?ec=4'));
           exit;
    }

    if(!isset($_POST['admin']) || $_POST['admin'] == '')
    {
            $_POST['admin'] = 'no';
    }

	if(!isset($_POST['caller']) || $_POST['caller'] == '')
	{
		$_POST['caller'] = 'admin.php';
    }
	
    // UPDATE admin info
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}admin set admin='". $_POST['admin'] . "' where id = '".$_POST['id']."'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // UPDATE into user
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET username='". addslashes($_POST['username']) ."',";

	if (!empty($_POST['password']))
	{
		$query .= "password = password('". addslashes($_POST['password']) ."'), ";
	}

	if( isset( $_POST['department'] ) )
	{	
        $query.= 'department="' . addslashes($_POST['department']) . '",';	
    }

	if( isset( $_POST['phonenumber'] ) )
	{	
        $query.= 'phone="' . addslashes($_POST['phonenumber']) . '",';	
    }

	if( isset( $_POST['Email'] ) )
	{	
        $query.= 'Email="' . addslashes($_POST['Email']) . '" ,';	
    }

	if( isset( $_POST['last_name'] ) )
	{	
        $query.= 'last_name="' . addslashes($_POST['last_name']) . '",';	
    }

	if( isset( $_POST['first_name'] ) )
	{	
        $query.= 'first_name="' . addslashes($_POST['first_name']) . '" ';	
    }

	$query.= 'WHERE id="' . $_POST['id'] . '"';
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
    if ($user_obj->isAdmin())
    {
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer WHERE user_id = '{$_POST['id']}'";
        $result = mysql_query($query, $GLOBALS['connection'])
            or die("Error in query: $query". mysql_error());
        if(isset($_REQUEST['reviewer']))
        {
            if(isset($_REQUEST['department_review']))
            {
                for($i = 0; $i<sizeof($_REQUEST['department_review']); $i++)
                {
                    $dept_rev = addslashes($_REQUEST['department_review'][$i]);
                    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id,user_id) VALUES('$dept_rev', '{$_POST['id']}')";
                    $result = mysql_query($query,$GLOBALS['connection']) or die("Error in query: $query". mysql_error());
                }
            }
        }
    }

	// back to main page
	if(!isset($_POST['caller']))
	{	
        $_POST['caller'] = 'admin.php';	
    }

    $_POST['last_message'] = urlencode('User successfully updated');
    header('Location: ' . $_POST['caller'] . '?last_message=' . $_POST['last_message']);
}
// Delete USER
elseif(isset($_POST['submit']) && 'Delete User' == $_POST['submit'])
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        }
    
        // form has been submitted -> process data
        // DELETE admin info
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = '{$_POST['id']}'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE user info
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = '$_POST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // DELETE perms info
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE uid = '$_POST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // Change data info to nobody
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET owner='0' where owner = '$_POST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // back to main page
        $_POST['last_message'] = urlencode($_POST['id'] . ' User successfully deleted');
        header('Location:' . $secureurl->encode('admin.php?last_message=' . $_POST['last_message']));
}
//Add Departments
elseif(isset($_POST['submit']) && 'Add Department' == $_POST['submit'])
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 

		//Check to see if this department is already in DB
		$query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department where name='" . addslashes($_POST['department']) . "'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        if(mysql_num_rows($result) != 0)
        {
	       	header('Location:' . $secureurl->encode(' error.php?ec=3&message=' . $_POST['department'] . ' already exist in the database'));
        	exit;
        }
		$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}department (name) VALUES ('" . addslashes($_POST['department']) . '\')';
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_POST['last_message'] = urlencode('Department successfully added');
        /////////Give New Department data's default rights///////////
        ////Get all default rights////
        $query = "SELECT id, default_rights FROM {$GLOBALS['CONFIG']['db_prefix']}data";
       	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
        $num_rows = mysql_num_rows($result);
        $data_array = array();

       	for($index = 0; $index< $num_rows; $index++)
        {
       		list($data_array[$index][0], $data_array[$index][1]) = mysql_fetch_row($result);
        }

       	mysql_free_result($result);
       	//////Get the new department's id////////////
       	$query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE name = '" . addslashes($_POST['department']) . "'";
       	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
       	$num_rows = mysql_num_rows($result);
       	if( $num_rows != 1 )
       	{
       		header('Location: ' . $secureurl->encode('error.php?ec=14&message=unable to identify ' . $_POST['department']));
       		exit;	
       	}

        list($newly_added_dept_id) = mysql_fetch_row($result);
        ////Set default rights into department//////
        $num_rows = sizeof($data_array);
        for($index = 0; $index < $num_rows; $index++)
       	{
       		$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, dept_id, rights) values(".$data_array[$index][0].','. $newly_added_dept_id.','. $data_array[$index][1].')';
       		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());        
       	}
       	header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_POST['last_message']));
}
// UPDATE Department
elseif(isset($_POST['submit']) && 'Update Department' == $_POST['submit'])
{ 
    // Make sure they are an admin
    if (!$user_obj->isAdmin()){
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    } 
    //Check to see if this department is already in DB
	$query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department where name=\"" . addslashes($_POST['name']) . '" and id!=' . $_POST['id'];
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 0)
    {
       	header('Location: ' . $secureurl->encode('error.php?ec=3&last_message=' . $_POST['name'] . ' already exist in the database'));
    	exit;
    }    
	$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}department SET name='" . addslashes($_POST['name']) ."' where id='$_POST[id]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // back to main page
    $_POST['last_message'] = urlencode('Department successfully updated - name=' . $_POST['name'] . '- id=' . $_POST['id']);
    header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_POST['last_message']));
}
// Add Category
elseif(@$_REQUEST['submit']=='Add Category')
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}category (name) VALUES ('". addslashes($_REQUEST['category']) ."')";
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
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}category where id='$_REQUEST[id]'";
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
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}category SET name='". addslashes($_REQUEST['name']) ."' where id='$_REQUEST[id]'";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // back to main page
        $_REQUEST['last_message'] = urlencode('Category ' . $_REQUEST['name'] . ' successfully updated');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
elseif(@$_REQUEST['submit']=='Add User Defined Field')
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 

	udf_functions_add_udf();

        $_REQUEST['last_message'] = urlencode('User Defined Field ' . $_REQUEST['display_name'] . ' successfully added');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
elseif(isset($_REQUEST['deleteudf']))
{
        // Make sure they are an admin
        if (!$user_obj->isAdmin()){
            header('Location:' . $secureurl->encode('error.php?ec=4'));
            exit;
        } 
	udf_functions_delete_udf();

        // back to main page
        $_REQUEST['last_message'] = urlencode('User Defined Field (' . $_REQUEST['id'] . ') successfully deleted');
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $_REQUEST['last_message']));
}
else
{
	echo 'Nothing to do';
}
?>
