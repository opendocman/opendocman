<?PHP
session_start();
if (!session_is_registered('uid'))
{
	header('Location:error.php?ec=1');
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
