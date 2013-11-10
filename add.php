<?php
/*
add.php - adds files to the repository
Copyright (C) 2002-2013 Stephen Lawrence Jr.

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


							ADD.PHP DOCUMENTATION
This page will allow user to set rights to every department.  It uses javascript to handle client-side data-storing and data-swapping.  Each time the data is stored, it is stored onto an array of objects of class Deparments.  It is also stored onto hidden form field in the page for php to access since php and javascript do not communicate (server-side and client-side share different environment).
As the user choose a deparment from the drop box named dept_drop_box, loadData(_selectedIndex) function is invoked.
After the data is loaded for the chosen deparment, if the user changes the right setting (right radio button e.g. "view", "read")
setData(selected_rb_name) is invoked.  This function will set the data in the appropriate deparment[] and it will set the hidden field as wel.  The connection between hidden field and department[] is the hidden field's name and the deparment[].getName().  The department names in the array is populated with the correct department names from the database.  This will lead to problems.  There will be deparment names of more than one word eg. "Information Systems".  The hidden field's accessible name cannot be more than one word.  PHP cannot access multiple word variables.  Therefore, javascript spTo_(string) (space to underscore) will go through and subtitude all the spaces with the underscore character. */

session_start();

include('odm-load.php');

if (!isset($_SESSION['uid']))
{
    redirect_visitor();
}

include('udf_functions.php');
require_once("AccessLog_class.php");
require_once("File_class.php");
require_once('Reviewer_class.php');
require_once('Email_class.php');

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

