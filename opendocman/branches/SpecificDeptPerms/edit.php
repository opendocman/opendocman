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
    $data_id = $_REQUEST['id'];

    draw_header(msg('area_update_file'), $last_message);
    checkUserPermission($_REQUEST['id'], $filedata->ADMIN_RIGHT, $filedata);
    // includes
    $query = "SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=$_SESSION[uid]";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    if (mysql_num_rows($result) != 1)
    {
        header('Location:error.php?ec=14');
        exit; //non-unique error
    }
    $query = "SELECT default_rights FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = $data_id";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    if (mysql_num_rows($result) != 1)
    {
        header('Location: error.php?ec=14&message=Error locating file id ' . $filedata->getId());
        exit;
    }
    list($default_rights) = mysql_fetch_row($result);

    $filedata = new FileData($data_id, $GLOBALS['connection'], DB_NAME);
    // error check
    if (!$filedata->exists())
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

        $userListArray = User::getAllUsers();
        $departmentListArray = Department::getAllDepartments();
        $categoryListArray = Category::getAllCategories();
        $forbiddenUsers = $filedata->getForbiddenRightUserIds();
        $viewUsers = $filedata->getViewRightUserIds();
        $readUsers = $filedata->getReadRightUserIds();
        $modifyUsers = $filedata->getWriteRightUserIds();
        $adminUsers = $filedata->getAdminRightUserIds();
        $forbiddenDepartments = $filedata->getForbiddenRightDeptIds();
        $viewDepartments = $filedata->getViewRightDeptIds();
        $readDepartments = $filedata->getReadRightDeptIds();
        $modifyDepartments = $filedata->getModifyRightDeptIds();       
        $adminDepartments = $filedata->getAdminRightDeptIds();
        $udfForm = udf_edit_file_form();

        // Send these vars to the template
        $GLOBALS['smarty']->assign('userListArray', $userListArray);
        $GLOBALS['smarty']->assign('departmentListArray', $departmentListArray);
        $GLOBALS['smarty']->assign('categoryListArray', $categoryListArray);
        $GLOBALS['smarty']->assign('defaultRights', $default_rights);
        $GLOBALS['smarty']->assign('id', $data_id);
        $GLOBALS['smarty']->assign('realname', $realname);
        $GLOBALS['smarty']->assign('category', $category);
        $GLOBALS['smarty']->assign('department', $department);
        $GLOBALS['smarty']->assign('description', $description);
        $GLOBALS['smarty']->assign('comment', $comment);        
        $GLOBALS['smarty']->assign('owner', $owner_id);
        $GLOBALS['smarty']->assign('udfForm', $udfForm);
        $GLOBALS['smarty']->assign('forbiddenUsers', $forbiddenUsers);
        $GLOBALS['smarty']->assign('viewUsers', $viewUsers);
        $GLOBALS['smarty']->assign('readUsers', $readUsers);
        $GLOBALS['smarty']->assign('modifyUsers', $modifyUsers);
        $GLOBALS['smarty']->assign('adminUsers', $adminUsers);
        $GLOBALS['smarty']->assign('forbiddenDepts', $forbiddenDepartments);
        $GLOBALS['smarty']->assign('viewDepts', $viewDepartments);
        $GLOBALS['smarty']->assign('readDepts', $readDepartments);
        $GLOBALS['smarty']->assign('modifyDepts', $modifyDepartments);
        $GLOBALS['smarty']->assign('adminDepts', $adminDepartments);
        
        // Display the beginning of the form
        display_smarty_template('edit_file.tpl');

        // Call Plugin API
        callPluginMethod('onBeforeEditFile', $data_id);
