<?php
session_start();
// admin.php - administration functions for admin users 
// check for valid session
// includes
include('config.php');
if (!isset($_SESSION['uid']))
{
	draw_error('error.php?ec=1');
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
	      <td><b><a href="user.php?submit=adduser">Add</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="user.php?submit=deletepick">Delete</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="user.php?submit=updatepick">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="user.php?submit=showpick">Display</a></b></td>
	     </tr>
		</table>
	   </td>
	   <td>
	 	<table border="0">
		 <tr>
	      <td><b><a href="department.php?submit=add">Add</a></b></td>
	     </tr>
		 <!-- 
	     <tr>
	      <td><b><a href="department.php?deletepick=1">Delete</a></b></td>
	     </tr>
		 -->
	     <tr>
	      <td><b><a href="department.php?submit=updatepick">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="department.php?submit=showpick">Display</a></b></td>
	     </tr>
		</td>
	   </table>
	  </td>
	   <td>
	 	<table border="0">
		 <tr>
	      <td><b><a href="category.php?submit=add">Add</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="category.php?submit=deletepick">Delete</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="category.php?submit=updatepick">Update</a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="category.php?submit=showpick">Display</a></b></td>
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
	      <td ><b><a href="delete.php?mode=view_del_archive">Del/Undel</a></b></td>
	     </tr>
	     <tr>
		 <td><b><a href="toBePublished.php?mode=root">Reviews</a></b></td>
		 </tr>
		 <tr>
		 <td><b><a href="rejects.php?mode=root">Rejections</a></b></td>
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
