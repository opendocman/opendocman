<?php
session_start();
// admin.php - administration functions for admin users 
// check for valid session
// includes
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING'] ) );
	exit;
}
include('config.php');
// open a connection to the database
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
$secureurl = new phpsecureurl;
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
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=adduser&state=' . ($_REQUEST['state']+1)); ?>">Add</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>">Delete</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>">Display</a></b></td>
	     </tr>
		</table>
	   </td>
	   <td>
	 	<table border="0">
		 <tr>
	      <td><b><a href="<?php echo $secureurl->encode('department.php?submit=add&state=' . ($_REQUEST['state']+1)); ?>">Add</a></b></td>
	     </tr>
		 <!-- 
	     <tr>
	      <td><b><a href="department.php?deletepick=1">Delete</a></b></td>
	     </tr>
		 -->
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('department.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('department.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>">Display</a></b></td>
	     </tr>
		</td>
	   </table>
	  </td>
	   <td>
	 	<table border="0">
		 <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=add&state=' . ($_REQUEST['state']+1)); ?>">Add</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>">Delete</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>">Display</a></b></td>
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
	      <td ><b><a href="<?php echo $secureurl->encode('delete.php?mode=view_del_archive&state=' . ($_REQUEST['state']+1)); ?>">Del/Undel</a></b></td>
	     </tr>
	     <tr>
		 <td><b><a href="<?php echo $secureurl->encode('toBePublished.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>">Reviews</a></b></td>
		 </tr>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('rejects.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>">Rejections</a></b></td>
		 </tr>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('check_exp.php?&state=' . ($_REQUEST['state']+1)); ?>">Check Expiration</a></b></td>
		 </tr>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('file_ops.php?&state=' . ($_REQUEST['state']+1)); ?>&submit=view_checkedout">Checked-Out Files</a></b></td>
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
