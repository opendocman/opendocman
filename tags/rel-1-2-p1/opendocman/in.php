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
header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING'] ) );
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
?>
<CENTER>
<TABLE align="center" width="85%" cellspacing="5" cellpadding="3" border="1"><CAPTION><B><?php echo $count; ?> document(s) check out to you </CAPTION>
<TR><TR><TR>
</TR></TR></TR>
<TR><TD align="center"><B>File Name</TD><TD align="center"><B>Check-In</TD><TD align="center"><B>Owner</TD><TD align="center"><B>Document Creation Date</TD><TD align="center"><B>Document Size</TD><TD align="center"><B>Description</TD></TR>


<?php
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
?>
	<TR valign="middle">
	<TD align="center"><?php echo $realname; ?></TD> 
	<TD align="center"><A href="check-in.php?id=<?php echo $id . '&state=' . ($_REQUEST['state']+1); ?>">Check-In Document</A></TD> 
	<TD align="center"><?php echo $last_name.', '.$first_name; ?></TD> 
	<TD align="center"><?php echo fix_date($created); ?></TD> 
	<TD align="center"><?php echo display_filesize($filename); ?> </TD> 
	<TD align="justify"><?php echo $description; ?></TD>
	</TR>
<?php
	}
?>

<?php
// clean up
mysql_free_result ($result);
?>
</table>
	<form action="out.php">
	<input type="submit" value="Back">
	</form>
</center>

<?php 
	draw_footer();
?>
