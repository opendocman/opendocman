<?PHP
session_start();
if (!session_is_registered('SESSION_UID'))
{
	header('Location:error.php?ec=1');
	exit;
}
include('config.php');
draw_header('Personal Profile');
draw_menu($SESSION_UID);
draw_status_bar('User Information', $last_message);
?>

<html>
<br><br>
<?php echo '<INPUT type="hidden" name="callee" value="' . $_SERVER['PHP_SELF'] . '">'; ?>
<table name="list" align="center", border="1">
<tr><td><center><a href="user.php?submit=Modify+User&item=<?php echo $SESSION_UID; ?>&callee=<?php echo $_SERVER['PHP_SELF']; ?>">Change Personal Info</a><center></td></tr>
</table>
</center>
<?php
draw_footer();
?>
