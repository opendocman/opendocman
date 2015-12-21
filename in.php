<?php
use Aura\Html\Escaper as e;

/*
in.php - display files checked out to user, offer link to check back in
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
*/


// check session
session_start();

// includes
include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$user_obj = new User($_SESSION['uid'], $pdo);

if (!$user_obj->canCheckIn()) {
    redirect_visitor('out.php');
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('button_check_in'), $last_message);

// query to get list of documents checked out to this user
$query = "
  SELECT
    d.id,
    u.last_name,
    u.first_name,
	d.realname,
    d.created,
    d.description,
    d.status
  FROM
    {$GLOBALS['CONFIG']['db_prefix']}data as d,
    {$GLOBALS['CONFIG']['db_prefix']}user as u
  WHERE
    d.status = :uid
  AND
    d.owner = u.id
";
$stmt = $pdo->prepare($query);
$stmt->execute(array(
    ':uid' => $_SESSION['uid']
));
$result = $stmt->fetchAll();

// how many records?
$count = $stmt->rowCount();
if ($count == 0) {
    echo '<img src="images/exclamation.gif"> ' . msg('message_no_documents_checked_out');
} else {
    echo '<table border="0" hspace="0" hgap="0" cellpadding="1" cellspacing="1">';
    echo '<caption><b>' . msg('message_document_checked_out_to_you'). ' : ' . e::h($count) . '</caption>';
    echo '<tr bgcolor="#83a9f7">';
    echo '<td class="listtable"><b>' .msg('button_check_in'). '</b></td>';
    echo '<td class="listtable"><b>' .msg('label_file_name'). '</b></td>';
    echo '<td class="listtable"><b>' .msg('label_description'). '</b></td>';
    echo '<td class="listtable"><b>' .msg('label_created_date'). '</b></td>';
    echo '<td class="listtable"><b>' .msg('owner'). '</b></td>';
    echo '<td class="listtable"><b>' .msg('label_size'). '</b></td>';
    echo '</tr>';

    $row_color = "#FCFCFC";
    // iterate through resultset
    foreach ($result as $row) {
        $id = $row['id'];
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $realname = $row['realname'];
        $created = $row['created'];
        $description = $row['description'];
        $status = $row['status'];

        // correction
        if ($description == '') {
            $description = msg('message_no_information_available');
        }
        $filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
        // display list
        $highlighted_color = '#bdf9b6';

        echo '<tr valign="middle" bgcolor="' . $row_color . '" onmouseover="this.style.backgroundColor=\'' . $highlighted_color . '\';" onmouseout="this.style.backgroundColor=\'' . $row_color . '\';">';
        echo '<td class="listtable"><div class="buttons"><a href="check-in.php?id=' . e::h($id) . '&amp;state=' . e::h(($_REQUEST['state']+1)) . '" class="regular"><img src="images/import-2.png" alt="checkin"/>' .msg('button_check_in'). '</a></div>';
        echo '</td>';
        echo '<td class="listtable">' . e::h($realname) . '</td>';
        echo '<td class="listtable">' . e::h($description) . '</td>';
        echo '<td class="listtable">' . fix_date(e::h($created)) . '</td> ';
        echo '<td class="listtable">' . e::h($last_name) . ', ' . e::h($first_name) . '</td> ';
        echo '<td class="listtable">' . display_filesize(e::h($filename)) . '</td> ';
        echo '</tr>';

        if ($row_color == "#FCFCFC") {
            $row_color = "#E3E7F9";
        } else {
            $row_color = "#FCFCFC";
        }
    }

    // clean up

    echo '</table>';
}

draw_footer();