if(!isset($_POST['submit'])) 
{
    $llast_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message']:'');
    draw_header(msg('area_add_new_file'), $llast_message);
    $current_user_dept = $user_obj->getDeptId();

    $index = 0;

    //CHM - Pull in the sub-select values
    $query = "SELECT table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE field_type = '4'";
    $result = mysql_query($query) or die ("Error in query163: $query. " . mysql_error());
    $num_rows = mysql_num_rows($result);
    
    $i=0;
    
    $t_name = array();
    // Set the values for the hidden sub-select fields
    while ($data = mysql_fetch_array($result)) {
        $explode_v = explode('_', $data['table_name']);
        $t_name[] = $explode_v[2];
        $i++;
    }

    // We need to set a form value for the current user so that
    // they can be pre-selected on the form
    
    $avail_users = $user_obj->getAllUsers();

    $users_array = array();
    foreach($avail_users as $avail_user) {
        if ($avail_user['id'] == $_SESSION['uid']) {
            $avail_user['selected'] = 'selected';
        } else {
            $avail_user['selected'] = '';
        }
        
        array_push($users_array, $avail_user);   
    }
        
    // We need to set a form value for the current department so that
    // it can be pre-selected on the form
    $avail_departments = Department::getAllDepartments();
    
    $depts_array = array();
    foreach($avail_departments as $avail_department) {
        if ($avail_department['id'] == $current_user_dept) {
            $avail_department['selected'] = 'selected';
        } else {
            $avail_department['selected'] = '';
        }
        array_push($depts_array, $avail_department);
    }

    $avail_categories = Category::getAllCategories();
    
    $cats_array = array();
    foreach($avail_categories as $avail_category) {
        array_push($cats_array, $avail_category);
    }
    
    //////Populate department perm list/////////////////
    $dept_perms_array = array();
    foreach($depts_array as $dept) {
        $avail_dept_perms['name'] = $dept['name'];
        $avail_dept_perms['id'] = $dept['id'];
        array_push($dept_perms_array, $avail_dept_perms);
    }
  
    $allDepartments = Department::getAllDepartments();
    $GLOBALS['smarty']->assign('allDepartments', $allDepartments);
    $GLOBALS['smarty']->assign('current_user_dept', $current_user_dept);
    $GLOBALS['smarty']->assign('t_name', $t_name);
    $GLOBALS['smarty']->assign('is_admin', $user_obj->isAdmin());
    $GLOBALS['smarty']->assign('avail_users', $users_array);
    $GLOBALS['smarty']->assign('avail_depts', $depts_array);
    $GLOBALS['smarty']->assign('cats_array', $cats_array);
    $GLOBALS['smarty']->assign('dept_perms_array', $dept_perms_array);
    $GLOBALS['smarty']->assign('user_id', $_SESSION['uid']);
    $GLOBALS['smarty']->assign('db_prefix', $GLOBALS['CONFIG']['db_prefix']);
    
    display_smarty_template('add.tpl');

    // Call the plugin API
    callPluginMethod('onBeforeAdd');
    
    udf_add_file_form();
    
    display_smarty_template('_add_footer.tpl');

}
else 
{      
    //invalid file
    if (empty($_FILES))
    {
        header('Location:error.php?ec=11');
        exit;
    }

    $numberOfFiles = count($_FILES['file']['name']);
    
    // First we need to make sure all files are allowed types
    for ($count = 0; $count < $numberOfFiles; $count++) {
     
        if(empty($_FILES['file']['name'][$count])) {
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

        // Lets lookup the try mime type
        $file_mime = File::mime($_FILES['file']['tmp_name'][$count], $_FILES['file']['name'][$count]);

        $allowedFile = 0;
        
        // check file type
        foreach ($GLOBALS['CONFIG']['allowedFileTypes'] as $thistype) {
          
            if ($file_mime == $thistype) {
                $allowedFile = 1;
                break;
            }
        }           

        // illegal file type!
        if (!isset($allowedFile) || $allowedFile != 1)
        {
            $last_message = 'MIMETYPE: ' . $file_mime . ' Failed';
            header('Location:error.php?ec=13&last_message=' . urlencode($last_message));
            exit;
        }
    }
    
    //submited form
    for ($count = 0; $count<$numberOfFiles; $count++)
    {
        
        if ($GLOBALS['CONFIG']['authorization'] == 'True')
        {
            $lpublishable = '0';
        }
        else
        {
            $lpublishable= '1';
        }
        $result_array = array();
        
        // If the admin has chosen to assign the department
        // Set it here. Otherwise just use the session UID's department
        if($user_obj->isAdmin() && isset($_REQUEST['file_department']))
        {
            $current_user_dept = $_REQUEST['file_department'];
        }
        else
        {
            $current_user_dept = $user_obj->getDeptId();
        }
        
        // File is bigger than what php.ini post/upload/memory limits allow.
        if($_FILES['file']['error'][$count] == '1')
        {
           header('Location:error.php?ec=26');
            exit;
        }

        // File too big?
        if($_FILES['file']['size'][$count] >  $GLOBALS['CONFIG']['max_filesize'] )
        {
            header('Location:error.php?ec=25');
            exit;
        }

        // Check to make sure the dir is available and writeable
        if (!is_dir($GLOBALS['CONFIG']['dataDir']))
        {
            $last_message=$GLOBALS['CONFIG']['dataDir'] . ' missing!';
            header('Location:error.php?ec=23&last_message=' .$last_message);
            exit;
        }
        else
        {
            if (!is_writeable($GLOBALS['CONFIG']['dataDir']))
            {
                $last_message=msg('message_folder_perms_error'). ': ' . $GLOBALS['CONFIG']['dataDir'] . ' ' . msg('message_not_writeable');
                header('Location:error.php?ec=23&last_message=' .$last_message);
                exit;
            }
        }
        // all checks completed, proceed!

        // Run the onDuringAdd() plugin function
        callPluginMethod('onDuringAdd');

        // If the admin has chosen to assign the owner
        // Set it here. Otherwise just use the session UID
        if($user_obj->isAdmin() && isset($_REQUEST['file_owner']))
        {
            $owner_id = $_REQUEST['file_owner'];
        }
        else
        {
            $owner_id = $_SESSION['uid'];
        }
        
        // INSERT file info into data table
        $query = "INSERT INTO 
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
            '" . addslashes($_REQUEST['category']) . "',
            '" . addslashes($owner_id) . "',
            '" . addslashes($_FILES['file']['name'][$count]) . "',
            NOW(),
            '" . addslashes($_REQUEST['description']) . "',
            '" . addslashes($current_user_dept) . "',
            '" . addslashes($_REQUEST['comment']) . "',
            0,
            $lpublishable
        )";

        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // get id from INSERT operation
        $fileId = mysql_insert_id($GLOBALS['connection']);

        udf_add_file_insert($fileId);

        $username = $user_obj->getUserName();
        
        // Add a file history entry
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id,modified_on, modified_by, note, revision) VALUES ( '$fileId', NOW(), '" . addslashes($username) . "', 'Initial import', 'current')";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        //Insert Department Rights into dept_perms
        foreach ($_POST['department_permission'] as $dept_id=>$dept_perm) {
            $query = "
                INSERT INTO 
                    {$GLOBALS['CONFIG']['db_prefix']}dept_perms (
                        fid, 
                        rights, 
                        dept_id
                        ) 
                VALUES(
                        $fileId, 
                        $dept_perm, 
                        $dept_id)";
                
            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
        }
        // Search for simular names in the two array (merge the array.  repetitions are deleted)
        // In case of repetitions, higher priority ones stay.
        // Priority is in this order (admin, modify, read, view)
       
        foreach ($_REQUEST['user_permission'] as $user_id => $permission) {

            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid, uid, rights) VALUES($fileId, $user_id, $permission)";           
            //echo $query."<br>";
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" . mysql_error());
        }

        // use id to generate a file name
        // save uploaded file with new name
        $newFileName = $fileId . '.dat';

        if (!is_uploaded_file($_FILES['file']['tmp_name'][$count]))
        {
            header('Location: error.php?ec=18');
            exit;
        }
        move_uploaded_file($_FILES['file']['tmp_name'][$count], $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);

        //copy($GLOBALS['CONFIG']['dataDir'] . '/' . ($fileId-1) . '.dat', $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);
        
        AccessLog::addLogEntry($fileId, 'A');
        
        // back to main page
        $message = urlencode(msg('message_document_added'));
        
        /**
         * Send out email notifications to reviewers
         */
        $file_obj = new FileData($fileId, $GLOBALS['connection'], DB_NAME);
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
        $from = $user_obj->getEmailAddress();
     
        $department = $file_obj->getDepartment();
        
        $reviewer_obj = new Reviewer($fileId, $GLOBALS['connection'], DB_NAME);
        $reviewer_list = $reviewer_obj->getReviewersForDepartment($department);

        $date = date('Y-m-d H:i:s T');
        
        // Build email for general notices
        $mail_subject = msg('addpage_new_file_added');
        $mail_body2 = msg('email_a_new_file_has_been_added') . "\n\n";
        $mail_body2.=msg('label_filename') . ':  ' . $file_obj->getName() . "\n\n";
        $mail_body2.=msg('label_status') . ': ' . msg('addpage_new') . "\n\n";
        $mail_body2.=msg('date') . ': ' . $date . "\n\n";
        $mail_body2.=msg('addpage_uploader') . ': ' . $full_name . "\n\n";
        $mail_body2.=msg('email_thank_you') . ',' . "\n\n";
        $mail_body2.=msg('email_automated_document_messenger') . "\n\n";
        $mail_body2.=$GLOBALS['CONFIG']['base_url'] . "\n\n";
        
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
