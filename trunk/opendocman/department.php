<?php

// department.php - Administer Departments
// check for valid session 
session_start();
if (!session_is_registered('SESSION_UID'))
{
	draw_error('error.php?ec=1');
	exit;
}

// includes
include('config.php');
// open a connection to the database
if(isset($submit) and $submit != 'Cancel')
{
	draw_menu($SESSION_UID);
}

if($submit=='add')
{
draw_status_bar('Add New Department', $last_message);

	draw_header('Add New Department');
	// Check to see if user is admin
	$user_obj = new User($SESSION_UID, $GLOBALS['connection'], $GLOBALS['database']);
	if(!$user_obj->isAdmin())        
	{
		draw_error('error.php?ec=4');
		exit;
	}
?>

<center>
 <table border="0" cellspacing="5" cellpadding="5">
   <!-- for file upload, note ENCTYPE -->
   <form action="commitchange.php" method="POST" enctype="multipart/form-data">

    <tr>
     <td><b>Department</b></td>
     <td colspan="3"><input name="department" type="text"></td>
    </tr>
	<input type="hidden" name="adddepartment" value="Add Department">
    <tr>
    <td></td>
     <td colspan="4" align="center"><input type="Submit" name="adddepartment" value="Add Department"></td>
    </form>
    </tr>
    <tr>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <td></td>
     <td align="center">
      <input type="Submit" name="submit" value="Cancel">
     </td>
     </form>
    </tr>
  </table>
  </center>
<?php
draw_footer();
}
elseif($submit =='delete')
{
	$delete='';
	draw_header('Department Deletion');
	draw_status_bar('Delete Department', $message);
 // query to show item
	echo '<center>'; 
	echo '<table border="0">';
	$query = "SELECT id, name FROM department where id='$item'";
    $result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($id, $name) = mysql_fetch_row($result))
    {
        echo '<tr><td>Id # :</td><td>' . $id . '</td></tr>';
        echo '<tr><td>Name :</td><td>' . $name . '</td></tr>';
    }
?>
    <form action="commitchange.php?id=<?php echo $item;?> " method="POST" enctype="multipart/form-data">
     <tr>
      <td valign="top">Are you sure you want to delete this?</td>
	  <td colspan="4" align="center"><input type="Submit" name="deletedepartment" value="Yes"></td>
    </form>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
     <td colspan="4" align="center"><input type="Submit" name="" value="No, Cancel"></td>
    </form>
    </tr>
   </form>
<?php
draw_footer();
}
elseif($submit == 'deletepick'){
$deletepick='';
draw_header('Department Selection');
draw_status_bar('Choose Department to Delete', $message);
?>
    <center>
        <table border="0" cellspacing="5" cellpadding="5">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
        <tr>
        <td><b>Department</b></td>
        <td colspan="3"><select name="item">
<?php
	$query = 'SELECT id, name FROM department ORDER BY name';
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
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
     <td colspan="4" align="center"><input type="Submit" name="delete" value="Delete"></td>
    </tr>
    </form>
   	</table>
	</center>
    </body>
    </html>
<?php
}
elseif($submit == 'showitem')
{
 // query to show item
	draw_header('Department Information');
 	draw_status_bar('Display Item Information', $last_message);
    echo '<center>';
	//select name
	$query = "SELECT name,id FROM department where id='$item'";
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    echo '<table name="main" cellspacing="15" border="0">';
    echo '<th>ID</th><th>Dept. Name</th>';
	list($department) = mysql_fetch_row($result);
	echo '<tr><td>' . $item . '</td>';
	echo '<td>' . $department . '</td></tr>';
?>
    <tr>
    <td align="center" colspan="2"><b>Users in this department</b></td>
    </tr>
<?php
    // Display all users assigned to this department
    $query = "SELECT department.id, user.first_name, user.last_name FROM department, user where department.id='$item' and user.department='$item'";
    $result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($id, $first_name, $last_name) = mysql_fetch_row($result))
	{	
        echo '<tr><td colspan="2">'.$first_name.' '.$last_name.'</td></tr>';
    }
?>
    <form action="admin.php?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
    <tr>
     <td colspan="4" align="center"><input type="Submit" name="" value="Back"></td>
    </tr>
    </table>
   </form>
<?php
draw_footer();
}
elseif($submit == 'showpick')
{
	draw_header('Department Selection');
	draw_status_bar('Choose item to view', $message);
	$showpick='';
?>
	<center>
	<table border="0" cellspacing="5" cellpadding="5">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
	<tr>
	<td><b>Department</b></td>
	<td colspan=3><select name="item">
<?php 
	$query = 'SELECT id, name FROM department ORDER BY name';
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
		echo "<option value=\"$id\">$name</option>";
	}

	mysql_free_result ($result);
?>
	</select></td>
	<tr>
	<td colspan="2" align="center"><input type="Submit" name="submit" value="showitem">
	</form><p>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="Submit" name="submit" value="Cancel">
	</form>
	</td>
	</tr>
	</table>
	</center>
<?php 
draw_footer();
}
elseif($submit == 'modify')
{
	draw_header('Department Update');
	draw_status_bar('Update Department',$message);
?>
	<center>
	<table border="0" cellspacing="5" cellpadding="5">
	<tr>
	<form action="commitchange.php" method="POST" enctype="multipart/form-data">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM department where id='$item'";
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($id, $name) = mysql_fetch_row($result))
	{
?>		<tr>
		<td><input type="textbox" name="name" value="<?php echo $name; ?>"></td>
		<td><input type="hidden" name="id" value="<?php echo $id; ?>"></td>
		</tr>
<?php
	}
	mysql_free_result ($result);
?>
	<td>
	<input type="Submit" name="updatedepartment" value="Modify Department">
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
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
	draw_header('Department Selection');
	draw_status_bar('Modify Department',$message);
?>
	<center>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" enctype="multipart/form-data">
	<table border="0" cellspacing="5" cellpadding="5">
	<tr>
	<td><b>Department to modify:</b></td>
	<td colspan="3"><select name="item">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM department ORDER BY name";
	$result = mysql_db_query($GLOBALS['database'], $query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

	while(list($id, $name) = mysql_fetch_row($result))
	{
		echo '<option value="' . $id . '">' . $name . '</option>';
	}
	mysql_free_result ($result);
?>
	</td>
	</tr>
	<tr>
	<td colspan="4" align="right">
	<input type="Submit" name="submit" value="modify">
	</td>
	</form>
	<td colspan="4" align="center">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="Submit" name="submit" value="Cancel">
	</form>
	</td>
	</tr>
	</table>
	</center>
<?php
	draw_footer();
}
elseif ($submit == 'Cancel')
{
	header ('Location: admin.php?last_message=' . $last_message);
}
?>
