<?php
/*
All source code copyright and proprietary Melonfire, 2001. All content, brand names and trademarks copyright and proprietary Melonfire, 2001. All rights reserved. Copyright infringement is a violation of law.

This source code is provided with NO WARRANTY WHATSOEVER. It is meant for illustrative purposes only, and is NOT recommended for use in production environments. 

Read more articles like this one at http://www.melonfire.com/community/columns/trog/ and http://www.melonfire.com/
*/

// edit.php - edit file properties

// check session and $id
session_start();
if (!session_is_registered('SESSION_UID'))
{
  header('Location:error.php?ec=1');
  exit;
}

if (!$id || $id == '')
{
  header('Location:error.php?ec=2');
  exit;
}
include('config.php');
$connection = mysql_connect($hostname, $user, $pass) or die ("Unable to connect!");
if (!$submit)
// form not yet submitted, display initial form
{
	draw_header('File Properties Modification');
	draw_menu($SESSION_UID);
	draw_status_bar('Edit Document Properties', $message);
	$data_id = $id;
	// includes
	$query ="SELECT user.department from user where user.id=$SESSION_UID";
	//echo($database); echo($query); echo($connection);
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	if(mysql_num_rows($result) != 1)
	{
	  header('Location:error.php?ec=14');
	  exit; //non-unique error
	}
	list($current_user_dept) = mysql_fetch_row($result);
	$query = "SELECT default_rights from data where data.id = $id";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	if(mysql_num_rows($result) != 1)
	{
		header('Location: error.php?ec=14&message=Error locating file id '. $filedata->getId());
		exit;
	}
	list($default_rights) = mysql_fetch_row($result);
?>
	<Script Language="JavaScript">
	 //define a class like structure to hold multiple data
    		function Department(name, id, rights)
    		{
       			this.name = name;
        		this.id = id;
        		this.rights = rights;
        		this.isset_flag = false;
        		if (typeof(_department_prototype_called) == "undefined")
        		{
             			_department_prototype_called = true;
            		 	Department.prototype.getName = getName;
            			Department.prototype.getId = getId;
            			Department.prototype.getRights = getRights;
             			Department.prototype.setName = setName;
            	 		Department.prototype.setId = setId;
             			Department.prototype.setRights = setRights;
             			Department.prototype.issetFlag = issetFlag;
             			Department.prototype.setFlag = setFlag;

        		}
	    		function setFlag(set_boolean)
	    		{	this.isset_flag = set_boolean;	}

       			function getName()
        		{       return this.name;		}

       			function getId()
        		{       return this.id;	                }
			
				function getRights()
				{	return parseInt(this.rights);		}

				function setRights(rights)
        		{       this.rights = parseInt(rights); }

       	 		function setName(name)
        		{       this.name = name;               }

				function setId(id)
            	{       this.id = id;         }

				function issetFlag()
            	{       return this.isset_flag;         }
    		} //end class

	var default_Setting_pos = 0;
	var all_Setting_pos = 1;
	var departments = new Array();
	var default_Setting = new Department("Default Setting for Unset Department", 0, <?php echo $default_rights; ?>);
	var all_Setting = new Department("All", 0, 0);
	departments[all_Setting_pos] = all_Setting;
	departments[default_Setting_pos] = default_Setting;
<?php
	$query = "SELECT name, dept_id, rights FROM department, dept_perms  WHERE department.id = dept_perms.dept_id and dept_perms.fid = $id ORDER by name";
	$result = mysql_db_query ($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	$dept_data = $result;
	$index = 0;
  	while( list($dept_name, $dept_id, $rights) = mysql_fetch_row($result) )
  	{    
    	echo "\t" . 'departments[' . ($index+2) . '] = new Department("' . $dept_name . '", "' . $dept_id . '", "' . $rights . "\");\n";
  	    $index++;
  	}
  //These are abstractive departments.  There are no discrete info in the database
  echo '</Script>' . "\n";

// open a connection

	
	// query to obtain current properties and rights 
//	$query = "SELECT category, realname, description, comment FROM data WHERE id = '$id' AND status = '0' AND owner = '$SESSION_UID'";
//	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	$filedata = new FileData($id, $connection, $database);
	$filedata->setId($id);
	// error check
	if( !$filedata->exists() ) //if (mysql_num_rows($result) <= 0)
	{
		header('Location:error.php?ec=2');
		exit;
	}
	else
	{
		// obtain data from resultset
		//list($category, $realname, $description, $comment) = mysql_fetch_row($result);
		//mysql_free_result($result);
		$category = $filedata->getCategory();
		$realname = $filedata->getName();
		$description = $filedata->getDescription();
		$comment = $filedata->getComment();
		// display the form
?>
		<p>
		<center>
		<table border="0" cellspacing="5" cellpadding="5">
		<form name=main action="<?php  echo $_SERVER['PHP_SELF']; ?>" method="POST">
		<input type="hidden" name="id" value="<?php  echo $id; ?>">
	
		<tr>
		<td valign="top">Name</td>
		<td colspan="3"><b><?php  echo $realname; ?></b></td>
		</tr>
			
		<tr>
		<td valign="top">Category</td>
		<td colspan="3"><select name="category">
<?php
		// query for category list
		$query = "SELECT id, name FROM category ORDER BY name";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
		while(list($ID, $CATEGORY) = mysql_fetch_row($result))
		{
			$str = '<option value="' . $ID . '"';
			// pre-select current category
			if ($category == $ID) 
			{ 
				$str .= ' selected'; 
			}
			$str .= '>' . $CATEGORY . '</option>';
			echo $str;
		}
		mysql_free_result($result);
?>
		</select></td>
		</tr>
		<!-- Select Department to own file -->
        <TR>
	    <TD><B>Department</B></TD>
     	<TD COLSPAN="3"><SELECT NAME="dept_drop_box" onChange ="loadDeptData(this.selectedIndex, this.name)">
		<option value="0"> Select a Department</option>
		<option value="1"> Default Setting for Unset Department</option>
		<option value="2"> All Departments</option>
<?php
		// query to get a list of department 
		$query = "SELECT id, name FROM department ORDER BY name";
		$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
        //since we want value to corepodant to group id, 2 must be added to compesate for the first two none group related options.
        while(list($dept_id, $name) = mysql_fetch_row($result))
        {
		  $id+=2;
		  echo '	<option value="' . $dept_id . '" name="' . $name . '">' . $name . '</option> ' . "\n";  
        }
		mysql_free_result ($result);
?>
        </TD></SELECT>
		</TR>
    	<TR>
		<!-- Loading Authority radio_button group -->
		<TD>Authority: </TD> <TD>  	
<?php
      	$query = "SELECT RightId, Description FROM rights order by RightId";
      	$result = mysql_db_query($database, $query, $connection) or die("Error in querry: $query. " . mysql_error());
      	while(list($RightId, $Description) = mysql_fetch_row($result))
      	{
      		echo $Description . ' <input type="radio" name="' . $Description . '" value="' . $RightId . '" onClick="setData(this.name)"> | ' . "\n";
      	}
     
	$query = "SELECT department.name, dept_perms.dept_id, dept_perms.rights FROM dept_perms, department where dept_perms.dept_id = department.id and fid = ".$filedata->getId()." ORDER BY name";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	while( list($dept_name, $dept_id, $rights) = mysql_fetch_row($result) )
	{
	      echo "\n\t" . '<input type="hidden" name="' . space_to_underscore($dept_name) . '" value=' . $rights . '>';
	}
	echo "\n\t" . '<input type="hidden" name="default_Setting" value=' . $default_rights . '>';
?>
	</td>
	</tr>
	<tr>
	<td valign="top">Description</td>
	<td colspan="3"><input type="Text" name="description" size="50" value="<?php  echo $description; ?>"></td>
	</tr>
	<tr>
	<td valign="top">Comment</td>
	<td colspan="3"><textarea name="comment" rows="4"><?php  echo $comment; ?></textarea></td>
	</tr>
	</table>
	<table border="1" cellspacing="0" cellpadding="3">
	<tr>
	<td valign="top"><b><i>Forbidden</i> rights</b></td>
	<td valign="top"><b><i>View</i> rights</b></td>
	<td valign="top"><b><i>Read</i> rights</b></td>
	<td valign="top"><b><i>Modify</i> rights</b></td>
	<td valign="top"><b><i>Admin</i> rights</b></td>
	</TR>
	<!--/////////////////////////////////////////////////////FORBIDDEN////////////////////////////////////////////-->
	<TR>
<?php 
	$id = $data_id;
	// GET ALL USERS
	$query = "SELECT id from user order by username";
	$result = mysql_db_query($database, $query, $connection) or die ( "Error in query(forbidden): " .$query . mysql_error() );
	$all_users = array();
	for($i = 0; $i<mysql_num_rows($result); $i++)
	{
		list($uid) = mysql_fetch_row($result);
		$all_users[$i] = new User($uid, $connection, $database);
	}
	//  LIST ALL FORBIDDEN USERS FOR THIS FILE
	$filedata = new FileData($id, $connection, $database);
	$filedata->setId( $id );
	$user_forbidden_array = $filedata->getForbiddenRightUserIds();
	$found = false;
	echo '<td><select name="forbidden[]" multiple size=10>' . "\n\t";
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_forbidden_array); $u++)
		{
			if($all_users[$a]->getId() == $user_forbidden_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_forbidden_array);
			}
		}
		if( !$found )
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select></td>
	<!--/////////////////////////////////////////////////////VIEW[]////////////////////////////////////////////-->
	<td><select name="view[]" multiple size = 10>
