<?php
/*
profile.php - page for changing personal info
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

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

session_start();
if (!session_is_registered('uid'))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING'] ) );
	exit;
}
include('config.php');
draw_header('Personal Profile');
draw_menu($_SESSION['uid']);
@draw_status_bar('User Information', $_REQUEST['last_message']);
?>

<html>
<br><br>
<INPUT type="hidden" name="callee" value="<?php echo $_SERVER['PHP_SELF']; ?>">
<table name="list" align="center", border="1">
<tr><td><center><a href="user.php?submit=Modify+User&item=<?php echo $_SESSION['uid']; ?>&caller=<?php echo $_SERVER['PHP_SELF']; ?>">Change Personal Info</a><center></td></tr>
</table>
</center>
<?php
draw_footer();
?>
