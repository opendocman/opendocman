<?php
/*
add.php - adds files to the repository
Copyright (C) 2002-2015 Stephen Lawrence Jr.

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

/*
                            ADD.PHP DOCUMENTATION
This page will allow user to set rights to every department. It uses javascript
to handle client-side data-storing and data-swapping. Each time the data is stored,
it is stored onto an array of objects of class Departments. It is also stored onto
hidden form field in the page for php to access since php and javascript do not
communicate (server-side and client-side share different environment).
As the user choose a department from the drop box named dept_drop_box, loadData(_selectedIndex)
function is invoked. After the data is loaded for the chosen department, if the user
changes the right setting (right radio button e.g. "view", "read") setData(selected_rb_name)
is invoked.  This function will set the data in the appropriate department[] and it will
set the hidden field as well. The connection between hidden field and department[] is
the hidden field's name and the department[].getName(). The department names in the
array is populated with the correct department names from the database. This will lead to
problems. There will be department names of more than one word eg. "Information Systems".
The hidden field's accessible name cannot be more than one word. PHP cannot access multiple word variables.
Therefore, javascript spTo_(string) (space to underscore) will go through and substitute
all the spaces with the underscore character.
*/

session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

include('udf_functions.php');
require_once("AccessLog_class.php");
require_once("File_class.php");
require_once('Reviewer_class.php');
require_once('Email_class.php');

$user_obj = new User($_SESSION['uid'], $pdo);

if (!$user_obj->canAdd()) {
    redirect_visitor('out.php');
}

