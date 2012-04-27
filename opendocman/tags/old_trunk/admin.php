<?php
/*
admin.php - provides admin interface
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
draw_header($GLOBALS['lang']['area_admin']);
draw_menu($_SESSION['uid']);
@draw_status_bar($GLOBALS['lang']['area_admin'],$_REQUEST['last_message']);
?>
	<center>	
	<table border="1" cellspacing="5" cellpadding="5" >
	<font color="#FFFFFF"><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo $GLOBALS['lang']['label_user']; ?></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo $GLOBALS['lang']['label_department']; ?></th><th bgcolor ="#83a9f7"><font color="#FFFFFF"><?php echo $GLOBALS['lang']['label_category']; ?></th></font><?php if($user_obj->isRoot()) echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' . $GLOBALS['lang']['label_file_maintenance'] . '</th></font>'; ?>
	  <tr>
	   <td>
<!-- User Admin -->
	    <table border="0">
		 <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=adduser&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_add']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_delete']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_update']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('user.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_display']; ?></a></b></td>
	     </tr>
		</table>
	   </td>
	   <td>
<!-- Department Admin -->
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
	      <td><b><a href="<?php echo $secureurl->encode('department.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_update']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('department.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_display']; ?></a></b></td>
	     </tr>
		</td>
	   </table>
	  </td>
	   <td>
<!-- Category Admin -->
	 	<table border="0">
		 <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=add&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_add']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=deletepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_delete']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=updatepick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_update']; ?></a></b></td>
	     </tr>
	     <tr>
	      <td><b><a href="<?php echo $secureurl->encode('category.php?submit=showpick&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_display']; ?></a></b></td>
	     </tr>
		</td>
	   </table>
	  </td>
<?php
if ( $user_obj->isRoot()	)
{
?>	  
	  <td>
<!-- Admin-Only Section -->
	 	<table border="0" valign="top">
		 <tr>
	      <td ><b><a href="<?php echo $secureurl->encode('delete.php?mode=view_del_archive&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_file_archive']; ?></a></b></td>
	     </tr>
	     <tr>
		 <td><b><a href="<?php echo $secureurl->encode('toBePublished.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_moderation']; ?></a></b></td>
		 </tr>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('rejects.php?mode=root&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_rejected_files']; ?></a></b></td>
		 </tr>
		 <?php
		 if( $GLOBALS['CONFIG']['authorization'] == "On" )
		 {?>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('check_exp.php?&state=' . ($_REQUEST['state']+1)); ?>"><?php echo $GLOBALS['lang']['label_run_expiration']; ?></a></b></td>
		 </tr>
		 <?php } ?>
		 <tr>
		 <td><b><a href="<?php echo $secureurl->encode('file_ops.php?&state=' . ($_REQUEST['state']+1)); ?>&submit=view_checkedout"><?php echo $GLOBALS['lang']['label_checked_out_files']; ?></a></b></td>
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
