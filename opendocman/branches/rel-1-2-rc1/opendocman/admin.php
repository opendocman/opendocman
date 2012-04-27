<?php
session_start();
// admin.php - administration functions for admin users 
// check for valid session
// includes
include('config.php');
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING']) );
	exit;
}
// open a connection to the database
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
// Check to see if user is admin
if(!$user_obj->isAdmin())
{
	draw_error('error.php?ec=4');
	exit;
}
draw_header('Admin');
draw_menu($_SESSION['uid']);
@draw_status_bar('Admin',$_REQUEST['last_message']);
?>
<center>	
<table border="1" cellspacing="5" cellpadding="5" >
<font color="#FFFFFF"><th bgcolor ="#83a9f7"><font color="#FFFFFF">Users</th><th bgcolor ="#83a9f7"><font color="#FFFFFF">Departments</th><th bgcolor ="#83a9f7"><font color="#FFFFFF">Categories</th></font><?php if($user_obj->isRoot()) echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">File Operations</th></font>'; ?>
<tr>
<td>
<table border="0">
<tr>
<td><b><a href="user.php?submit=adduser<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Add</a></b></td>
</tr>
<tr>
<td><b><a href="user.php?submit=deletepick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Delete</a></b></td>
</tr>
<tr>
<td><b><a href="user.php?submit=updatepick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Update</a></b></td>
</tr>
<tr>
<td><b><a href="user.php?submit=showpick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Display</a></b></td>
</tr>
</table>
</td>
<td>
<table border="0">
<tr>
<td><b><a href="department.php?submit=add<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Add</a></b></td>
</tr>
<!-- 
<tr>
<td><b><a href="department.php?deletepick=1">Delete</a></b></td>
</tr>
-->
<tr>
<td><b><a href="department.php?submit=updatepick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Update</a></b></td>
</tr>
<tr>
<td><b><a href="department.php?submit=showpick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Display</a></b></td>
</tr>
</td>
</table>
</td>
<td>
<table border="0">
<tr>
<td><b><a href="category.php?submit=add<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Add</a></b></td>
</tr>
<tr>
<td><b><a href="category.php?submit=deletepick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Delete</a></b></td>
</tr>
<tr>
<td><b><a href="category.php?submit=updatepick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Update</a></b></td>
</tr>
<tr>
<td><b><a href="category.php?submit=showpick<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Display</a></b></td>
</tr>
</td>
</table>
</td>
	<?php
if ( $user_obj->isRoot()	)
{
	?>	  
		<td>
		<table border="0" valign="top">
		<tr>
		<td ><b><a href="delete.php?mode=view_del_archive<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Del/Undel</a></b></td>
		</tr>
		<tr>
		<td><b><a href="toBePublished.php?mode=root<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Reviews</a></b></td>
		</tr>
		<tr>
		<td><b><a href="rejects.php?mode=root<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Rejections</a></b></td>
		</tr>
		<tr>
		<td><b><a href="check_exp.php?<?php echo '&state=' . ($_REQUEST['state']+1); ?>">Check Expiration</a></b></td>
		</tr>
		<tr>
		<td><b><a href="file_ops.php?<?php echo '&state=' . ($_REQUEST['state']+1); ?>&submit=view_checkedout">Checked-Out Files</a></b></td>
		</tr>
		</table>
		</td>
		<?php
}
?>
</tr>
</table>
</center>

<?php draw_footer(); ?>
