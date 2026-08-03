<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
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

use Aura\Html\Escaper as e;

// uploads a new version of a file

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$user_obj = new User($_SESSION['uid'], $pdo);

if (!$user_obj->canCheckIn()) {
    redirect_visitor('out');
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    $last_message='Failed';
    header('Location:error?ec=2&last_message=' . urlencode($last_message));
    exit;
}

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
        header('Location:error?ec=2&last_message=' . urlencode($last_message));
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
<div class="container">
    <form action="check-in" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <input type="hidden" name="id" value="<?php echo e::h($_GET['id']);
        ?>">
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('label_filename');
        ?></label>
            <div class="col-sm-9">
                <p class="form-control-plaintext fw-bold"><?php echo e::h($real_name);
        ?></p>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('label_description');
        ?></label>
            <div class="col-sm-9">
                <p class="form-control-plaintext"><?php echo e::h($description);
        ?></p>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('label_file_location');
        ?></label>
            <div class="col-sm-9">
                <input name="file" type="file" class="form-control">
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label"><?php echo msg('label_note_for_revision_log');
        ?></label>
            <div class="col-sm-9">
                <textarea name="note" class="form-control" rows="3"></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <button class="btn btn-primary" type="submit" name="submit" value="Check  Document In"><?php echo msg('button_check_in')?></button>
            </div>
        </div>
    </form>
</div>
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
    // Validate CSRF token for check-in operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    
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
        header('Location:error?ec=11&last_message=' . urlencode($last_message));
        exit;
    }

    // Check ini max upload size
    if ($_FILES['file']['error'] == 1) {
        $last_message = 'Upload Failed - check your upload_max_filesize directive in php.ini';
        header('Location: error?last_message=' . urlencode($last_message));
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
        header('Location:error?ec=13&last_message=' . urlencode($last_message));
        exit;
    }

    // query to ensure that user has modify rights
    $file_data_obj = new FileData($id, $pdo);

    if ($file_data_obj->getError() == '' && $file_data_obj->getStatus() == $_SESSION['uid']) {
        // if dir not available, create it
        if (!is_dir($GLOBALS['CONFIG']['revisionDir'])) {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'], 0775)) {
                $last_message=msg('message_directory_creation_failed'). ': ' . $GLOBALS['CONFIG']['revisionDir'] ;
                header('Location:error?ec=23&last_message=' . urlencode($last_message));
                exit;
            }
        }
        if (!is_dir($GLOBALS['CONFIG']['revisionDir'] . $id)) {
            if (!mkdir($GLOBALS['CONFIG']['revisionDir'] . $id, 0775)) {
                $last_message=msg('message_directory_creation_failed') . ': ' . $GLOBALS['CONFIG']['revisionDir'] .  $id;
                header('Location:error?ec=23&last_message=' . urlencode($last_message));
                exit;
            }
        }
        // all OK, proceed!
        
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':uid' => $_SESSION['uid']));
        $result = $stmt->fetch();

        $username = $result['username'];

        // Mark previous 'current' entry as 'incoming' (pending approval)
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = 'incoming', note = :note WHERE id = :id AND revision = 'current'";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':id' => $id, ':note' => $_POST['note']));

        // update file status
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET status = '0', publishable = :publishable, realname = :filename WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':publishable' => $publishable,
            ':filename' => $filename,
            ':id' => $id
        ));

        // Save new version with original filename
        $newFilePath = getFilePath($id, $filename, 'incoming');
        $newFileDir = dirname($newFilePath);
        if (!is_dir($newFileDir)) {
            mkdir($newFileDir, 0775, true);
        }
        copy($_FILES['file']['tmp_name'], $newFilePath);

        // Re-extract text content for search indexing
        $file_mime = File::mime($newFilePath, $filename);
        if (TextExtractorFactory::isExtractable($file_mime)) {
            $extractor = TextExtractorFactory::create($file_mime);
            if ($extractor !== null) {
                $contentText = $extractor->extract($newFilePath);
                $indexQuery = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}content_index (file_id, content_text, indexed_at) VALUES (:file_id, :content_text, NOW()) ON DUPLICATE KEY UPDATE content_text = :content_text2, indexed_at = NOW()";
                $indexStmt = $pdo->prepare($indexQuery);
                $indexStmt->execute([
                    ':file_id' => $id,
                    ':content_text' => $contentText,
                    ':content_text2' => $contentText,
                ]);
            }
        }
    
        AccessLog::addLogEntry($id, 'I', $pdo);
    
        // Send email notifications to reviewers if authorization is required
        if ($GLOBALS['CONFIG']['authorization'] == 'True') {
            $file_obj = new FileData($id, $pdo);
            $get_full_name = $user_obj->getFullName();
            $full_name = $get_full_name[0] . ' ' . $get_full_name[1];
            $from = $user_obj->getEmailAddress();

            $department = $file_obj->getDepartment();

            $reviewer_obj = new Reviewer($id, $pdo);
            $reviewer_list = $reviewer_obj->getReviewersForDepartment($department);

            if ($reviewer_list && count($reviewer_list) > 0) {
                $date = date('Y-m-d H:i:s T');

                // Build email for reviewer notification
                $mail_subject = msg('email_subject_review_status') . $file_obj->getName();
                $mail_body = msg('message_document_checked_in') . ' - ' . msg('email_comments_regarding_review') . PHP_EOL . PHP_EOL;
                $mail_body .= msg('label_filename') . ':  ' . $file_obj->getName() . PHP_EOL . PHP_EOL;
                $mail_body .= msg('label_status') . ': ' . msg('message_document_checked_in') . PHP_EOL . PHP_EOL;
                $mail_body .= msg('date') . ': ' . $date . PHP_EOL . PHP_EOL;
                $mail_body .= msg('author') . ': ' . $full_name . PHP_EOL . PHP_EOL;
                if (!empty($_POST['note'])) {
                    $mail_body .= msg('label_note_for_revision_log') . ': ' . $_POST['note'] . PHP_EOL . PHP_EOL;
                }
                $mail_body .= msg('email_thank_you') . ',' . PHP_EOL . PHP_EOL;
                $mail_body .= msg('email_automated_document_messenger') . PHP_EOL . PHP_EOL;
                $mail_body .= $GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;

                // Send notification emails to reviewers
                $email_obj = new Email();
                $email_obj->setFullName($full_name);
                $email_obj->setSubject($mail_subject);
                $email_obj->setFrom($from);
                $email_obj->setRecipients($reviewer_list);
                $email_obj->setBody($mail_body);
                $email_obj->sendEmail();
            }
        }
        
        // clean up and back to main page
        $last_message = msg('message_document_checked_in');
        header('Location: out?last_message=' . urlencode($last_message));
    }
}
