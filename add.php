<?php
/*
add.php - adds files to the repository
Copyright (C) 2007 Stephen Lawrence Jr.
Copyright (C) 2002-2011 Stephen Lawrence Jr.

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

if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));
    exit;
}
include('odm-load.php');
include('udf_functions.php');

$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);

//un_submitted form
if(!isset($_POST['submit'])) 
{
    $llast_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message']:'');
    draw_header(msg('area_add_new_file'), $llast_message);

    //////////////////////////Get Current User's department id///////////////////
    $query ="SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user where id='$_SESSION[uid]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 1) /////////////If somehow this user belongs to many departments, then error out.

    {
        header('Location:error.php?ec=14');
        exit; //non-unique error
    }
    list($current_user_dept) = mysql_fetch_row($result);    
    $index = 0;
    ///////Define a class that hold Department information (id, name, and rights)/////////
    //this class will be used to temporarily hold department information client-side wise//
        
    $userListArray = User::getAllUsers();

    $departmentListArray = Department::getAllDepartments();

    $categoryListArray = Category::getAllCategories();
    
    $rightsListArray = User_Perms::getAllRights();
    
    $allDepartments = Department::getAllDepartments();
    $userDeptartmentId = $user_obj->getDeptId();

    // Store the output of any UDF form fields
    $udfForm = udf_add_file_form();
    
    // Assign all of the needed smarty vars
    $GLOBALS['smarty']->assign('allDepartments', $allDepartments);
    $GLOBALS['smarty']->assign('userDepartmentId', $userDeptartmentId);
    $GLOBALS['smarty']->assign('isAdmin', $user_obj->isAdmin());
    $GLOBALS['smarty']->assign('userListArray', $userListArray);
    $GLOBALS['smarty']->assign('departmentListArray', $departmentListArray);
    $GLOBALS['smarty']->assign('categoryListArray', $categoryListArray);
    $GLOBALS['smarty']->assign('udfForm', $udfForm);
    $GLOBALS['smarty']->assign('rightsListArray', $rightsListArray);
    
    // Drop the beginning of the form
    display_smarty_template('add_file.tpl');
    
    // Call the plugin API
    callPluginMethod('onBeforeAdd');
    
    //Now close the form up
    display_smarty_template('add_file_footer.tpl');
//    display_smarty_template('add_file_js.tpl');
}
else 
{

Fb::log($_POST);
    
    //submited form
    // change this to 100 if you want to add 100 of the same files automatically.  For debuging purpose only
    for($khoa = 0; $khoa<1; $khoa++)
    {
        //invalid file
        if(empty($_FILES))
        {
            header('Location:error.php?ec=11');
            exit;
        }
        
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
            //get current user's department
            $query ="SELECT department FROM {$GLOBALS['CONFIG']['db_prefix']}user where id=$_SESSION[uid]";
            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
            if(mysql_num_rows($result) != 1)
            {
                header('Location:error.php?ec=14');
                exit; //non-unique error
            }
            list($current_user_dept) = mysql_fetch_row($result);
        }
        // File is bigger than what php.ini post/upload/memory limits allow.
        if($_FILES['file']['error'] == '1')
        {
           header('Location:error.php?ec=26');
            exit;
        }

        // File too big?
        if($_FILES['file']['size'] >  $GLOBALS['CONFIG']['max_filesize'] )
        {
            header('Location:error.php?ec=25');
            exit;
        }

    // check file type
    foreach($GLOBALS['CONFIG']['allowedFileTypes'] as $thistype)
    {
        if ($_FILES['file']['type'] == $thistype)
        {
            $allowedFile = 1;
            break;
        }
        else
        {
            $allowedFile = 0;
        }
    }
    // illegal file type!
    if ($allowedFile != 1)
    {
        $last_message='MIMETYPE: ' . $_FILES['file']['type'] . ' Failed';
        header('Location:error.php?ec=13&last_message=' . urlencode($last_message));
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
            '" . addslashes($_FILES['file']['name']) . "',
            NOW(),
            '" . addslashes($_REQUEST['description']) . "',
            '" . addslashes($current_user_dept) . "',
            '" . addslashes($_REQUEST['comment']) . "',
            '" . addslashes($_REQUEST['defaultDepartmentPerms']) . "',
            $lpublishable
        )";
        
Fb::log($query);

        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // get id from INSERT operation
        $fileId = mysql_insert_id($GLOBALS['connection']);

        udf_add_file_insert($fileId);

        //Find out the owners' username to add to log
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user where id='$_SESSION[uid]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        list($username) = mysql_fetch_row($result);

        // Add a log entry
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id,modified_on, modified_by, note, revision) VALUES ( '$fileId', NOW(), '" . addslashes($username) . "', 'Initial import', 'current')";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());


        //Insert Department Rights into dept_perms

                // Search for simular names in the two array (merge the array.  repetitions are deleted)
        // In case of repetitions, higher priority ones stay.
        // Priority is in this order (admin, modify, read, view)
        $filedata = new FileData($fileId, $GLOBALS['connection'], DB_NAME);
        $result_array = array();
        
        if  (isset ($_REQUEST['deptAdmin']))
        {
            $result_array = advanceCombineArrays($_REQUEST['deptAdmin'], $filedata->ADMIN_RIGHT, $_REQUEST['deptModify'], $filedata->WRITE_RIGHT);
        }

        if (isset ($_REQUEST['deptModify']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['deptModify'], $filedata->WRITE_RIGHT);
        }
        
        if (isset ($_REQUEST['deptRead']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['deptRead'], $filedata->READ_RIGHT);
        }

        if (isset ($_REQUEST['deptView']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['deptView'], $filedata->VIEW_RIGHT);
        }

        if (isset ($_REQUEST['deptForbidden']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['deptForbidden'], $filedata->FORBIDDEN_RIGHT);
        }
        // INSERT user permissions - view
        for($i = 0; $i<sizeof($result_array); $i++)
        {
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, dept_id, rights) VALUES('$fileId', '".$result_array[$i][0]."','". $result_array[$i][1]."')";
Fb::log($query, 'deptPerms SQL');            
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" .mysql_error());
        }
        
        
        
        
        
        
//        
//        
//        
//        // Forbidden
//        foreach($_POST['deptForbidden'] as $deptForbiddenItem)
//        {
//            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) VALUES('$fileId', '-1', '$deptForbiddenItem')";
//Fb::log($query, 'deptForbidden SQL');            
//            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//        }
//
//        // View
//        foreach($_POST['deptView'] as $deptViewItem)
//        {
//            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) VALUES('$fileId', '1', '$deptViewItem')";
//Fb::log($query, 'deptView SQL');            
//            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//        }
//        
//        // Read
//        foreach($_POST['deptRead'] as $deptReadItem)
//        {
//            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) VALUES('$fileId', '2', '$deptReadItem')";
//Fb::log($query, 'deptRead SQL');            
//            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//        }
//        
//        // Modify
//        foreach($_POST['deptModify'] as $deptModifyItem)
//        {
//            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) VALUES('$fileId', '3', '$deptModifyItem')";
//Fb::log($query, 'deptModify SQL');            
//            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//        }       
// 
//        // Admin
//        foreach($_POST['deptAdmin'] as $deptAdminItem)
//        {
//            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, rights, dept_id) VALUES('$fileId', '4', '$deptAdminItem')";
//Fb::log($query, 'deptAdmin SQL');            
//            mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error() );
//        }        
//        
//        
//      
//     
        /*
         * Begin Specific User Perms
         */
        // Search for simular names in the two array (merge the array.  repetitions are deleted)
        // In case of repetitions, higher priority ones stay.
        // Priority is in this order (admin, modify, read, view)

        $result_array = array();
        if  (isset ($_REQUEST['admin']))
        {
            $result_array = advanceCombineArrays($_REQUEST['admin'], $filedata->ADMIN_RIGHT, $_REQUEST['modify'], $filedata->WRITE_RIGHT);
        }
        
        if (isset ($_REQUEST['modify']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['modify'], $filedata->WRITE_RIGHT);
        }
        
        if (isset ($_REQUEST['read']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['read'], $filedata->READ_RIGHT);
        }

        if (isset ($_REQUEST['view']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['view'], $filedata->VIEW_RIGHT);
        }

        if (isset ($_REQUEST['forbidden']))
        {
            $result_array = advanceCombineArrays($result_array, 'NULL', $_REQUEST['forbidden'], $filedata->FORBIDDEN_RIGHT);
        }
        // INSERT user permissions - view
        for($i = 0; $i<sizeof($result_array); $i++)
        {
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user_perms (fid,uid,rights) VALUES('$fileId', '".$result_array[$i][0]."','". $result_array[$i][1]."')";
Fb::log($query, 'userPerms SQL');            
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query" .mysql_error());
        }

        // use id to generate a file name
        // save uploaded file with new name
        $newFileName = $fileId . '.dat';

        if($khoa==0)
        {
            if (!is_uploaded_file ($_FILES['file']['tmp_name']))
            {
                header('Location: error.php?ec=18');
                exit;
            }
            move_uploaded_file($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);
        }
        else
        {
            copy($GLOBALS['CONFIG']['dataDir'] . '/' . ($fileId-1) . '.dat', $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);
        }
        // back to main page
        $message = urlencode(msg('message_document_added'));

        // Call the plugin API
        callPluginMethod('onAfterAdd',$fileId);
        
        header('Location: details.php?id=' . $fileId . '&last_message=' . $message);
    }
}
?>
<script type="text/javascript">

 

</script>
    <?php
    draw_footer();