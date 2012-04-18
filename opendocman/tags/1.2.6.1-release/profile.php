<?php
/*
profile.php - page for changing personal info
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
 
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
if (!isset ($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}
include('odm-load.php');

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

draw_header(msg('area_personal_profile'), $last_message);
?>

<html>
    <br><br>
    <INPUT type="hidden" name="callee" value="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table name="list" align="center" border="0">
           <tr><td><a href="user.php?submit=Modify+User&item=<?php echo $_SESSION['uid']; ?>&caller=<?php echo $_SERVER['PHP_SELF']; ?>"><?php echo msg('profilepage_update_profile')?></a></td></tr>
                        </table>
<?php
draw_footer();