<?php
	$user_view_array = $filedata->getViewRightUserIds();
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_view_array); $u++)
		{
			if($all_users[$a]->getId() == $user_view_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_view_array);
			}
		}
		if( !$found )
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select></td>

	<!--/////////////////////////////////////////////////////READ[]////////////////////////////////////////////-->
	<td><select name="read[]" multiple size="10">
<?php 
	$user_read_array = $filedata->getReadRightUserIds();
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_view_array); $u++)
		{
			if($all_users[$a]->getId() == $user_view_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_view_array);
			}
		}
		if( !$found )
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select></td>

	<!--/////////////////////////////////////////////////////MODIFY[]////////////////////////////////////////////-->
	<td><select name="modify[]" multiple size = 10>
<?php 
	$user_write_array = $filedata->getWriteRightUserIds();
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_view_array); $u++)
		{
			if($all_users[$a]->getId() == $user_view_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_view_array);
			}
		}
		if( !$found )
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
	?>
	</select></td>

	<!--/////////////////////////////////////////////////Admin/////////////////////////////////////////////////////-->
	<td><select name="admin[]" multiple size = 10>
<?php 
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_view_array); $u++)
		{
			if($all_users[$a]->getId() == $user_view_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_view_array);
			}
		}
		if( !$found )
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}

	//clean up	
	//mysql_free_result ($result);
	mysql_free_result ($result2);
	mysql_close($connection);