if (!isset($_POST['submit'])) {
    $last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
    draw_header(msg('area_add_new_file'), $last_message);
    $current_user_dept = $user_obj->getDeptId();

    $index = 0;

    //CHM - Pull in the sub-select values
    $query = "SELECT table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE field_type = '4'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    $num_rows = $stmt->rowCount();
    
    $i=0;
    
    $t_name = array();
    // Set the values for the hidden sub-select fields
    foreach ($result as $data) {
        $explode_v = explode('_', $data['table_name']);
        $t_name[] = $explode_v[2];
        $i++;
    }

    // We need to set a form value for the current user so that
    // they can be pre-selected on the form

    $avail_users = $user_obj->getAllUsers($pdo);

    $users_array = array();
    foreach ($avail_users as $avail_user) {
        if ($avail_user['id'] == $_SESSION['uid']) {
            $avail_user['selected'] = 'selected';
        } else {
            $avail_user['selected'] = '';
        }
        
        array_push($users_array, $avail_user);
    }
        
    // We need to set a form value for the current department so that
    // it can be pre-selected on the form
    $avail_departments = Department::getAllDepartments($pdo);
    
    $departments_array = array();
    foreach ($avail_departments as $avail_department) {
        if ($avail_department['id'] == $current_user_dept) {
            $avail_department['selected'] = 'selected';
        } else {
            $avail_department['selected'] = '';
        }
        array_push($departments_array, $avail_department);
    }

    $avail_categories = Category::getAllCategories($pdo);
    
    $cats_array = array();
    foreach ($avail_categories as $avail_category) {
        array_push($cats_array, $avail_category);
    }
    
    //////Populate department perm list/////////////////
    $dept_perms_array = array();
    foreach ($departments_array as $dept) {
        $avail_dept_perms['name'] = $dept['name'];
        $avail_dept_perms['id'] = $dept['id'];
        array_push($dept_perms_array, $avail_dept_perms);
    }
  
    $allDepartments = Department::getAllDepartments($pdo);
    $GLOBALS['smarty']->assign('allDepartments', $allDepartments);
    $GLOBALS['smarty']->assign('current_user_dept', $current_user_dept);
    $GLOBALS['smarty']->assign('t_name', $t_name);
    $GLOBALS['smarty']->assign('is_admin', $user_obj->isAdmin());
    $GLOBALS['smarty']->assign('avail_users', $users_array);
    $GLOBALS['smarty']->assign('avail_depts', $departments_array);
    $GLOBALS['smarty']->assign('cats_array', $cats_array);
    $GLOBALS['smarty']->assign('dept_perms_array', $dept_perms_array);
    $GLOBALS['smarty']->assign('user_id', $_SESSION['uid']);
    $GLOBALS['smarty']->assign('db_prefix', $GLOBALS['CONFIG']['db_prefix']);
    
    display_smarty_template('add.tpl');

    udf_add_file_form();

    // Call the plugin API
    callPluginMethod('onBeforeAdd');

    display_smarty_template('_add_footer.tpl');
} else {
    //invalid file
    if (empty($_FILES)) {
        header('Location:error.php?ec=11');
        exit;
    }

    $numberOfFiles = count($_FILES['file']['name']);
    $tmp_name = array();
    
    // First we need to make sure all files are allowed types
    for ($count = 0; $count < $numberOfFiles; $count++) {
        if (empty($_FILES['file']['name'][$count])) {
            $last_message = $GLOBALS['lang']['addpage_file_missing'];
            header('Location: error.php?last_message=' . urlencode($last_message));
            exit;
        }
          
        // Check ini max upload size
        if ($_FILES['file']['error'][$count] == 1) {
            $last_message = 'Upload Failed - check your upload_max_filesize directive in php.ini';
            header('Location: error.php?last_message=' . urlencode($last_message));
            exit;
        }

        $tmp_name[$count] = realpath($_FILES['file']['tmp_name'][$count]);
        // Lets lookup the try mime type
        $file_mime = File::mime($tmp_name[$count], $_FILES['file']['name'][$count]);

        $allowedFile = 0;
        
        // check file type
        foreach ($GLOBALS['CONFIG']['allowedFileTypes'] as $allowed_type) {
            if ($file_mime == $allowed_type) {
                $allowedFile = 1;
                break;
            }
        }

        // illegal file type!
        if (!isset($allowedFile) || $allowedFile != 1) {
            $last_message = 'MIMETYPE: ' . $file_mime . ' Failed';
            header('Location:error.php?ec=13&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    //submited form
    for ($count = 0; $count<$numberOfFiles; $count++) {
        if ($GLOBALS['CONFIG']['authorization'] == 'True') {
            $publishable = '0';
        } else {
            $publishable= '1';
        }
        $result_array = array();
        
        // If the admin has chosen to assign the department
        // Set it here. Otherwise just use the session UID's department
        if ($user_obj->isAdmin() && isset($_REQUEST['file_department'])) {
            $current_user_dept = $_REQUEST['file_department'];
        } else {
            $current_user_dept = $user_obj->getDeptId();
        }
        
        // File is bigger than what php.ini post/upload/memory limits allow.
        if ($_FILES['file']['error'][$count] == '1') {
            header('Location:error.php?ec=26');
            exit;
        }

        // File too big?
        if ($_FILES['file']['size'][$count] >  $GLOBALS['CONFIG']['max_filesize']) {
            header('Location:error.php?ec=25');
            exit;
        }

        // Check to make sure the dir is available and writable
        if (!is_dir($GLOBALS['CONFIG']['dataDir'])) {
            $last_message=$GLOBALS['CONFIG']['dataDir'] . ' missing!';
            header('Location:error.php?ec=23&last_message=' .$last_message);
            exit;
        } else {
            if (!is_writable($GLOBALS['CONFIG']['dataDir'])) {
                $last_message=msg('message_folder_perms_error'). ': ' . $GLOBALS['CONFIG']['dataDir'] . ' ' . msg('message_not_writable');
                header('Location:error.php?ec=23&last_message=' .$last_message);
                exit;
            }
        }

        // We need to verify that the temporary upload is there before we continue
        if (!is_uploaded_file($tmp_name[$count])) {
            header('Location: error.php?ec=18');
            exit;
        }

        // all checks completed, proceed!

        // Run the onDuringAdd() plugin function
        callPluginMethod('onDuringAdd');

        // If the admin has chosen to assign the owner
        // Set it here. Otherwise just use the session UID
        if ($user_obj->isAdmin() && isset($_REQUEST['file_owner'])) {
            $owner_id = $_REQUEST['file_owner'];
        } else {
            $owner_id = $_SESSION['uid'];
        }
        
        // INSERT file info into data table
        $file_data_query = "INSERT INTO 
        {$GLOBALS['CONFIG']['db_prefix']}data (
            status,
            category,
            owner,
            realname,
            created,
            description,
            department,
            comment,
            default_rights,
            publishable
        )
            VALUES
        (
            0,
            :category,
            :owner_id,
            :realname,
            NOW(),
            :description,
            :current_user_dept,
            :comment,
            0,
            $publishable
        )";

        $file_data_stmt = $pdo->prepare($file_data_query);
        
        $file_data_stmt->bindParam(':category', $_REQUEST['category']);
        $file_data_stmt->bindParam(':owner_id', $owner_id);
        $file_data_stmt->bindParam(':realname', $_FILES['file']['name'][$count]);
        $file_data_stmt->bindParam(':description', $_REQUEST['description']);
        $file_data_stmt->bindParam(':current_user_dept', $current_user_dept);
        $file_data_stmt->bindParam(':comment', $_REQUEST['comment']);
        
        $file_data_stmt->execute();

        // get id from INSERT operation
        $fileId = $pdo->lastInsertId();

        udf_add_file_insert($fileId);

        $username = $user_obj->getUserName();
        
        // Add a file history entry
        $history_query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log 
            (
                id,
                modified_on, 
                modified_by,
                note,
                revision
            ) VALUES ( 
                '$fileId',
                NOW(),
                :username,
                'Initial import',
                'current'
            )";
        
        $history_stmt = $pdo->prepare($history_query);
        $history_stmt->bindParam(':username', $username);
        $history_stmt->execute();
        
        //Insert Department Rights into dept_perms
        foreach ($_POST['department_permission'] as $dept_id=>$dept_perm) {
            $dept_perms_query = "
                INSERT INTO 
                    {$GLOBALS['CONFIG']['db_prefix']}dept_perms 
                    (
                        fid, 
                        rights, 
                        dept_id
                    ) VALUES (
                        $fileId, 
                        :dept_perm, 
                        :dept_id
                    )";
                
            $dept_perms_stmt = $pdo->prepare($dept_perms_query);
            $dept_perms_stmt->bindParam(':dept_perm', $dept_perm);
            $dept_perms_stmt->bindParam(':dept_id', $dept_id);
            $dept_perms_stmt->execute();
        }
        // Search for similar names in the two array (merge the array.  repetitions are deleted)
        // In case of repetitions, higher priority ones stay.
        // Priority is in this order (admin, modify, read, view)

        foreach ($_REQUEST['user_permission'] as $user_id => $permission) {
            $user_perms_query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) VALUES($fileId, :user_id, :permission)";
            
            $user_perms_stmt = $pdo->prepare($user_perms_query);
            $user_perms_stmt->bindParam(':user_id', $user_id);
            $user_perms_stmt->bindParam(':permission', $permission);
            $user_perms_stmt->execute();
        }

        // use id to generate a file name
        // save uploaded file with new name
        $newFileName = $fileId . '.dat';

        move_uploaded_file($tmp_name[$count], $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);
        //copy($GLOBALS['CONFIG']['dataDir'] . '/' . ($fileId-1) . '.dat', $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);

        AccessLog::addLogEntry($fileId, 'A', $pdo);
        
        // back to main page
        $message = urlencode(msg('message_document_added'));
        
        /**
         * Send out email notifications to reviewers
         */
        $file_obj = new FileData($fileId, $pdo);
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
        $from = $user_obj->getEmailAddress();
     
        $department = $file_obj->getDepartment();
        
        $reviewer_obj = new Reviewer($fileId, $pdo);
        $reviewer_list = $reviewer_obj->getReviewersForDepartment($department);

        $date = date('Y-m-d H:i:s T');
        
        // Build email for general notices
        $mail_subject = msg('addpage_new_file_added');
        $mail_body2 = msg('email_a_new_file_has_been_added') . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('label_status') . ': ' . msg('addpage_new') . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('addpage_uploader') . ': ' . $full_name . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
        $mail_body2.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
        
        $email_obj = new Email();
        $email_obj->setFullName($full_name);
        $email_obj->setSubject($mail_subject);
        $email_obj->setFrom($from);
        $email_obj->setRecipients($reviewer_list);
        $email_obj->setBody($mail_body2);
        $email_obj->sendEmail();
    
        //email_users_id($mail_from, $reviewer_list, $mail_subject, $mail_body2, $mail_headers);
        // Call the plugin API
        callPluginMethod('onAfterAdd', $fileId);
    }
        
    header('Location: details.php?id=' . $fileId . '&last_message=' . $message);
    exit;
}
draw_footer();
