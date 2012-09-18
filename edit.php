<?php
/*
edit.php - edit file properties
Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
Copyright (C) 2008-2011 Stephen Lawrence Jr.

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
include('odm-load.php');
include('udf_functions.php');
require_once("AccessLog_class.php");
 
$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if(strchr($_REQUEST['id'], '_') )
{
	    header('Location:error.php?ec=20');
}
if (!isset($_SESSION['uid']))
{
  header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
  exit;
}

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '')
{
	header('Location:error.php?ec=2');
  	exit;
}

$filedata = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);

if( $filedata->isArchived() )
{
    header('Location:error.php?ec=21');
}

// form not yet submitted, display initial form
if (!isset($_REQUEST['submit']))
{
	draw_header(msg('area_update_file'), $last_message);
	checkUserPermission($_REQUEST['id'], $filedata->ADMIN_RIGHT, $filedata);
	$data_id = $_REQUEST['id'];
	// includes
	$query ="SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=$_SESSION[uid]";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	if(mysql_num_rows($result) != 1)
	{
	  header('Location:error.php?ec=14');
	  exit; //non-unique error
	}	
	$query = "SELECT default_rights FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = $data_id";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	if(mysql_num_rows($result) != 1)
	{
		header('Location: error.php?ec=14&message=Error locating file id '. $filedata->getId());
		exit;
	}
	list($default_rights) = mysql_fetch_row($result);
?>
<script type="text/javascript">
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
	var default_Setting = new Department("<?php echo msg('label_default_for_unset')?>", 0, <?php echo $default_rights; ?>);
	var all_Setting = new Department("All", 0, 0);
	departments[all_Setting_pos] = all_Setting;
	departments[default_Setting_pos] = default_Setting;
<?php
	$query = "SELECT name, dept_id, rights FROM {$GLOBALS['CONFIG']['db_prefix']}department, {$GLOBALS['CONFIG']['db_prefix']}dept_perms  WHERE {$GLOBALS['CONFIG']['db_prefix']}department.id ={$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id and {$GLOBALS['CONFIG']['db_prefix']}dept_perms.fid = $data_id ORDER by name";
	$result = mysql_query ($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

	$index = 0;
  	while( list($dept_name, $dept_id, $rights) = mysql_fetch_row($result) )
  	{    
    	echo "\t" . 'departments[' . ($index+2) . '] = new Department("' . $dept_name . '", "' . $dept_id . '", "' . $rights . "\");\n";
  	    $index++;
  	}
  //These are abstractive departments.  There are no discrete info in the database
?>
</script>
<?php
	$filedata = new FileData($data_id, $GLOBALS['connection'], DB_NAME);
	// error check
	if( !$filedata->exists() ) 
	{
		header('Location:error.php?ec=2');
		exit;
	}
	else
	{
		$category = $filedata->getCategory();
		$realname = $filedata->getName();
		$description = $filedata->getDescription();
		$comment = $filedata->getComment();
		$owner_id = $filedata->getOwner();
                $department = $filedata->getDepartment();
		// display the form
?>
		<p>
		<table border="0" cellspacing="5" cellpadding="5">
		<form name=main action="<?php  echo $_SERVER['PHP_SELF']; ?>" method="POST">
		<input type="hidden" name="id" value="<?php  echo $_REQUEST['id']; ?>">
	
		<tr>
		<td><?php echo msg('label_name')?></td>
		<td colspan="3"><b><?php  echo $realname; ?></b></td>
		</tr>
		<tr id="ownerSelect">
		<td><?php echo msg('editpage_assign_owner')?></td>
		<td colspan="3"><b>
		<select name="file_owner">
			<?php  
			$lusers = getAllUsers();
			for($i = 0; $i < sizeof($lusers); $i++)
			{
				if($lusers[$i][0] == $owner_id)
				{	
					echo '<option value="' . $lusers[$i][0] . '" selected>' . $lusers[$i][1] . ' - ' . $lusers[$i][2] . '</option>' . "\n";
				}
				else
				{
					echo '<option value="' . $lusers[$i][0] . '">' . $lusers[$i][1] . ' - ' . $lusers[$i][2] . '</option>' . "\n";
				}
			}
			?>
		</select>
		</b></td>
		</tr>
                <tr id="deptOwnerSelect">
		<td><?php echo msg('editpage_assign_department')?></td>
		<td colspan="3"><b>
		<select name="file_department">
			<?php
                        // query to get a list of available departments
                        $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                        //////////////////Forbidden////////////////////
                        while(list($id, $name) = mysql_fetch_row($result))
                        {
                            if($id == $department)
                            {
                                $selected = 'selected';
                            }
                            else
                            {
                                $selected = '';
                            }
                            echo "<option value=\"$id\" $selected>$name</option>";
                        }
			?>
		</select>
		</b></td>
		</tr>
		<tr>
		<td><a class="body" href="help.html#Add_File_-_Category"  onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('category')?></a></td>
		<td colspan="3"><select name="category">
<?php
		// query for category list
		$query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
		while(list($catid, $cat) = mysql_fetch_row($result))
		{
			$str = '<option value="' . $catid . '"';
			// pre-select current category
			if ($category == $catid)
			{ 
				$str .= ' selected'; 
			}
			$str .= '>' . $cat . '</option>';
			echo $str;
		}
		mysql_free_result($result);
?>
		</select></td>
		</tr>
<?php
		udf_edit_file_form();
?>
		<!-- Select Department to own file -->
        <TR id="departmentSelect">
            
	    <TD>
                <a class="body" href="help.html#Add_File_-_Department" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('department')?></a></TD>
     	<TD COLSPAN="3">
            <hr />
            <SELECT NAME="dept_drop_box" onChange ="loadDeptData(this.selectedIndex, this.name)">
		<option value="0"><?php echo msg('label_select_a_department')?></option>
		<option value="1">(<?php echo msg('label_default_for_unset')?>)</option>
		<option value="2">(<?php echo msg('label_all_departments')?>)</option>
<?php
		// query to get a list of department 
		$query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
		$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        //since we want value to corepodant to group id, 2 must be added to compesate for the first two none group related options.
        while(list($dept_id, $name) = mysql_fetch_row($result))
        {
		  //$id+=2;
		  echo '	<option value="' . $dept_id . '" name="' . $name . '">' . $name . '</option> ' . "\n";  
        }
		mysql_free_result ($result);
?>          
        </TD></SELECT>
		</TR>
    	<TR id="authorityRadio">
		<!-- Loading Authority radio_button group -->
		<TD><a class="body" href="help.html#Add_File_-_Authority" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_department_authority')?></a></TD> 
                <TD>
<?php                 
      	$query = "SELECT RightId, Description FROM {$GLOBALS['CONFIG']['db_prefix']}rights order by RightId";
      	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in querry: $query. " . mysql_error());
      	while(list($RightId, $Description) = mysql_fetch_row($result))
      	{
      		echo $Description . ' <input type="radio" name="' . $Description . '" value="' . $RightId . '" onClick="setData(this.name)"> | ' . "\n";
      	}
     
	$query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}department.name, {$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id, {$GLOBALS['CONFIG']['db_prefix']}dept_perms.rights FROM {$GLOBALS['CONFIG']['db_prefix']}dept_perms, {$GLOBALS['CONFIG']['db_prefix']}department WHERE {$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id = {$GLOBALS['CONFIG']['db_prefix']}department.id and fid = ".$filedata->getId()." ORDER BY name";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while( list($dept_name, $dept_id, $rights) = mysql_fetch_row($result) )
	{
	      echo "\n\t" . '<input type="hidden" name="' . space_to_underscore($dept_name) . '" value=' . $rights . '>';
	}
	echo "\n\t" . '<input type="hidden" name="default_Setting" value=' . $default_rights . '>';
?><hr />
	</td>
	</tr>
	<tr>
	<td><a class="body" href="help.html#Add_File_-_Description" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_description')?></a></td>
	<td colspan="3"><input type="Text" name="description" size="50" value="<?php  echo str_replace('"', '&quot;', $description); ?>"></td>
	</tr>
	<tr>
	<td><a class="body" href="help.html#Add_File_-_Comment" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_comment')?></a></td>
	<td colspan="3"><textarea name="comment" rows="4"><?php  echo $comment; ?></textarea></td>
	</tr>
	</table>
	<table id="specificUserPerms" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td colspan="5"><b><?php echo msg('label_specific_permissions')?></b></td>
            </tr>
            <tr>
            
	<!--/////////////////////////////////////////////////////FORBIDDEN////////////////////////////////////////////-->

<?php 
	$id = $data_id;
	// GET ALL USERS
	$query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}user order by username";
	$result = mysql_query($query, $GLOBALS['connection']) or die ( "Error in query(forbidden): " .$query . mysql_error() );
	$all_users = array();
	for($i = 0; $i<mysql_num_rows($result); $i++)
	{
		list($my_uid) = mysql_fetch_row($result);
		$all_users[$i] = new User($my_uid, $GLOBALS['connection'], DB_NAME);
	}
	//  LIST ALL FORBIDDEN USERS FOR THIS FILE
	$lquery = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = $id AND rights=" . $filedata->FORBIDDEN_RIGHT;
	$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . "\n<BR>" . mysql_error());

	for($i = 0; $i < mysql_num_rows($lresult); $i++ )
	{
		list($user_forbidden_array[$i]) = mysql_fetch_row($lresult);
	}

	$found = false;
	echo '<td valign="top" align="center">
            <a class="body" href="help.html#Rights_-_Forbidden" onClick="return popup(this, \'Help\')" style="text-decoration:none">' . msg('label_forbidden') . '</a><br />
            <select class="multiView" name="forbidden[]" multiple="multiple" size=10 onchange="changeForbiddenList(this, this.form);">' . "\n\t";
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		if(isset($user_forbidden_array))
		{
				for($u = 0; $u<sizeof($user_forbidden_array); $u++)
				{
						if($all_users[$a]->getId() == $user_forbidden_array[$u])
						{
								echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
								$found = true;
								$u = sizeof($user_forbidden_array);
						}
				}
		}
		if(!$found)
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select>
        </td>
        <td>
	<!--/////////////////////////////////////////////////////VIEW[]////////////////////////////////////////////-->
	<a class="body" href="help.html#Rights_-_View" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_view')?></a><br/>            
            <select class="multiView" name="view[]" multiple="multiple" size = 10 onchange="changeList(this, this.form);">
                
<?php
	$lquery = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = $id AND rights>=" . $filedata->VIEW_RIGHT;
	$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . "\n<BR>" . mysql_error());
	for($i = 0; $i < mysql_num_rows($lresult); $i++ )
		list($user_view_array[$i]) = mysql_fetch_row($lresult);
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
		if(!$found)
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select>
        </td>
        <td>

	<!--/////////////////////////////////////////////////////READ[]////////////////////////////////////////////-->
        <a class="body" href="help.html#Rights_-_Read" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_read')?></a><br />
	<select class="multiView" name="read[]" multiple="multiple" size="10" onchange="changeList(this, this.form);">
	<?php 
	$lquery = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = $id AND rights>=" . $filedata->READ_RIGHT;
	$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . "\n<BR>" . mysql_error());
	for($i = 0; $i < mysql_num_rows($lresult); $i++ )
		list($user_read_array[$i]) = mysql_fetch_row($lresult);
	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_read_array); $u++)
		{
			if($all_users[$a]->getId() == $user_read_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_read_array);
			}
		}
		if(!$found)
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
?>
	</select>
        </td>
        <td>

	<!--/////////////////////////////////////////////////////MODIFY[]////////////////////////////////////////////-->
        <a class="body" href="help.html#Rights_-_Modify" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_modify')?></a><br />
	<select class="multiView" name="modify[]" multiple="multiple" size = 10 onchange="changeList(this, this.form);">
	<?php 
	$lquery = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = $id AND rights>=" . $filedata->WRITE_RIGHT;
	$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . "\n<BR>" . mysql_error());
	for($i = 0; $i < mysql_num_rows($lresult); $i++ )
        {
		list($user_write_array[$i]) = mysql_fetch_row($lresult);
        }

	for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_write_array); $u++)
		{
			if($all_users[$a]->getId() == $user_write_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_write_array);
			}
		}
		if(!$found)
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}
	?>
	</select>
        </td>
        <td>

	<!--/////////////////////////////////////////////////Admin/////////////////////////////////////////////////////-->
        <a class="body" href="help.html#Rights_-_Admin" onClick="return popup(this, 'Help')" style="text-decoration:none"><?php echo msg('label_admin')?></a><br />
	<select class="multiView" name="admin[]" multiple="multiple" size = 10 onchange="changeList(this, this.form);">
	<?php 
	$lquery = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = $id AND rights>=" . $filedata->ADMIN_RIGHT;
	$lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . "\n<BR>" . mysql_error());
	for($i = 0; $i < mysql_num_rows($lresult); $i++ )
        {
		list($user_admin_array[$i]) = mysql_fetch_row($lresult);
        }
        
        for($a = 0; $a<sizeof($all_users); $a++)
	{
		$found = false;
		for($u = 0; $u<sizeof($user_admin_array); $u++)
		{
			if($all_users[$a]->getId() == $user_admin_array[$u])
			{
				echo '<option value="' . $all_users[$a]->getId() . '" selected> ' . $all_users[$a]->getName() . '</option>';
				$found = true;
				$u = sizeof($user_admin_array);
			}
		}
		if(!$found)
		{
			echo '<option VALUE="' . $all_users[$a]->getId() . '">' . $all_users[$a]->getName() . '</option>';
		}
	}

?>
	</select>
        </td>
<?php
    // Call Plugin API
    callPluginMethod('onBeforeEditFile',$id);
?>
	</tr>
	</table>
	<table>
	<tr>
        
	<td colspan="4" align="center"><div class="buttons"><button class="positive" type="Submit" name="submit" value="Update Document Properties"><?php echo msg('button_save')?></button></div></td>
	<td colspan="4" align="center"><div class="buttons"><button class="negative" type="Reset" name="reset" value="Reset" onclick="reload()"><?php echo msg('button_reset')?></button></div></td>
        </div>
	</tr>
	<table>
	</form>
	</table>
<?php 
	}//end else
}
else
{   
        // form submitted, process data
        $fileId = $_REQUEST['id'];
	$filedata = new FileData($fileId, $GLOBALS['connection'], DB_NAME);

        // Call the plugin API
        callPluginMethod('onBeforeEditFileSaved');

        $filedata->setId($fileId);
	// check submitted data
	// at least one user must have "view" and "modify" rights
        if ( !isset($_REQUEST['view']) or !isset($_REQUEST['modify']) or !isset($_REQUEST['read']) or !isset ($_REQUEST['admin']))
        {
            header("Location:error.php?ec=12");
            exit;
        }

        // Check to make sure the file is available
        $status = $filedata->getStatus($fileId);
        if($status != 0)
	{
		header('Location:error.php?ec=2');
		exit;
	}
        
	// update category
        $filedata->setCategory(mysql_real_escape_string($_REQUEST['category']));
        $filedata->setDescription(mysql_real_escape_string($_REQUEST['description']));
        $filedata->setComment(mysql_real_escape_string($_REQUEST['comment']));
        $filedata->setDefaultRights(mysql_real_escape_string($_REQUEST['default_Setting']));
        if(isset($_REQUEST['file_owner']))
	{
            $filedata->setOwner(mysql_real_escape_string($_REQUEST['file_owner']));
        }
        if(isset($_REQUEST['file_department']))
	{
            $filedata->setDepartment(mysql_real_escape_string($_REQUEST['file_department']));
        }

        // Update the file with the new values
        $filedata->updateData();
        
	udf_edit_file_update();

	// clean out old permissions
	$query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = '$fileId'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	$result_array = array();// init;
	if( isset( $_REQUEST['admin'] ) && isset ($_REQUEST['modify']) )
	{
            $result_array = advanceCombineArrays($_REQUEST['admin'], $filedata->ADMIN_RIGHT, $_REQUEST['modify'], $filedata->WRITE_RIGHT);
        }
	if( isset( $_REQUEST['read'] ) )
	{	
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['read'], $filedata->READ_RIGHT);
        }
	if( isset( $_REQUEST['view'] ) )
	{	
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['view'], $filedata->VIEW_RIGHT);
        }
	if( isset( $_REQUEST['forbidden'] ) )
	{	
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['forbidden'], $filedata->FORBIDDEN_RIGHT);
        }
	//display_array2D($result_array);
	for($i = 0; $i<sizeof($result_array); $i++)
	{
		$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) VALUES($fileId, '".$result_array[$i][0]."','". $result_array[$i][1]."')";
		//echo $query."<br>";
		$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" .mysql_error());;
	}
	
	//UPDATE Department Rights into dept_perms
	$query = "SELECT name, id FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
	while( list($dept_name, $id) = mysql_fetch_row($result) )
	{
		$string=addslashes(space_to_underscore($dept_name));
		$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}dept_perms SET rights ='{$_REQUEST[$string]}' where fid=".$filedata->getId()." and {$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id =$id";
                mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
	}
	// clean up
	mysql_freeresult($result);
	$message = urlencode('Document successfully updated');

        AccessLog::addLogEntry($fileId,'M');
                
        // Call the plugin API
        callPluginMethod('onAfterEditFile',$fileId);
        
        header('Location: details.php?id=' . $fileId . '&last_message=' . $message);
}
?>
<script type="text/javascript">
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
        // Joe Jeskiewicz fix
        var pattern = / /g;
        return string.replace(pattern, "_");
    //  return string.replace(" ", "_");
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
                        // Don't include the "Select a Department" prompt
			while(index < (dept_drop_box.length - 1))
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
	function changeList(select_list, current_form)
	{
		var select_list_array = new Array();
		select_list_array[0] = current_form['view[]']; 
		select_list_array[1] = current_form['read[]']; 
		select_list_array[2] = current_form['modify[]'];
		select_list_array[3] = current_form['admin[]'];
		for( var i=0; i < select_list_array.length; i++)
		{
			if(select_list_array[i] == select_list)
			{
				for(var j=0; j< select_list.options.length; j++)
				{
					if(select_list.options[j].selected)
					{
						for(var k=0; k < i; k++)
						{
							select_list_array[k].options[j].selected=true;	
						}//end for
						current_form['forbidden[]'].options[j].selected=false;
					}//end if
					else
					{
						for(var k=i+1; k < select_list_array.length; k++)
						{
							select_list_array[k].options[j].selected=false;
						}
					}//end else
				}//end for	
			}//end if
		}//end for
	}
	function changeForbiddenList(select_list, current_form)
	{
		var select_list_array = new Array();
		select_list_array[0] = current_form['view[]']; 
		select_list_array[1] = current_form['read[]']; 
		select_list_array[2] = current_form['modify[]'];
		select_list_array[3] = current_form['admin[]'];
		for(var i=0; i < select_list.options.length; i++)
		{
			if(select_list.options[i].selected==true)
			{
				for( var j=0; j < select_list_array.length; j++)
				{
					select_list_array[j].options[i].selected=false;	
				}//end for
			}
		} //end for
	}
</script>
<?php
draw_footer();