?>
	</select></td>
	</tr>
	</table>
	<table>
	<tr>
	
	<td colspan="4" align="center"><input type="Submit" name="submit" value="Update Document Properties"></td>
	<td colspan="4" align="center"><input type="Reset" name="reset" value="Reset" onclick="reload()"></td>
	</tr>
	<table>
	</form>
	</table>
	</center>
	</body>
	</html>
<?php 
	}//end else
}
else
{
	// form submitted, process data
	$filedata = new FileData($id, $connection, $database);
	$filedata->setId($id);
	// check submitted data
	// at least one user must have "view" and "modify" rights
	if (sizeof($view) <= 0 or sizeof($modify)<= 0 or sizeof($read)<= 0 or sizeof ($admin)<= 0) { header("Location:error.php?ec=12"); exit; }
	
	// query to verify
	$query = "SELECT status FROM data WHERE id = '$id' and status = '0'";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	
	if(mysql_num_rows($result) <= 0)
	{
		header('Location:error.php?ec=2'); 
		exit; 
	}
	// update db with new information	
	mysql_escape_string($query = "UPDATE data SET category='$category', description='".addslashes($description)."', comment='".addslashes($comment)."', default_rights=$default_Setting WHERE id = '$id'");
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	
	// clean out old permissions
	$query = "DELETE FROM user_perms WHERE fid = '$id'";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());

	$result_array = advanceCombineArrays($admin, $filedata->ADMIN_RIGHT, $write, $filedata->WRITE_RIGHT);
	$result_array = advanceCombineArrays($result_array, 'NULL', $read, $filedata->READ_RIGHT);
	$result_array = advanceCombineArrays($result_array, 'NULL', $view, $filedata->VIEW_RIGHT);
	$result_array = advanceCombineArrays($result_array, 'NULL', $forbidden, $filedata->FORBIDDEN_RIGHT);
	//display_array2D($result_array);
	for($i = 0; $i<sizeof($result_array); $i++)
	{
		$query = "INSERT INTO user_perms (fid, uid, rights) VALUES($id, '".$result_array[$i][0]."','". $result_array[$i][1]."')";
		$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query" .mysql_error());;
	}
	//UPDATE Department Rights into dept_perms
	$query = "SELECT name, id FROM department ORDER BY name";
	$result = mysql_db_query($database, $query, $connection) or die("Error in query: $query. " . mysql_error() );
	while( list($dept_name, $id) = mysql_fetch_row($result) )
	{
		$string=space_to_underscore($dept_name);
		$query = "UPDATE dept_perms SET rights =\"".$$string."\" where fid=".$filedata->getId()." and dept_perms.dept_id =$id";
		$result2 = mysql_db_query($database, $query, $connection) or die("Error in query: $query. " . mysql_error() );
	}
	// clean up
	mysql_close($connection);
	mysql_freeresult($result);
	$message = 'Document successfully updated';
	header('Location: out.php?message=' . $message);
	exit;
}
?>
<SCRIPT LANGUAGE="JavaScript">
	var index = 0;
    var index2 = 0;
	var begin_Authority;
    var end_Authority;
    var frm_main = document.main;
    var dept_drop_box = frm_main.dept_drop_box;
    //Find init position of Authority
    while(frm_main.elements[index].name != "forbidden")
    {       index++;        }
	index2 = index;         //continue the search from index to avoid unnessary iteration
	// Now index contains the position of the view radio button
        //Next search for the position of the admin radio button
    while(frm_main.elements[index2].name != "admin")
    {       index2++;       }
    //Now index2 contains the position of the admin radio button
    //Set the size of the array
    begin_Authority = index;
    end_Authority = index2;

