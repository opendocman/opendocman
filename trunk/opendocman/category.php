<?php
// category.php - Administer Categories
// check for valid session 
session_start();
if (!session_is_registered('SESSION_UID'))
{
	draw_error('error.php?ec=1');
	exit;
}
// includes
include('config.php');

if(isset($submit) and $submit != 'Cancel')
{
	draw_menu($SESSION_UID);
}

if($submit == 'add')
{
                if (!isset($last_message))
                {
                        $last_message='';
                }
		draw_header('Add New Category');
		draw_status_bar('Add New Category', $last_message);
		// Check to see if user is admin
		$user_obj = new User($SESSION_UID, $connection, $database);
		if(!$user_obj->isAdmin())        
		{
				draw_error('error.php?ec=4');
				exit;
		}
?>
<center>
<form action="commitchange.php?last_message=<?php $last_message; ?>" method="GET" enctype="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="5">
	<tr>
		<td><b>Category</b></td>
		<td colspan="3"><input name="category" type="text"></td>
	</tr>
	<input type="hidden" name="addcategory" value="Add Category">
	<tr>
		
</table>
<input type="Submit" name="addcategory" value="Add Category">
</form>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
<input type="Submit" name="submit" value="Cancel">
</form>
</center>
<?php
	draw_footer();
}
elseif($submit == 'delete')
{
	$delete='';
	draw_header('Category Deletion');
	draw_status_bar('Delete Item', $last_message);
	// query to show item
	echo '<center>'; 
	echo '<table border=0>';
	$query = 'SELECT id, name FROM category where id="' . $item . '"';
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
		echo '<tr><td>Id # :</td><td>' . $id . '</td></tr>';
		echo '<tr><td>Name :</td><td>' . $name . '</td></tr>';
	}
?>
	<TABLE name="delete_table">
	<form action="commitchange.php?last_message=<?php echo $last_message; ?>&id=<?php echo $item;?> " method="POST" enctype="multipart/form-data">
		<tr>
			<td valign="top">Are you sure you want to delete this?</td>
			<td colspan="4" align="center"><input type="Submit" name="deletecategory" value="Yes"></td>
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
		<td colspan="4" align="center"><input type="Submit" name="" value="No, Cancel"></td>
	</form>
		</tr>
	</TABLE>
<?php
	draw_footer();
}
elseif($submit == 'deletepick')
{
        if (!isset($last_message))
        {
                $last_message='';
        }
	$deletepick='';
	draw_header('Category Selection');
	draw_status_bar('Choose Item to Delete', $last_message);
?>
	<center>
	<table border="0" cellspacing="5" cellpadding="5">
	<form action="<?php $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
			<tr>
				<td><b>Category</b></td>
				<td colspan=3><select name="item">
<?php
	$query = 'SELECT id, name FROM category ORDER BY name';
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
		$str = '<option value="' . $id . '"';
		$str .= '>' . $name . '</option>';
		echo $str;
	}
	mysql_free_result ($result);
	$deletepick='';
?>
	</select></td>
	<tr>
		<td></td>
		<td colspan="2" align="center">
		<input type="Submit" name="submit" value="delete">
		<input type="Submit" name="submit" value="Cancel">
		</td>
	</tr>
			</form>
		</table>
		</center>
<?php
	draw_footer();
}
elseif($submit == 'Show Category')
{
	// query to show item
	draw_header('Category Information');
	draw_status_bar('Display Item Information', $last_message);
	echo '<center>';
	// Select name
	$query = "SELECT category.name FROM category where category.id='$item'";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	echo('<table name="main" cellspacing="15" border="0">');
	list($category) = mysql_fetch_row($result);
	echo '<th>Name</th><th>ID</th>';
	echo '<tr>';
	echo '<td>'.$category.'</td>';	
	echo '<td>'.$item.'</td>';
	echo '</tr>';	
?>
	<form action="admin.php?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
		<tr>
			<td colspan="4" align="center"><input type="Submit" name="" value="Back"></td>
		</tr>
	</form>
	</table>
<?php
	
	draw_footer();
}
elseif($submit == 'showpick')
{
                if (!isset($last_message))
                {
                        $last_message='';
                }       
		draw_header('Category Selection');
		draw_status_bar('Choose item to view', $last_message);
		$showpick='';
?>
			<center>
			<table border="0" cellspacing="5" cellpadding="5">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
			<tr>
			<td><b>Category</b></td>
			<td colspan="3"><select name="item">
<?php
			$query = 'SELECT id, name FROM category ORDER BY name';
			$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
		while(list($id, $name) = mysql_fetch_row($result))
		{
				echo '<option value="' . $id . '">' . $name . '</option>';
		}
		mysql_free_result ($result);
?>
		</select></td>
		<tr>
		<td></td>
		<td colspan="3" align="center">
		<input type="Submit" name="submit" value="Show Category">
		<input type="Submit" name="submit" value="Cancel">
		</td>
		</tr>
		</form>
		</table>
		</center>
	</body>
</html>
<?php
	draw_footer();
}
elseif($submit == 'Update')
{
        if (!isset($last_message))
        {
                $last_message='';
        }
	draw_header('Update Category');
	draw_status_bar('Update Item', $last_message);
?>
	<center>
		<table border="0" cellspacing="5" cellpadding="5">
			<tr>
		<form action="commitchange.php?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM category where id='$item'";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
		echo '<tr>';
		echo '<td><input type="textbox" name="name" value="'.$name.'"></td>';
		echo '<td><input type="hidden" name="id" value="'.$id.'"></td>';
		echo '</tr>';
	}
	mysql_free_result ($result);
?>
		<td>
			<input type="Submit" name="updatecategory" value="Modify Category">
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $last_message; ?>">
		<input type="Submit" name="submit" value="Cancel">
	</form>
			</td>
		</tr>
	</table>
</center>
<?php
draw_footer();
}
elseif($submit == 'updatepick')
{
        if (!isset($last_message))
        {
                $last_message='';
        }
	draw_header('Category Selection');
	draw_status_bar('Modify Item',$last_message);
?>
	<center>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
			<table border="0">
				<tr>
				<td><b>Category to modify:</b></td>
				<td colspan="3"><select name="item">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM category ORDER BY name";
	$result = mysql_db_query($database, $query, $connection) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
		echo '<option value="'.$id.'">'.$name.'</option>';
	}
	mysql_free_result ($result);
?>
		</td>
	</tr>
	<tr>
        <td></td>
        <td colspan="3" align="center">
        <input type="Submit" name="submit" value="Update">
        <input type="Submit" name="submit" value="Cancel">
        </td>
        </tr>
	</form></TD>
	</tr>
	</table>
	</center>
<?php
	draw_footer();
}
elseif ($submit == 'Cancel')
{
		$last_message=urlencode('Action canceled');
		header ('Location: admin.php?last_message='.$last_message);
}
?>
