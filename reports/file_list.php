<?php
/*
Copyright (C) 2013  Stephen Lawrence Jr.

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
 * Provide a spreadsheet report of all the files
 * 
 */

// check for session and $_REQUEST['id']
session_start();

include('../odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor('../index.php?redirection=reports/file_list.php');
}

// open a connection to the database
$user_obj = new User($_SESSION['uid'], $pdo);
// Check to see if user is admin
if (!$user_obj->isAdmin()) {
    header('Location:../error.php?ec=4');
    exit;
}

function cleanExcelData(&$str)
{
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
    $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
}

// filename for download 
$filename = "file_report_" . date('Ymd') . ".csv";
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv; charset=UTF-16LE");
$out = fopen("php://output", 'w');
$flag = false;
$query = "SELECT 
            {$GLOBALS['CONFIG']['db_prefix']}data.realname,
            {$GLOBALS['CONFIG']['db_prefix']}data.description,
            {$GLOBALS['CONFIG']['db_prefix']}data.publishable,
            {$GLOBALS['CONFIG']['db_prefix']}data.status,    
            {$GLOBALS['CONFIG']['db_prefix']}data.id,
            {$GLOBALS['CONFIG']['db_prefix']}user.username,
            {$GLOBALS['CONFIG']['db_prefix']}log.revision,
            CASE {$GLOBALS['CONFIG']['db_prefix']}data.publishable
                WHEN -1 THEN 'Rejected'
                WHEN 0 THEN 'Un-approved'
                WHEN 1 THEN 'Active'
                WHEN 2 THEN 'Archived'
                WHEN -2 THEN 'Deleted'
            END AS 'Publishing Status',
            CASE {$GLOBALS['CONFIG']['db_prefix']}data.status
                WHEN 1 THEN 'Checked Out'
                WHEN 0 THEN 'Not Checked Out'
            END AS 'Check-Out Status'
          FROM 
            {$GLOBALS['CONFIG']['db_prefix']}data 
          LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}user
            ON {$GLOBALS['CONFIG']['db_prefix']}user.id = {$GLOBALS['CONFIG']['db_prefix']}data.owner
          LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}log
              ON {$GLOBALS['CONFIG']['db_prefix']}log.id = {$GLOBALS['CONFIG']['db_prefix']}data.id
          ORDER BY id
          ";
$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    // display field/column names as first row
    if (!$flag) {
        fputcsv($out, array_keys($row), ',', '"');
        $flag = true;
    }
 
    array_walk($row, 'cleanExcelData');
    fputcsv($out, array_values($row), ',', '"');
}

fclose($out);
exit;
