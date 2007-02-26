<?php
/*
in.php - display files checked out to user, offer link to check back in
Copyright (C) 2002, 2003, 2004  Stephen Lawrence

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
if (!session_is_registered('uid'))
{
header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
exit;
}
// includes
include('config.php');
draw_header('Check-in');
draw_menu($_SESSION['uid']);
@draw_status_bar('Documents Currently Checked Out To You', $_POST['last_message']); 

// query to get list of documents checked out to this user
$query = "SELECT data.id, user.last_name, user.first_name, realname, created, description, status FROM data,user WHERE status = '$_SESSION[uid]' AND data.owner = user.id";
$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

// how many records?
$count = mysql_num_rows($result);
if ($count == 0)
	echo '<img src="images/exclamation.gif"> No documents checked out to you';
else
{
	echo '<table cellspacing="5" cellpadding="3" border="1"><caption><B>'.$count.' document';
	if ($count != 1)
		echo 's';
	echo ' checked out to you</CAPTION>';
	echo '<TR bgcolor="#83a9f7">';
        echo '<TD align="center"><B>Check-In</TD>';
        echo '<TD align="center"><B>File Name</TD>';
        echo '<TD align="center"><B>Description</TD>';
        echo '<TD align="center"><B>Created Date</TD>';
        echo '<TD align="center"><B>Owner</TD>';
        echo '<TD align="center"><B>Size</TD>';
        echo '</TR>';

        $row_color = "#FCFCFC";
	// iterate through resultset
	while(list($id, $last_name, $first_name, $realname, $created, $description, $status) = mysql_fetch_row($result))
	{
	// correction
	if ($description == '') 
        {
            $description = 'No information available'; 
        }
	$filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
	// display list

	echo '<TR valign="middle" bgcolor="' . $row_color . '">';
	echo '<TD align="center"><A href="check-in.php?id=' . $id . '&state=' . ($_REQUEST['state']+1) . '"><img src="images/check-in.png" border=0 width=45 height=45></A></TD>';
	echo '<TD align="center">' . $realname . '</TD>';
	echo '<TD align="justify">' . $description . '</TD>';
	echo '<TD align="center">' . fix_date($created) . '</TD> ';
	echo '<TD align="center">' . $last_name . ', ' . $first_name . '</TD> ';
	echo '<TD align="center">' . display_filesize($filename) . '</TD> ';
	echo '</TR>';

        if ( $row_color == "#FCFCFC" )
          $row_color = "#E3E7F9";
        else
          $row_color = "#FCFCFC";
	}

	// clean up
	mysql_free_result ($result);

	echo '</table>';
}

draw_footer();
?>