Fb::log($forbiddenDepartments, '$forbiddenDepartments');
        // Show Footer
        display_smarty_template('add_file_footer.tpl');
    }//end else
} 
else
{   
Fb::log($_POST);   
        // form submitted, process data
        $fileId = $_REQUEST['id'];
	$filedata = new FileData($fileId, $GLOBALS['connection'], DB_NAME);

        // Call the plugin API
        callPluginMethod('onBeforeEditFileSaved');

        $filedata->setId($fileId);
	// check submitted data
	// at least one user must have admin rights
//        if (!isset ($_REQUEST['admin']))
//        {
//            header("Location:error.php?ec=12");
//            exit;
//        }

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
        $filedata->setDefaultRights(mysql_real_escape_string($_REQUEST['defaultDepartmentPerms']));
        
        // Set the Owner of the file
        if(isset($_REQUEST['file_owner']))
	{
            $filedata->setOwner(mysql_real_escape_string($_REQUEST['file_owner']));
        }
        
        // Set the Department for the file
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
	if( isset( $_REQUEST['admin']) )
	{
            $result_array = advanceCombineArrays($_REQUEST['admin'], $filedata->ADMIN_RIGHT, $_REQUEST['admin'], $filedata->WRITE_RIGHT);
        }
        if( isset( $_REQUEST['modify'] ) )
	{	
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['modify'], $filedata->WRITE_RIGHT);
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
Fb::log('There is a forbidden department');            
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['forbidden'], $filedata->FORBIDDEN_RIGHT);
        }
Fb::log($result_array, '$result_array');
	//display_array2D($result_array);
	for($i = 0; $i<sizeof($result_array); $i++)
	{
		$query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) VALUES($fileId, '".$result_array[$i][0]."','". $result_array[$i][1]."')";
		//echo $query."<br>";  
Fb::log($query, 'User Perms Query Row:');                
		$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" .mysql_error());;
	}
	
        
        $result_array2 = array();
	//UPDATE Department Rights into dept_perms
        if  (isset ($_REQUEST['deptAdmin']) && isset ($_REQUEST['deptModify']))
        {
            $result_array2 = advanceCombineArrays($_REQUEST['deptAdmin'], $filedata->ADMIN_RIGHT, $_REQUEST['deptModify'], $filedata->WRITE_RIGHT);
        }
Fb::log($result_array2, '$results_array2 @ admin: ');
        if (isset ($_REQUEST['deptModify']))
        {
            $result_array2 = advanceCombineArrays($result_array2, 'NULL', $_REQUEST['deptModify'], $filedata->WRITE_RIGHT);
        }
        
Fb::log($result_array2, '$results_array2 @ modify: ');        
        
        if (isset ($_REQUEST['deptRead']))
        {
            $result_array2 = advanceCombineArrays($result_array2, 'NULL', $_REQUEST['deptRead'], $filedata->READ_RIGHT);
        }
        
Fb::log($result_array2, '$results_array2 @ read: ');
        
        if (isset ($_REQUEST['deptView']))
        {
            $result_array2 = advanceCombineArrays($result_array2, 'NULL', $_REQUEST['deptView'], $filedata->VIEW_RIGHT);
        }

Fb::log($result_array2, '$results_array2 @ view: ');

        if (isset ($_REQUEST['deptForbidden']))
        {
            $result_array2 = advanceCombineArrays($result_array2, 'NULL', $_REQUEST['deptForbidden'], $filedata->FORBIDDEN_RIGHT);
        }
        
Fb::log($result_array2, '$results_array2 @ forbidden: ');

	// clean out old permissions
	$query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_perms WHERE fid = '$fileId'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	
Fb::log($result_array2, '$results_array2');

        // INSERT dept permissions
        for($i = 0; $i<sizeof($result_array2); $i++)
        {
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, dept_id, rights) VALUES ('{$fileId}','{$result_array2[$i][0]}','{$result_array2[$i][1]}')";
Fb::log($query, 'deptPerms SQL');            
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" .mysql_error());
        }
        
//	$query = "SELECT name, id FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
//	$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//	while( list($dept_name, $id) = mysql_fetch_row($result) )
//	{
//		$string=addslashes(space_to_underscore($dept_name));
//		$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}dept_perms SET rights ='{$_REQUEST[$string]}' where fid=".$filedata->getId()." and {$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id =$id";   
//                mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//	}
//	
	// clean up
	$message = urlencode('Document successfully updated');

        // Call the plugin API
        callPluginMethod('onAfterEditFile',$fileId);
exit;        
        header('Location: details.php?id=' . $fileId . '&last_message=' . $message);
}
draw_footer();
