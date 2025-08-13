<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/*
 * OPTIMIZED ADD.PHP
 * 
 * This is an optimized version of the original add.php that addresses performance bottlenecks:
 * 1. Uses database transactions for atomic operations
 * 2. Implements batched database operations where possible
 * 3. Uses asynchronous email notifications via EmailQueue
 * 4. Optimized file processing with early validation
 * 5. Reduced redundant database queries
 * 6. Better error handling and logging
 */

session_start();

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$user_obj = new User($_SESSION['uid'], $GLOBALS['pdo']);

if (!$user_obj->canAdd()) {
    redirect_visitor('out');
}

if (!isset($_POST['submit'])) {
    // Display the upload form (same as original)
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
        // Add rights field for file permissions template
        $avail_user['rights'] = '';
        
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
        // Add rights field for file permissions template
        $avail_department['rights'] = '';
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
        $avail_dept_perms['rights'] = $dept['rights'];
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
    // OPTIMIZED FILE UPLOAD PROCESSING
    
    // Start performance timing
    $start_time = microtime(true);
    error_log("OptimizedAdd: Starting upload process");
    
    // Initialize email queue for asynchronous notifications
    $email_queue = new EmailQueue($pdo);
    
    //invalid file
    if (empty($_FILES)) {
        header('Location:error?ec=11');
        exit;
    }

    $numberOfFiles = count($_FILES['file']['name']);
    $tmp_name = array();
    
    // OPTIMIZATION: Early validation of ALL files before processing any
    error_log("OptimizedAdd: Validating {$numberOfFiles} files");
    
    for ($count = 0; $count < $numberOfFiles; $count++) {
        if (empty($_FILES['file']['name'][$count])) {
            $last_message = $GLOBALS['lang']['addpage_file_missing'];
            header('Location: error?last_message=' . urlencode($last_message));
            exit;
        }
          
        // Check ini max upload size
        if ($_FILES['file']['error'][$count] == 1) {
            $last_message = 'Upload Failed - check your upload_max_filesize directive in php.ini';
            header('Location: error?last_message=' . urlencode($last_message));
            exit;
        }

        $tmp_name[$count] = realpath($_FILES['file']['tmp_name'][$count]);
        
        // Validate upload was successful
        if (!is_uploaded_file($tmp_name[$count])) {
            header('Location: error?ec=18');
            exit;
        }
        
        // File size check (before MIME detection for performance)
        if ($_FILES['file']['size'][$count] > $GLOBALS['CONFIG']['max_filesize']) {
            header('Location:error?ec=25');
            exit;
        }
        
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
            header('Location:error?ec=13&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    // OPTIMIZATION: Check directory availability once
    if (!is_dir($GLOBALS['CONFIG']['dataDir'])) {
        $last_message=$GLOBALS['CONFIG']['dataDir'] . ' missing!';
        header('Location:error?ec=23&last_message=' . urlencode($last_message));
        exit;
    } else {
        if (!is_writable($GLOBALS['CONFIG']['dataDir'])) {
            $last_message=msg('message_folder_perms_error'). ': ' . $GLOBALS['CONFIG']['dataDir'] . ' ' . msg('message_not_writable');
            header('Location:error?ec=23&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    // OPTIMIZATION: Determine common values once
    if ($GLOBALS['CONFIG']['authorization'] == 'True') {
        $publishable = '0';
    } else {
        $publishable = '1';
    }
    
    // Determine owner and department once
    if ($user_obj->isAdmin() && isset($_REQUEST['file_owner'])) {
        $owner_id = $_REQUEST['file_owner'];
    } else {
        $owner_id = $_SESSION['uid'];
    }
    
    if ($user_obj->isAdmin() && isset($_REQUEST['file_department'])) {
        $current_user_dept = $_REQUEST['file_department'];
    } else {
        $current_user_dept = $user_obj->getDeptId();
    }
    
    // Get username once for logging
    $username = $user_obj->getUserName();
    
    // Get user info once for email notifications
    $get_full_name = $user_obj->getFullName();
    $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
    $from_email = $user_obj->getEmailAddress();
    
    // OPTIMIZATION: Start database transaction for atomic operations
    try {
        $pdo->beginTransaction();
        error_log("OptimizedAdd: Started database transaction");
        
        $processed_files = array();
        
        // Process each file within the transaction
        for ($count = 0; $count < $numberOfFiles; $count++) {
            error_log("OptimizedAdd: Processing file " . ($count + 1) . " of {$numberOfFiles}");
            
            // Run the onDuringAdd() plugin function
            callPluginMethod('onDuringAdd');
            
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
                :publishable
            )";

            $file_data_stmt = $pdo->prepare($file_data_query);
            
            $file_data_stmt->bindParam(':category', $_REQUEST['category']);
            $file_data_stmt->bindParam(':owner_id', $owner_id);
            $file_data_stmt->bindParam(':realname', $_FILES['file']['name'][$count]);
            $file_data_stmt->bindParam(':description', $_REQUEST['description']);
            $file_data_stmt->bindParam(':current_user_dept', $current_user_dept);
            $file_data_stmt->bindParam(':comment', $_REQUEST['comment']);
            $file_data_stmt->bindParam(':publishable', $publishable);
            
            $file_data_stmt->execute();

            // get id from INSERT operation
            $fileId = $pdo->lastInsertId();
            
            // Store file info for batch operations
            $processed_files[] = array(
                'id' => $fileId,
                'tmp_name' => $tmp_name[$count],
                'filename' => $_FILES['file']['name'][$count],
                'count' => $count
            );

            // Process UDF fields
            udf_add_file_insert($fileId);
            
            // Add a file history entry
            $history_query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log 
                (
                    id,
                    modified_on, 
                    modified_by,
                    note,
                    revision
                ) VALUES ( 
                    :file_id,
                    NOW(),
                    :username,
                    'Initial import',
                    'current'
                )";
            
            $history_stmt = $pdo->prepare($history_query);
            $history_stmt->bindParam(':file_id', $fileId);
            $history_stmt->bindParam(':username', $username);
            $history_stmt->execute();
        }
        
        // OPTIMIZATION: Batch insert department permissions
        if (!empty($_POST['department_permission'])) {
            error_log("OptimizedAdd: Processing department permissions");
            
            $dept_perms_values = array();
            $dept_perms_params = array();
            $param_index = 0;
            
            foreach ($processed_files as $file_info) {
                foreach ($_POST['department_permission'] as $dept_id => $dept_perm) {
                    $dept_perms_values[] = "(:fid_{$param_index}, :dept_perm_{$param_index}, :dept_id_{$param_index})";
                    $dept_perms_params[":fid_{$param_index}"] = $file_info['id'];
                    $dept_perms_params[":dept_perm_{$param_index}"] = $dept_perm;
                    $dept_perms_params[":dept_id_{$param_index}"] = $dept_id;
                    $param_index++;
                }
            }
            
            if (!empty($dept_perms_values)) {
                $dept_perms_query = "
                    INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) 
                    VALUES " . implode(', ', $dept_perms_values);
                
                $dept_perms_stmt = $pdo->prepare($dept_perms_query);
                $dept_perms_stmt->execute($dept_perms_params);
            }
        }
        
        // OPTIMIZATION: Batch insert user permissions
        if (!empty($_REQUEST['user_permission'])) {
            error_log("OptimizedAdd: Processing user permissions");
            
            $user_perms_values = array();
            $user_perms_params = array();
            $param_index = 0;
            
            foreach ($processed_files as $file_info) {
                foreach ($_REQUEST['user_permission'] as $user_id => $permission) {
                    $user_perms_values[] = "(:fid_{$param_index}, :uid_{$param_index}, :rights_{$param_index})";
                    $user_perms_params[":fid_{$param_index}"] = $file_info['id'];
                    $user_perms_params[":uid_{$param_index}"] = $user_id;
                    $user_perms_params[":rights_{$param_index}"] = $permission;
                    $param_index++;
                }
            }
            
            if (!empty($user_perms_values)) {
                $user_perms_query = "
                    INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) 
                    VALUES " . implode(', ', $user_perms_values);
                
                $user_perms_stmt = $pdo->prepare($user_perms_query);
                $user_perms_stmt->execute($user_perms_params);
            }
        }
        
        // Commit transaction before file operations
        $pdo->commit();
        error_log("OptimizedAdd: Database transaction committed successfully");
        
        // OPTIMIZATION: Move files after successful database operations
        $successful_files = array();
        foreach ($processed_files as $file_info) {
            $newFileName = $file_info['id'] . '.dat';
            $destination = $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName;
            
            if (move_uploaded_file($file_info['tmp_name'], $destination)) {
                $successful_files[] = $file_info;
                
                // Add access log entry
                AccessLog::addLogEntry($file_info['id'], 'A', $pdo);
                
                error_log("OptimizedAdd: File moved successfully - ID: {$file_info['id']}, Name: {$file_info['filename']}");
            } else {
                error_log("OptimizedAdd: Failed to move file - ID: {$file_info['id']}, Name: {$file_info['filename']}");
                // Note: Database entries remain, file can be re-uploaded later
            }
        }
        
        // OPTIMIZATION: Queue email notifications asynchronously
        if (!empty($successful_files)) {
            error_log("OptimizedAdd: Queuing email notifications for " . count($successful_files) . " files");
            
            foreach ($successful_files as $file_info) {
                // Get department for this file
                $file_obj = new FileData($file_info['id'], $pdo);
                $department = $file_obj->getDepartment();
                
                // Get reviewers for this department
                $reviewer_obj = new Reviewer($file_info['id'], $pdo);
                $reviewer_list = $reviewer_obj->getReviewersForDepartment($department);
                
                if (!empty($reviewer_list)) {
                    $date = date('Y-m-d H:i:s T');
                    
                    // Build email content
                    $mail_subject = msg('addpage_new_file_added');
                    $mail_body = msg('email_a_new_file_has_been_added') . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('label_status') . ': ' . msg('addpage_new') . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('addpage_uploader') . ': ' . $full_name . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
                    $mail_body .= msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
                    $mail_body .= $GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
                    
                    // Queue emails for all reviewers
                    $queued_count = $email_queue->queueFileNotification(
                        $reviewer_list,
                        $mail_subject,
                        $mail_body,
                        $from_email,
                        $full_name,
                        $file_info['id'],
                        5 // Normal priority
                    );
                    
                    error_log("OptimizedAdd: Queued {$queued_count} email notifications for file ID: {$file_info['id']}");
                }
                
                // Call the plugin API
                callPluginMethod('onAfterAdd', $file_info['id']);
            }
        }
        
        // Calculate processing time
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000, 2);
        error_log("OptimizedAdd: Upload process completed in {$processing_time}ms for {$numberOfFiles} files");
        
        // Redirect to the last uploaded file or a summary page
        if (!empty($successful_files)) {
            $last_file = end($successful_files);
            $message = urlencode(msg('message_document_added') . " (Processed in {$processing_time}ms)");
            header('Location: details?id=' . $last_file['id'] . '&last_message=' . $message);
        } else {
            $message = urlencode("Upload completed but no files were successfully processed");
            header('Location: add?last_message=' . $message);
        }
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("OptimizedAdd: Error during upload process: " . $e->getMessage());
        error_log("OptimizedAdd: Stack trace: " . $e->getTraceAsString());
        
        $error_message = "Upload failed due to system error. Please try again or contact administrator.";
        header('Location: error?last_message=' . urlencode($error_message));
        exit;
    }
}

draw_footer();