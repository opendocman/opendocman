<?php
/*
Copyright (C) 2012-2013  Stephen Lawrence Jr.

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

// check for session and $_REQUEST['id']
session_start();

include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

include('udf_functions.php');

// open a connection to the database
$user_obj = new User($_SESSION['uid'], $pdo);
// Check to see if user is admin
if (!$user_obj->isAdmin()) {
    header('Location:error.php?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('accesslogpage_access_log'), $last_message);

$query = "SELECT 
            a.*,
            d.realname,
            u.username
          FROM 
            {$GLOBALS['CONFIG']['db_prefix']}access_log a
          INNER JOIN 
            {$GLOBALS['CONFIG']['db_prefix']}data AS d ON a.file_id = d.id
          INNER JOIN 
            {$GLOBALS['CONFIG']['db_prefix']}user AS u ON a.user_id = u.id
        ";
$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll();

$actions_array = array(
    "A" => msg('accesslogpage_file_added'),
    "B" => msg('accesslogpage_reserved'),
    "C" => msg('accesslogpage_reserved'),
    "V" => msg('accesslogpage_file_viewed'),
    "D" => msg('accesslogpage_file_downloaded'),
    "M" => msg('accesslogpage_file_modified'),
    "I" => msg('accesslogpage_file_checked_in'),
    "O" => msg('accesslogpage_file_checked_out'),
    "X" => msg('accesslogpage_file_deleted'),
    "Y" => msg('accesslogpage_file_authorized'),
    "R" => msg('accesslogpage_file_rejected')
    );
$accesslog_array = array();

foreach ($result as $row) {
    $details_link = 'details.php?id=' . $row['file_id'] . '&state=' . ($_REQUEST['state'] + 1);

    $accesslog_array[] = array(
        'user_id' => $row['user_id'],
        'file_id' => $row['file_id'],
        'user_name' => $row['username'],
        'realname' => $row['realname'],
        'action' => $actions_array[$row['action']],
        'details_link' => $details_link,
        'timestamp' => $row['timestamp']
    );
}

$GLOBALS['smarty']->assign('accesslog_array', $accesslog_array);
display_smarty_template('access_log.tpl');

draw_footer();
