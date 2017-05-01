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

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

include('udf_functions.php');
require_once("AccessLog_class.php");
require_once("User_Perms_class.php");

$user_perms_obj = new User_Perms($_SESSION['uid'], $pdo);

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    header('Location:error.php?ec=2');
    exit;
}

if (strchr($_REQUEST['id'], '_')) {
    header('Location:error.php?ec=20');
}

$filedata = new FileData($_REQUEST['id'], $pdo);

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
    $department_query = "SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=:user_id";
    $department_stmt = $pdo->prepare($department_query);
    $department_stmt->bindParam(':user_id', $_SESSION['uid']);
    $department_stmt->execute();
    $result = $department_stmt->fetchAll();

    if ($department_stmt->rowCount() != 1) {
        header('Location:error.php?ec=14');
        exit; //non-unique error
    }

    $filedata = new FileData($data_id, $pdo);

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
        $table_name_query = "SELECT table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE field_type = '4'";
        $table_name_stmt = $pdo->prepare($table_name_query);
        $table_name_stmt->execute();
        $result = $table_name_stmt->fetchAll();

        $num_rows = $table_name_stmt->rowCount();
        
        $t_name = array();
        $i = 0;
        foreach ($result as $data) {
            $explode_v = explode('_', $data['table_name']);
            $t_name = $explode_v[2];
            $i++;
        }

        // For the User dropdown
        $avail_users = $user_perms_obj->user_obj->getAllUsers($pdo);
        
        // We need to set a form value for the current department so that
        // it can be pre-selected on the form
        $avail_departments = Department::getAllDepartments($pdo);


        $avail_categories = Category::getAllCategories($pdo);

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

        // Call Plugin API
        callPluginMethod('onBeforeEditFile', $data_id);

        display_smarty_template('_edit_footer.tpl');
    }//end else
} else {
    // form submitted, process data
    $fileId = $_REQUEST['id'];
    $filedata = new FileData($fileId, $pdo);

    // Call the plugin API
    callPluginMethod('onBeforeEditFileSaved');

    $filedata->setId($fileId);
    $perms_error = false;
    // check submitted data
    // at least one user must have "view" and "modify" rights
    foreach ($_REQUEST['user_permission'] as $permission) {
        if ($permission > 2) {
            $perms_error = true;
        }
    }
     
    if (!$perms_error) {
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
    $filedata->setCategory($_REQUEST['category']);
    $filedata->setDescription($_REQUEST['description']);
    $filedata->setComment($_REQUEST['comment']);
    if (isset($_REQUEST['file_owner'])) {
        $filedata->setOwner($_REQUEST['file_owner']);
    }
    if (isset($_REQUEST['file_department'])) {
        $filedata->setDepartment($_REQUEST['file_department']);
    }

    // Update the file with the new values
    $filedata->updateData();

    udf_edit_file_update();

    // clean out old permissions
    $del_user_perms_query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = :file_id";
    $del_user_perms_stmt = $pdo->prepare($del_user_perms_query);
    $del_user_perms_stmt->bindParam(':file_id', $fileId);
    $del_user_perms_stmt->execute();

    // clean out old permissions
    $del_dept_perms_query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_perms WHERE fid = :file_id";
    $del_dept_perms_stmt = $pdo->prepare($del_dept_perms_query);
    $del_dept_perms_stmt->bindParam(':file_id', $fileId);
    $del_dept_perms_stmt->execute();
    
    $result_array = array(); // init;

    foreach ($_REQUEST['user_permission'] as $user_id=>$permission) {
        $insert_user_perms_query = "
            INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms 
            (
                fid, 
                uid, 
                rights
            ) VALUES(
                :file_id, 
                :user_id, 
                :permission
            )";
        //echo $query."<br>";
        $insert_user_perms_stmt = $pdo->prepare($insert_user_perms_query);
        $insert_user_perms_stmt->bindParam(':file_id', $fileId);
        $insert_user_perms_stmt->bindParam(':user_id', $user_id);
        $insert_user_perms_stmt->bindParam(':permission', $permission);
        $insert_user_perms_stmt->execute();
    }

    //UPDATE Department Rights into dept_perms
    foreach ($_POST['department_permission'] as $dept_id => $dept_perm) {
        $update_dept_perms_query = "
            INSERT INTO
                {$GLOBALS['CONFIG']['db_prefix']}dept_perms
            (
                fid,
                dept_id,
                rights
            )
            VALUES
             (
                :file_id,
                :dept_id,
                :dept_perm
             )
             ";
        $update_dept_perms_stmt = $pdo->prepare($update_dept_perms_query);
        $update_dept_perms_stmt->bindParam(':dept_perm', $dept_perm);
        $update_dept_perms_stmt->bindParam(':dept_id', $dept_id);
        $update_dept_perms_stmt->bindParam(':file_id', $filedata->getId());
        $update_dept_perms_stmt->execute();
    }

    $message = 'Document successfully updated';

    AccessLog::addLogEntry($fileId, 'M', $pdo);

    // Call the plugin API
    callPluginMethod('onAfterEditFile', $fileId);

    header('Location: details.php?id=' . $fileId . '&last_message=' . urlencode($message));
}
draw_footer();
