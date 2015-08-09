<?php
/*
check-in.php - uploads a new version of a file
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
*/


// check for valid session and $id
session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

require_once("AccessLog_class.php");
require_once("File_class.php");
require_once('Email_class.php');
require_once('Reviewer_class.php');

$user_obj = new User($_SESSION['uid'], $pdo);

if (!$user_obj->canCheckIn()) {
    redirect_visitor('out.php');
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    $last_message='Failed';
    header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
    exit;
}

// includes

// open connection
if (!isset($_POST['submit'])) {
    $id = (int) $_REQUEST['id'];

    // form not yet submitted, display initial form

    // pre-fill the form with some information so that user knows which file is being updated
    $query = "SELECT description, realname FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = :id AND status = :uid";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':id' => $id,
        ':uid' => $_SESSION['uid']
    ));
    $result = $stmt->fetch();

    // in case script is directly accessed, query above will return 0 rows
    if ($stmt->rowCount() <= 0) {
        $last_message='Failed';
        header('Location:error.php?ec=2&last_message=' . urlencode($last_message));
        exit;
    } else {
        draw_header(msg('button_check_in'), $last_message);
        $description = $result['description'];
        $real_name = $result['realname'];

        if ($description == '') {
            $description = msg('message_no_description_available');
        }

        // start displaying form
        ?>
<table border="0" cellspacing="5" cellpadding="5">
    <form action="<?php echo $_SERVER['PHP_SELF'];
        ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $_GET['id'];
        ?>">
        <tr>
            <td><b><?php echo msg('label_filename');
        ?></b></td>
            <td><b><?php echo $real_name;
        ?></b></td>
        </tr>

        <tr>
            <td><b><?php echo msg('label_description');
        ?></b></td>
            <td><?php echo $description;
        ?></td>
        </tr>

        <tr>
            <td><b><?php echo msg('label_file_location');
        ?></b></td>
            <td><input name="file" type="file"></td>
        </tr>

        <tr>
            <td><?php echo msg('label_note_for_revision_log');
        ?></td>
            <td><textarea name="note"></textarea></td>
        </tr>


        <tr>
            <td colspan="4" align="center"><div class="buttons"><button class="positive" type="submit" name="submit" value="Check  Document In"><?php echo msg('button_check_in')?></button></div></td>
        </tr>
    </form>
</table>
        <?php
        draw_footer();
        ?>
<script type="text/javascript">
    function check(select, send_dept, send_all)
    {
        if(send_dept.checked || select.options[select.selectedIndex].value != "0")
            send_all.disabled = true;
        else
        {
            send_all.disabled = false;
            for(var i = 1; i < select.options.length; i++)
                select.options[i].selected = false;
        }
    }
</script>
        <?php

    }//end else
}//end if (!$submit)
else {
    if ($GLOBALS['CONFIG']['authorization'] == 'True') {
        $publishable = '0';
    } else {
        $publishable= '1';
    }
    // form has been submitted, process data
    $id = (int) $_POST['id'];

    $filename = $_FILES['file']['name'];

    // no file!
    if ($_FILES['file']['size'] <= 0) {
        $last_message='Failed';
        header('Location:error.php?ec=11&last_message=' . urlencode($last_message));
        exit;
    }

    // Check ini max upload size
    if ($_FILES['file']['error'] == 1) {
        $last_message = 'Upload Failed - check your upload_max_filesize directive in php.ini';
        header('Location: error.php?last_message=' . urlencode($last_message));
        exit;
    }
    
    // Lets try and determine the true file-type
    $file_mime = File::mime($_FILES['file']['tmp_name'], $_FILES['file']['name']);
    
    // check file type
    foreach ($GLOBALS['CONFIG']['allowedFileTypes'] as $this_type) {
        if ($file_mime == $this_type) {
            $allowedFile = 1;
            break;
        } else {
            $allowedFile = 0;
        }
    }
    
    // illegal file type!
    if ($allowedFile != 1) {
        $last_message='MIMETYPE: ' . $file_mime . ' Failed';
        header('Location:error.php?ec=13&last_message=' . urlencode($last_message));
        exit;
    }

    // query to ensure that user has modify rights
    $file_data_obj = new FileData($id, $pdo);

    if ($file_data_obj->getError() == '' && $file_data_obj->getStatus() == $_SESSION['uid']) {
        //look to see how many revision are there
        $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':id' => $id
        ));
        $result = $stmt->fetchAll();

        $revision_number = $stmt->rowCount();

        // if dir not available, create it
        if (!is_dir($GLOBALS['CONFIG']['revisionDir'])) {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'], 0775)) {
                $last_message=msg('message_directory_creation_failed'). ': ' . $GLOBALS['CONFIG']['revisionDir'] ;
                header('Location:error.php?ec=23&last_message=' . urlencode($last_message));
                exit;
            }
        }
        if (!is_dir($GLOBALS['CONFIG']['revisionDir'] . $id)) {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'] . $id, 0775)) {
                $last_message=msg('message_directory_creation_failed') . ': ' . $GLOBALS['CONFIG']['revisionDir'] .  $id;
                header('Location:error.php?ec=23&last_message=' . urlencode($last_message));
                exit;
            }
        }
        $file_name = $GLOBALS['CONFIG']['dataDir'] . $id .'.dat';
        //read and close
        $file_handler = fopen($file_name, "r");
        $file_content = fread($file_handler, filesize($file_name));
        fclose($file_handler);
        //write and close
        $file_handler = fopen($GLOBALS['CONFIG']['revisionDir'] . $id . '/' . $id . '_' . ($revision_number - 1) . '.dat', "w");
        fwrite($file_handler, $file_content);
        fclose($file_handler);
        // all OK, proceed!

        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':uid' => $_SESSION['uid']));
        $result = $stmt->fetch();

        $username = $result['username'];

        // update revision log
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log set revision='" . intval((intval($revision_number) - 1)) . "' WHERE id = :id and revision = 'current'";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':id' => $id
        ));

        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'current')";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':id' => $id,
            ':username' => $username,
            ':note' => $_POST['note']
        ));

        // update file status
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET status = '0', publishable = :publishable, realname = :filename WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':publishable' => $publishable,
            ':filename' => $filename,
            ':id' => $id
        ));

        // rename and save file
        $newFileName = $id . '.dat';
        copy($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . $newFileName);
    
        AccessLog::addLogEntry($id, 'I', $pdo);
    
        /**
         * Send out email notifications to reviewers
         */
        $file_obj = new FileData($id, $pdo);
        $get_full_name = $user_obj->getFullName();
        $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
        
        $department = $file_obj->getDepartment();
        
        $reviewer_obj = new Reviewer($id, $pdo);
        $reviewer_list = $reviewer_obj->getReviewersForDepartment($department);

        $date = date('Y-m-d H:i:s T');
        
        // Build email for general notices
        $mail_subject = msg('checkinpage_file_was_checked_in');
        $mail_body2 = msg('checkinpage_file_was_checked_in') . PHP_EOL;
        $mail_body2.=msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL;
        $mail_body2.=msg('label_status') . ': ' . msg('addpage_new') . PHP_EOL;
        $mail_body2.=msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('addpage_uploader') . ': ' . $full_name . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
        $mail_body2.=msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
        $mail_body2.=$GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
        
        $email_obj = new Email();
        $email_obj->setFullName($full_name);
        $email_obj->setSubject($mail_subject);
        $email_obj->setFrom($full_name . ' <' . $user_obj->getEmailAddress() . '>');
        $email_obj->setRecipients($reviewer_list);
        $email_obj->setBody($mail_body2);
        $email_obj->sendEmail();
        
        // clean up and back to main page
        $last_message = msg('message_document_checked_in');
        header('Location: out.php?last_message=' . urlencode($last_message));
    }
}
