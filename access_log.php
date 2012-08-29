<?php
/*
Copyright (C) 2012  Stephen Lawrence Jr.

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
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

include('odm-load.php');
include('udf_functions.php');
$secureurl = new phpsecureurl;

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('accesslogpage_access_log'), $last_message);

$query = "SELECT 
            {$GLOBALS['CONFIG']['db_prefix']}access_log.*, 
            {$GLOBALS['CONFIG']['db_prefix']}data.realname, 
            {$GLOBALS['CONFIG']['db_prefix']}user.username
          FROM 
            {$GLOBALS['CONFIG']['db_prefix']}access_log 
          INNER JOIN 
            {$GLOBALS['CONFIG']['db_prefix']}data ON {$GLOBALS['CONFIG']['db_prefix']}access_log.file_id={$GLOBALS['CONFIG']['db_prefix']}data.id
          INNER JOIN 
            {$GLOBALS['CONFIG']['db_prefix']}user ON {$GLOBALS['CONFIG']['db_prefix']}access_log.user_id = {$GLOBALS['CONFIG']['db_prefix']}user.id
          WHERE 
            {$GLOBALS['CONFIG']['db_prefix']}access_log.user_id='$_SESSION[uid]'
        ";
$result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());

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

while ($row = mysql_fetch_array($result))
{
    $details_link = $secureurl->encode('details.php?id=' . $row['file_id'] . '&state=' . ($_REQUEST['state'] + 1));

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