/////////////////////Defining event-handling functions///////////////////////////////////////////////////////
    var num_of_authorities = 4;
    function showData()
	{
		alert(frm_main.elements["Information_Systems"].value);
		alert(frm_main.elements["Test"].value);
		alert(frm_main.elements["Toxicology"].value);
	}
	function test()
	{
		alert(frm_main.elements["default_Setting"].value);
	}
	
	//loadData(_selectedIndex) load department data array
	//loadData(_selectedIndes) will only load data at index=_selectedIndex-1 of the array since
	//since _selectedIndex=0 is the "Please choose a department" option
	//when _selectedIndex=0, all radio button will be cleared.  No department[] will be set
	function loadDeptData(_selectedIndex, dropbox_name)
    {
    	if(_selectedIndex > 0)  //does not load data for option 0
    	{
    		switch(departments[(_selectedIndex-1)].getRights())
			{
            	case -1:
            		frm_main.forbidden.checked = true;
					deselectOthers("forbidden");
					break;
				case 0:
					frm_main.none.checked = true;
					deselectOthers("none");
					break;
				case 1:
                    frm_main.view.checked = true;
					deselectOthers("view");
                    break;
                case 2:
					frm_main.read.checked = true;
					deselectOthers("read");
                    break;
                case 3:
					frm_main.write.checked = true;
                    deselectOthers("write");
                    break;
                case 4:
					frm_main.admin.checked = true;
					deselectOthers("admin");
                break;				
				default: break;
             }
                }
		else
        {
			index = begin_Authority;
            while(index <= end_Authority)
            {
				frm_main.elements[index++].checked = false;
            }
        }
    }
	//return weather or not a department name is a department
	function isDepartment(department_name)
	{
		index = 0;
		while(index < departments.length)
		{
			if(departments[index++].getName() == department_name)
				return true;
		}
		return false;
	}
	function isFormElements(department_name)
	{
		index = 0;
		while(index < frm_main.elements.length)
		{
			index2 = 0;
			while(index2<documents.length)
			{
				if(frm_main.elements[index]==documents[index2++].getName())
					return true;
			}
			index++
		}
		return false;
	}
	//Deselect other button except the button with the name stored in selected_rb_name
	//Design to control the rights radio buttons
	function deselectOthers(selected_rb_name)
    {
		var index = begin_Authority;
    	while(index <= end_Authority)
        {
			if(frm_main.elements[index].name != selected_rb_name)
            {
            	frm_main.elements[index].checked = false;
            }
			index++;
    	}
    }
	function spTo_(string)
	{
		return string.replace(" ", "_");
	}
	function setData(selected_rb_name)
	{
		var index = 0;
		var current_selected_dept =  dept_drop_box.selectedIndex - 1;
		var current_dept = departments[current_selected_dept];
		deselectOthers(selected_rb_name);
		//set right into departments
		departments[current_selected_dept].setRights(frm_main.elements[selected_rb_name].value); 
		//Since the All and Defualt department are abstractive departments, hidden fields do not exists for them.
		if(current_selected_dept-2 >= 0) // -1 from above and -2 now will set the first real field being 0
		{
			//set department data into hidden field
			frm_main.elements[spTo_( current_dept.getName() )].value = current_dept.getRights();		
		}
		departments[current_selected_dept].setFlag("true");
		if(  current_selected_dept == default_Setting_pos )  //for default user option
        {
			frm_main.elements['default_Setting'].value = frm_main.elements[selected_rb_name].value;
        	while (index< dept_drop_box.length)
        	{
            	//do not need to set "All Department" and "Default Department"  they are only abstracts
				if(departments[index].issetFlag() == false && index != all_Setting_pos && index != default_Setting_pos)
                {
                	//set right radio buton's value into all Department that is available on the database
					departments[index].setRights(frm_main.elements[selected_rb_name].value); 
					//set right onto hidden valid hidden fields to communicate with php
					frm_main.elements[spTo_(departments[index].getName())].value = frm_main.elements[selected_rb_name].value;
				}
                index++;
            }
			index = 0;
    	}
		if( current_selected_dept == all_Setting_pos) //for all user option. linked with predefine value above.
		{
			index = 0;
			while(index < dept_drop_box.length)
			{
				if(index != default_Setting_pos && index != all_Setting_pos) //Don't set default and All
				{
					//All setting acts like the user actually setting the right for all the department. -->setFlag=true
					departments[index].setFlag(true);
					//Set rights into department array
					departments[index].setRights(frm_main.elements[selected_rb_name].value );
					//Set rights into hidden fields for php
					frm_main.elements[spTo_(departments[index].getName())].value = frm_main.elements[selected_rb_name].value;
				}
				index++;
			}
		} 
				
	}
</SCRIPT>

