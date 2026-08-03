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

// (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
// Display revision history

use Aura\Html\Escaper as e;

// check session and $id
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    header('Location:error?ec=2');
    exit;
}

draw_header(msg('area_view_history'), $last_message);
ob_start();
//revision parsing
if (strchr($_REQUEST['id'], '_')) {
    list($_REQUEST['id'], $revision_id) = explode('_', $_REQUEST['id']);
}
$datafile = new FileData($_REQUEST['id'], $pdo);
// verify
if ($datafile->getError() != null) {
    header('Location:error?ec=2');
    exit;
} else {
    // obtain data from resultset

    $owner_full_name = $datafile->getOwnerFullName();
    $owner = $owner_full_name[1].', '.$owner_full_name[0];
    $realname = $datafile->getRealName();
    $category = $datafile->getCategoryName();
    $created = $datafile->getCreatedDate();
    $description = $datafile->getDescription();
    $comments = $datafile->getComment();
    $status = $datafile->getStatus();
    $id = $_REQUEST['id'];

// corrections
if ($description == '') {
    $description = msg('message_no_description_available');
}
    if ($comments == '') {
        $comments = msg('message_no_author_comments_available');
    }
    if ($datafile->isArchived()) {
        $filename = getFilePath($id, $realname, 'archive');
    } else {
        $filename = getFilePath($id, $realname, 'data');
    }

    echo '<!-- DEBUG: id=' . e::h($id) . ' real_name="' . e::h($realname) . '" filename="' . e::h($filename) . '" -->';
    ?>
<div class="container-fluid">

<div class="row mb-3">
    <div class="col-auto">
<?php
// check file status, display appropriate icon
if ($status == 0) {
    echo '<img src="images/file_unlocked.png" alt="" border=0>';
} else {
    echo '<img src="images/file_locked.png"  alt="" border=0>';
}
    ?>
    </div>
    <div class="col">
        <span class="fs-4 fw-bold"><?php echo e::h($realname); ?></span>
    </div>
</div>

<dl class="row mb-0">
<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_category');
    ?></dt>
<dd class="col-sm-9"><?php echo e::h($category);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_file_size');
    ?></dt>
<dd class="col-sm-9"><?php echo display_filesize($filename);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_creation_date');
    ?></dt>
<dd class="col-sm-9"><?php echo fix_date($created);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_owner');
    ?></dt>
<dd class="col-sm-9"><?php echo e::h($owner);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_description');
    ?></dt>
<dd class="col-sm-9"><?php echo e::h($description);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_comment');
    ?></dt>
<dd class="col-sm-9"><?php echo e::h($comments);
    ?></dd>

<dt class="col-sm-3 text-sm-end"><?php echo msg('historypage_revision');
    ?></dt>
<dd class="col-sm-9">
    <div id="revision_current">
<?php
if (isset($revision_id)) {
    if ($revision_id == 0) {
        echo msg('historypage_original_revision');
    } else {
        echo $revision_id;
    }
} else {
    echo msg('historypage_latest');
}
    ?>
    </div>
</dd>
</dl>

<hr class="my-3">

<div class="row mb-3">
    <div class="col-auto">
        <img src="images/revision.png" width=40 height=40 alt="" border="0">
    </div>
    <div class="col">
        <strong><?php echo msg('historypage_history'); ?></strong>
    </div>
</div>

<div class="table-responsive">
	<table class="table table-striped">
	<thead class="table-primary">
	<tr>
	<th><?php echo msg('historypage_version');
    ?></th>
	<th><?php echo msg('historypage_modification');
    ?></th>
	<th><?php echo msg('historypage_by');
    ?></th>
	<th><?php echo msg('historypage_note');
    ?></th>
	</tr>
	</thead>
	<tbody>
<?php
    // query to obtain a list of modifications

    if (isset($revision_id)) {
        $query = "
          SELECT
            u.last_name,
            uuser.first_name,
			l.modified_on,
			l.note,
			l.revision
		  FROM
		    {$GLOBALS['CONFIG']['db_prefix']}log l,
		    {$GLOBALS['CONFIG']['db_prefix']}user u
		  WHERE
		    l.id = :id
          AND
            u.username = l.modified_by
		  AND
		    l.revision <= :revision_id
		  ORDER BY
		    l.modified_on DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':id' => $id,
            ':revision_id'=> $revision_id
        ));
        $result = $stmt->fetchAll();
    } else {
        $query = "
          SELECT
            u.last_name,
            u.first_name,
			l.modified_on,
			l.note,
			l.revision
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}log l,
			{$GLOBALS['CONFIG']['db_prefix']}user u
		  WHERE
			l.id = :id
          AND
            u.username = l.modified_by
          ORDER BY
            l.modified_on DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':id' => $id
        ));
        $result = $stmt->fetchAll();
    }


    $current_revision = $stmt->rowCount();
    // iterate through resultset
    if (count($result) === 0) {
        echo '<tr><td colspan="4">No history entries found (query returned 0 rows for id=' . e::h($id) . ')</td></tr>';
    }
    foreach ($result as $row) {
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $modified_on = $row['modified_on'];
        $note = $row['note'];
        $revision = $row['revision'];

        if (isset($bgcolor) && $bgcolor == "#FCFCFC") {
            $bgcolor = "#E3E7F9";
        } else {
            $bgcolor = "#FCFCFC";
        }

        echo '<tr>';

        $extra_message = '';
        if ($revision === 'current') {
            if (is_file(getFilePath($id, $realname, 'data'))) {
                echo '<td class="text-center"><a href="details?id=' . e::h($id) . '&state=' . (e::h($_REQUEST['state'])) . '"><span class="revision">' . e::h(msg('historypage_latest')) . '</span></a>' . e::h($extra_message);
            } else {
                echo '<td>' . e::h(msg('historypage_latest')) . e::h($extra_message);
            }
        } elseif ($revision === 'incoming' || $revision === 'pending') {
            echo '<td>' . e::h(msg('historypage_pending')) . e::h($extra_message);
            if (is_file(getFilePath($id, $realname, 'revision', (int) $revision))) {
                echo '<td class="text-center"><a href="details?id=' . e::h($id) . '_' . e::h($revision) . '&state=' . (e::h($_REQUEST['state'])) . '"><span class="revision">' . e::h(((int) $revision + 1)) . '</span></a>' . e::h($extra_message);
            } else {
                echo '<td>' . e::h($revision) . e::h($extra_message);
            }
        }
        ?>
                    </td>
                    <td><?php echo fix_date($modified_on);
        ?></td>
                    <td><?php echo e::h($last_name) . ', ' . e::h($first_name);
        ?></td>
                    <td><?php echo e::h($note);
        ?></td>
            </tr>
<?php

    }
    // clean up
?>
	</tbody>
	</table>
</div>

</div>
<?php
// Call the plugin API
callPluginMethod('onAfterHistory', $datafile->getId());
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
}
