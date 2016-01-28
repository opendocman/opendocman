<?php
use Aura\Html\Escaper as e;

/*
history.php - display revision history
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2013 Stephen Lawrence Jr.

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


// check session and $id
session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (!isset($_REQUEST['id']) || $_REQUEST['id'] == '') {
    header('Location:error.php?ec=2');
    exit;
}

draw_header(msg('area_view_history'), $last_message);
//revision parsing
if (strchr($_REQUEST['id'], '_')) {
    list($_REQUEST['id'], $revision_id) = explode('_', $_REQUEST['id']);
}
$datafile = new FileData($_REQUEST['id'], $pdo);
// verify
if ($datafile->getError() != null) {
    header('Location:error.php?ec=2');
    exit;
} else {
    // obtain data from resultset

    $owner_full_name = $datafile->getOwnerFullName();
    $owner = $owner_full_name[1].', '.$owner_full_name[0];
    $real_name = $datafile->getRealName();
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
        $filename = $GLOBALS['CONFIG']['archiveDir'] . e::h($id) . '.dat';
    } else {
        $filename = $GLOBALS['CONFIG']['dataDir'] . e::h($id) . '.dat';
    }
    ?>
<table border="0" width=80% cellspacing="4" cellpadding="1">

<tr>
<td align="right">
<?php
// check file status, display appropriate icon
if ($status == 0) {
    echo '<img src="images/file_unlocked.png" alt="" border=0 align="absmiddle">';
} else {
    echo '<img src="images/file_locked.png"  alt="" border=0 align="absmiddle">';
}
    echo '</td>';
    echo '<td align="left"><font size="+1">'. e::h($real_name) .'</font></td>';
    ?>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_category');
    ?></th><td><?php echo e::h($category);
    ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_file_size');
    ?></th><td> <?php echo display_filesize($filename);
    ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_creation_date');
    ?></th><td> <?php echo fix_date($created);
    ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_owner');
    ?></th><td> <?php echo e::h($owner);
    ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_description');
    ?></th><td> <?php echo e::h($description);
    ?></td>
</tr>

<tr>
<th valign=top align=right><?php echo msg('historypage_comment');
    ?></th><td> <?php echo e::h($comments);
    ?></td>
</tr>
<tr>
<th valign=top align=right><?php echo msg('historypage_revision');
    ?></th><td>
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
</td>
</tr>

<!-- history table -->
<tr>
<td align="right">
<img src="images/revision.png" width=40 height=40 alt="" border="0" align="absmiddle">
</td>
<td><?php echo msg('historypage_history');
    ?></td>
</td>
</tr>

<tr>
<td colspan="2" align="center">
	<table border="0" cellspacing="5" cellpadding="5">
	<tr bgcolor="#83a9f7">
	<th><font size=-1><?php echo msg('historypage_version');
    ?></font></th>
	<th><font size=-1><?php echo msg('historypage_modification');
    ?></font></th>
	<th><font size=-1><?php echo msg('historypage_by');
    ?></font></th>
	<th><font size=-1><?php echo msg('historypage_note');
    ?></font></th>
	</tr>
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

        echo '<tr bgcolor=' . $bgcolor . '>';

        $extra_message = '';
        if (is_file($GLOBALS['CONFIG']['revisionDir'] . $id . '/' . $id . "_$revision.dat")) {
            echo '<td align=center><font size="-1"> <a href="details.php?id=' . e::h($id) . '_' . e::h($revision) . '&state=' . (e::h($_REQUEST['state'])) . '"><div class="revision">' . e::h(($revision + 1)) . '</div></a>' . e::h($extra_message);
        } else {
            echo '<td><font size="-1">' . e::h($revision) . e::h($extra_message);
        }
        ?>
                    </font></td>
                    <td><font size="-1"><?php echo fix_date($modified_on);
        ?></font></td>
                    <td><font size="-1"><?php echo e::h($last_name) . ', ' . e::h($first_name);
        ?></font></td>
                    <td><font size="-1"><?php echo e::h($note);
        ?></font></td>
            </tr>
<?php

    }
    // clean up
?>
	</table>
</td>
</tr>

</table>
<?php
// Call the plugin API
callPluginMethod('onAfterHistory', $datafile->getId());
    draw_footer();
}
