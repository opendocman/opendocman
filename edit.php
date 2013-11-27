<?php

/*
  edit.php - edit file properties
  Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
  Copyright (C) 2008-2013 Stephen Lawrence Jr.

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

if (!isset($_SESSION['uid']))
{
  redirect_visitor();
}

include('udf_functions.php');
require_once("AccessLog_class.php");
require_once("User_Perms_class.php");

$user_perms_obj = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    header('Location:error.php?ec=2');
    exit;
}

if (strchr($_REQUEST['id'], '_')) {
    header('Location:error.php?ec=20');
}

$filedata = new FileData($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);

if ($filedata->isArchived()) {
    header('Location:error.php?ec=21');
}

// form not yet submitted, display initial form
if (!isset($_REQUEST['submit'])) {
    draw_header(msg('area_update_file'), $last_message);
    checkUserPermission($_REQUEST['id'], $filedata->ADMIN_RIGHT, $filedata);

    $current_user_dept = $user_perms_obj->user_obj->getDeptId();

    $data_id = $_REQUEST['id'];
    // includes
    $query = "SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=$_SESSION[uid]";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    if (mysql_num_rows($result) != 1) {
        header('Location:error.php?ec=14');
        exit; //non-unique error
    }

    $filedata = new FileData($data_id, $GLOBALS['connection'], DB_NAME);

    // error check
    if (!$filedata->exists()) {
        header('Location:error.php?ec=2');
        exit;
    } else {
        $category = $filedata->getCategory();
        $realname = $filedata->getName();
        $description = $filedata->getDescription();
        $comment = $filedata->getComment();
        $owner_id = $filedata->getOwner();
        $department = $filedata->getDepartment();

        //CHM
        $query = "SELECT table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE field_type = '4'";
        $result = mysql_query($query) or die("Error in query163: $query. " . mysql_error());
        $num_rows = mysql_num_rows($result);
        
        $t_name = array();
        $i = 0;
        while ($data = mysql_fetch_array($result)) {
            $explode_v = explode('_', $data['table_name']);
            $t_name = $explode_v[2];
            $i++;
        }

        // For the User dropdown
        $avail_users = $user_perms_obj->user_obj->getAllUsers();
        
        // We need to set a form value for the current department so that
        // it can be pre-selected on the form
        $avail_departments = Department::getAllDepartments();


        $avail_categories = Category::getAllCategories();

        $cats_array = array();
        foreach ($avail_categories as $avail_category) {
            array_push($cats_array, $avail_category);
        }


        //////Populate department perm list/////////////////
        $dept_perms_array = array();
        foreach ($avail_departments as $dept) {            
            $avail_dept_perms['name'] = $dept['name'];
            $avail_dept_perms['id'] = $dept['id'];
            $avail_dept_perms['rights'] = $filedata->getDeptRights($dept['id']);
            array_push($dept_perms_array, $avail_dept_perms);
        }
        
        //////Populate users perm list/////////////////
        $user_perms_array = array();
        foreach ($avail_users as $user) {                 
            $avail_user_perms['fid'] = $data_id;
            $avail_user_perms['first_name'] = $user['first_name'];
            $avail_user_perms['last_name'] = $user['last_name'];
            $avail_user_perms['id'] = $user['id'];
            $avail_user_perms['rights'] = $user_perms_obj->getPermissionForUser($user['id'], $data_id);          
            array_push($user_perms_array, $avail_user_perms);
        }
    
        // Call Plugin API
        callPluginMethod('onBeforeEditFile', $data_id);

        $GLOBALS['smarty']->assign('file_id', $filedata->getId());
        $GLOBALS['smarty']->assign('realname', $filedata->name);
        $GLOBALS['smarty']->assign('allDepartments', $avail_departments);
        $GLOBALS['smarty']->assign('current_user_dept', $current_user_dept);
        $GLOBALS['smarty']->assign('t_name', $t_name);
        $GLOBALS['smarty']->assign('is_admin', $user_perms_obj->user_obj->isAdmin());
        $GLOBALS['smarty']->assign('avail_users', $user_perms_array);
        $GLOBALS['smarty']->assign('avail_depts', $dept_perms_array);
        $GLOBALS['smarty']->assign('cats_array', $cats_array);
        $GLOBALS['smarty']->assign('user_id', $_SESSION['uid']);
        $GLOBALS['smarty']->assign('pre_selected_owner', $owner_id);
        $GLOBALS['smarty']->assign('pre_selected_category', $category);
        $GLOBALS['smarty']->assign('pre_selected_department', $department);
        $GLOBALS['smarty']->assign('description', $description);
        $GLOBALS['smarty']->assign('comment', $comment);
        $GLOBALS['smarty']->assign('db_prefix', $GLOBALS['CONFIG']['db_prefix']);
       
        display_smarty_template('edit.tpl');
        udf_edit_file_form();
        display_smarty_template('_edit_footer.tpl');
    }//end else
} else { 
    // form submitted, process data
    $fileId = $_REQUEST['id'];
    $filedata = new FileData($fileId, $GLOBALS['connection'], DB_NAME);

    // Call the plugin API
    callPluginMethod('onBeforeEditFileSaved');

    $filedata->setId($fileId);
    $perms_error = false;
    // check submitted data
    // at least one user must have "view" and "modify" rights
    foreach( $_REQUEST['user_permission'] as $permission ) {
    
        if ($permission > 2) {
            $perms_error = true;
        }
    }
     
    if(!$perms_error) {
        header("Location:error.php?ec=12");
        exit;
    }

    // Check to make sure the file is available
    $status = $filedata->getStatus($fileId);
    if ($status != 0) {
        header('Location:error.php?ec=2');
        exit;
    }

    // update category
    $filedata->setCategory(mysql_real_escape_string($_REQUEST['category']));
    $filedata->setDescription(mysql_real_escape_string($_REQUEST['description']));
    $filedata->setComment(mysql_real_escape_string($_REQUEST['comment']));
    if (isset($_REQUEST['file_owner'])) {
        $filedata->setOwner(mysql_real_escape_string($_REQUEST['file_owner']));
    }
    if (isset($_REQUEST['file_department'])) {
        $filedata->setDepartment(mysql_real_escape_string($_REQUEST['file_department']));
    }

    // Update the file with the new values
    $filedata->updateData();

    udf_edit_file_update();

    // clean out old permissions
    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = '$fileId'";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    $result_array = array(); // init;
    
    foreach($_REQUEST['user_permission'] as $user_id=>$permission) {
       
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) VALUES($fileId, $user_id, $permission)";
        //echo $query."<br>";
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" . mysql_error());
    }

    //UPDATE Department Rights into dept_perms
    foreach ($_POST['department_permission'] as $dept_id => $dept_perm) {
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}dept_perms SET rights = $dept_perm where fid=" . $filedata->getId() . " and {$GLOBALS['CONFIG']['db_prefix']}dept_perms.dept_id = $dept_id";
        mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    }

    $message = urlencode('Document successfully updated');

    AccessLog::addLogEntry($fileId, 'M');

    // Call the plugin API
    callPluginMethod('onAfterEditFile', $fileId);

    header('Location: details.php?id=' . $fileId . '&last_message=' . $message);
}
draw_footer();
