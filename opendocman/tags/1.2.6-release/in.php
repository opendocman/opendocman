<?php
/*
in.php - display files checked out to user, offer link to check back in
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


// check session
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

// includes
include('odm-load.php');
draw_header(msg('button_check_in'), $last_message);

// query to get list of documents checked out to this user
$query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id, 
        {$GLOBALS['CONFIG']['db_prefix']}user.last_name,
        {$GLOBALS['CONFIG']['db_prefix']}user.first_name,
				realname, 
				created, 
				description, 
				status 
				FROM {$GLOBALS['CONFIG']['db_prefix']}data, 
        {$GLOBALS['CONFIG']['db_prefix']}user
				WHERE status = '{$_SESSION['uid']}' 
				AND {$GLOBALS['CONFIG']['db_prefix']}data.owner = {$GLOBALS['CONFIG']['db_prefix']}user.id";

$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

// how many records?
$count = mysql_num_rows($result);
if ($count == 0)
{
    echo '<img src="images/exclamation.gif"> ' . msg('message_no_documents_checked_out');
}
else
{
    echo '<table border="0" hspace="0" hgap="0" cellpadding="1" cellspacing="1">';
    echo '<caption><b>' . msg('message_document_checked_out_to_you'). ' : ' . $count . '</caption>';
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
    while(list($id, $last_name, $first_name, $realname, $created, $description, $status) = mysql_fetch_row($result))
    {
        // correction
        if ($description == '')
        {
            $description = msg('message_no_information_available');
        }
        $filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
        // display list
        $highlighted_color = '#bdf9b6';

        echo '<tr valign="middle" bgcolor="' . $row_color . '" onmouseover="this.style.backgroundColor=\'' . $highlighted_color . '\';" onmouseout="this.style.backgroundColor=\'' . $row_color . '\';">';
        echo '<td class="listtable"><div class="buttons"><a href="check-in.php?id=' . $id . '&amp;state=' .($_REQUEST['state']+1) . '" class="regular"><img src="images/import-2.png" alt="checkin"/>' .msg('button_check_in'). '</a></div>';
        echo '</td>';
        echo '<td class="listtable">' . $realname . '</td>';
        echo '<td class="listtable">' . $description . '</td>';
        echo '<td class="listtable">' . fix_date($created) . '</td> ';
        echo '<td class="listtable">' . $last_name . ', ' . $first_name . '</td> ';
        echo '<td class="listtable">' . display_filesize($filename) . '</td> ';
        echo '</tr>';

        if ( $row_color == "#FCFCFC" )
        {
            $row_color = "#E3E7F9";
        }
        else
        {
            $row_color = "#FCFCFC";
        }
    }

    // clean up
    mysql_free_result ($result);

    echo '</table>';
}

draw_footer();