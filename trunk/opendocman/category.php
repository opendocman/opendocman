<?php
/*
category.php - Administer Categories
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

// check for valid session 
session_start();
if (!isset($_SESSION['uid']))
{
	header('Location:index.php?redirection=' . urlencode($_SERVER['PHP_SELF'] . '?' . $HTTP_SERVER_VARS['QUERY_STRING']));
	exit;
}
// includes
include('config.php');
$secureurl = new phpsecureurl;
if(!$user_obj->isAdmin())        
{
    header('Location:' . $secureurl->encode('error.php?ec=4'));
    exit;
}

if(isset($_REQUEST['submit']) and $_REQUEST['submit'] != 'Cancel')
{
    draw_menu($_SESSION['uid']);
}

if(isset($_GET['submit']) && $_GET['submit'] == 'add')
{
    // Check to see if user is admin
    if (!isset($_REQUEST['last_message']))
    {
        $_REQUEST['last_message']='';
    }
    draw_header($GLOBALS['lang']['area_add_new_category']);
    draw_status_bar($GLOBALS['lang']['area_add_new_category'], $_REQUEST['last_message']);
    // Check to see if user is admin
    ?>
        <center>
        <table border="0" cellspacing="5" cellpadding="5">
        <form action="commitchange.php?last_message=<?php $_REQUEST['last_message']; ?>" method="GET" enctype="multipart/form-data">
	<tr>
	    <td><b><?php echo $GLOBALS['lang']['label_category']; ?></b></td>
	    <td colspan="3"><input name="category" type="text"></td>
	        <input type="hidden" name="submit" value="Add Category">
	</tr>
	<tr>
            <td></td>
            <td>
                <input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_add_category']; ?>">
            </td>
        </tr>
</form>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <tr>
            <td></td>
            <td>
                <input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>">
            </td>
        </tr>
</form>
</table>
</center>
<?php
	draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == $GLOBALS['lang']['button_delete'])
{
        // If demo mode, don't allow them to update the demo account
        if (@$GLOBALS['CONFIG']['demo'] == 'true')
        {
                @draw_status_bar($GLOBALS['lang']['area_delete_category'] ,$_POST['last_message']);
                echo $GLOBALS['lang']['message_sorry_demo_mode'];
                draw_footer();
                exit;
        }
	$delete='';
        if (!isset($_REQUEST['last_message']))
        {       
                $_REQUEST['last_message']='';
        }
	draw_header($GLOBALS['lang']['area_delete_category']);
	draw_status_bar($GLOBALS['lang']['area_delete_category'], $_REQUEST['last_message']);
	// query to show item
	echo '<center>'; 
	echo '<table border=0>';
	$query = 'SELECT id, name FROM ' . $GLOBALS['CONFIG']['table_prefix'] . 'category where id="' . $_REQUEST['item'] . '"';
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($lid, $lname) = mysql_fetch_row($result))
	{
		echo '<tr><td>Id # :</td><td>' . $lid . '</td></tr>';
		echo '<tr><td>Name :</td><td>' . $lname . '</td></tr>';
	}
?>
	<TABLE name="delete_table">
	<form action="commitchange.php" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?php echo $_REQUEST['item']; ?>">
		<tr>
			<td valign="top"><?php echo $GLOBALS['lang']['message_are_you_sure_remove']; ?></td>
			<td colspan="4" align="center"><input type="Submit" name="deletecategory" value="<?php echo $GLOBALS['lang']['button_yes']; ?>"></td>
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $_REQUEST['last_message']; ?>" method="POST" enctype="multipart/form-data">
		<td colspan="4" align="center"><input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>"></td>
	</form>
		</tr>
	</TABLE>
<?php
	draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'deletepick')
{
        if (!isset($_REQUEST['last_message']))
        {
                $_REQUEST['last_message']='';
        }
	$deletepick='';
	draw_header($GLOBALS['lang']['area_delete_category']);
	draw_status_bar($GLOBALS['lang']['area_delete_category'], $_REQUEST['last_message']);
?>
	<center>
	<table border="0" cellspacing="5" cellpadding="5">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
			<tr>
				<td><b><?php echo $GLOBALS['lang']['label_category']; ?></b></td>
				<td colspan=3><select name="item">
<?php
	$query = 'SELECT id, name FROM '  . $GLOBALS['CONFIG']['table_prefix'] . 'category ORDER BY name';
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($lid, $lname) = mysql_fetch_row($result))
	{
		$str = '<option value="' . $lid . '"';
		$str .= '>' . $lname . '</option>';
		echo $str;
	}
	mysql_free_result ($result);
	$deletepick='';
?>
	</select></td>
	<tr>
		<td></td>
		<td colspan="2" align="center">
		<input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_delete']; ?>">
		<input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>">
		</td>
	</tr>
			</form>
		</table>
		</center>
<?php
	draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == $GLOBALS['lang']['button_display_category'])
{
	// query to show item
	draw_header($GLOBALS['lang']['area_display_category']);
        if (!isset($_REQUEST['last_message']))
        {
                $_REQUEST['last_message'] = '';
        }
	draw_status_bar($GLOBALS['lang']['area_display_category'], $_REQUEST['last_message']);
	echo '<center>';
	// Select name
	$query = "SELECT " . $GLOBALS['CONFIG']['table_prefix'] . "data.realname, " . $GLOBALS['CONFIG']['table_prefix'] . "user.username, " . $GLOBALS['CONFIG']['table_prefix'] . "department.name, " . $GLOBALS['CONFIG']['table_prefix'] . "category.name FROM " . $GLOBALS['CONFIG']['table_prefix'] . "data," . $GLOBALS['CONFIG']['table_prefix'] . "user," . $GLOBALS['CONFIG']['table_prefix'] . "department," . $GLOBALS['CONFIG']['table_prefix'] . "category where " . $GLOBALS['CONFIG']['table_prefix'] . "data.category='$_REQUEST[item]' AND " . $GLOBALS['CONFIG']['table_prefix'] . "user.id = " . $GLOBALS['CONFIG']['table_prefix'] . "data.owner AND " . $GLOBALS['CONFIG']['table_prefix'] . "department.id = " . $GLOBALS['CONFIG']['table_prefix'] . "data.department AND " . $GLOBALS['CONFIG']['table_prefix'] . "data.category = " . $GLOBALS['CONFIG']['table_prefix'] . "category.id";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	echo('<table name="main" cellspacing="15" border="0">');
	echo '<th>' . $GLOBALS['lang']['label_name'] . '</th><th>' . $GLOBALS['lang']['label_user'] . '</th><th>' . $GLOBALS['lang']['label_department'] . '</th><th>' . $GLOBALS['lang']['label_category'] . '</th>';
	while (list($lrealname, $lusername, $ldepartment, $lcategory) = mysql_fetch_row($result))
        {
	echo '<tr>';
	echo '<td>' . $lrealname . '</td>';	
	echo '<td>' . $lusername . '</td>';	
	echo '<td>' . $ldepartment . '</td>';	
	echo '<td>' . $lcategory . '</td>';	
	echo '</tr>';	
        }
?>
	<form action="admin.php?last_message=<?php echo $_REQUEST['last_message']; ?>" method="POST" enctype="multipart/form-data">
		<tr>
			<td colspan="4" align="center"><input type="Submit" name="" value="<?php echo $GLOBALS['lang']['button_back']; ?>"></td>
		</tr>
	</form>
	</table>
<?php
	
	draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'showpick')
{
                if (!isset($_REQUEST['last_message']))
                {
                        $_REQUEST['last_message']='';
                }       
		draw_header($GLOBALS['lang']['area_display_category']);
		draw_status_bar($GLOBALS['lang']['area_display_category'], $_REQUEST['last_message']);
		$showpick='';
?>
			<center>
			<table border="0" cellspacing="5" cellpadding="5">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $_REQUEST['last_message']; ?>" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
			<tr>
			<td><b><?php echo $GLOBALS['lang']['label_category']; ?></b></td>
			<td colspan="3"><select name="item">
<?php
			$query = 'SELECT id, name FROM '  . $GLOBALS['CONFIG']['table_prefix'] . 'category ORDER BY name';
			$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
		while(list($lid, $lname) = mysql_fetch_row($result))
		{
				echo '<option value="' . $lid . '">' . $lname . '</option>';
		}
		mysql_free_result ($result);
?>
		</select></td>
		<tr>
		<td></td>
		<td colspan="3" align="center">
		<input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_display_category']; ?>">
		<input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>">
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
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == $GLOBALS['lang']['button_update'])
{
        if (!isset($_REQUEST['last_message']))
        {
                $_REQUEST['last_message']='';
        }
	draw_header($GLOBALS['lang']['area_update_category']);
	draw_status_bar($GLOBALS['lang']['area_update_category'], $_REQUEST['last_message']);
?>
	<center>
		<table border="0" cellspacing="5" cellpadding="5">
			<tr>
		<form action="commitchange.php?last_message=<?php echo $_REQUEST['last_message']; ?>" method="POST" enctype="multipart/form-data">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM " . $GLOBALS['CONFIG']['table_prefix'] . "category where id='$_REQUEST[item]'";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($lid, $lname) = mysql_fetch_row($result))
	{
		echo '<tr>';
		echo '<td><input type="textbox" name="name" value="' . $lname . '"></td>';
		echo '<td><input type="hidden" name="id" value="' . $lid . '"></td>';
		echo '</tr>';
	}
	mysql_free_result ($result);
?>
		<td>
			<input type="Submit" name="updatecategory" value="<?php echo $GLOBALS['lang']['button_modify_category']; ?>">
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $_REQUEST['last_message']; ?>">
		<input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>">
	</form>
			</td>
		</tr>
	</table>
</center>
<?php
draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'updatepick')
{
        if (!isset($_REQUEST['last_message']))
        {
                $_REQUEST['last_message']='';
        }
	draw_header($GLOBALS['lang']['area_update_category']);
	draw_status_bar($GLOBALS['lang']['area_update_category'],$_REQUEST['last_message']);
?>
	<center>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
			<table border="0">
				<tr>
				<td><b><?php echo $GLOBALS['lang']['label_category']; ?></b></td>
				<td colspan="3"><select name="item">
<?php
	// query to get a list of users
	$query = "SELECT id, name FROM " . $GLOBALS['CONFIG']['table_prefix'] . "category ORDER BY name";
	$result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
	while(list($lid, $lname) = mysql_fetch_row($result))
	{
		echo '<option value="' . $lid . '">' . $lname . '</option>';
	}
	mysql_free_result ($result);
?>
		</td>
	</tr>
	<tr>
        <td></td>
        <td colspan="3" align="center">
        <input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_update']; ?>">
        <input type="Submit" name="submit" value="<?php echo $GLOBALS['lang']['button_cancel']; ?>">
        </td>
        </tr>
	</form></TD>
	</tr>
	</table>
	</center>
<?php
	draw_footer();
}
elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == $GLOBALS['lang']['button_cancel'])
{
		$_REQUEST['last_message']=urlencode('Action canceled');
		header ('Location: admin.php?last_message=' . $_REQUEST['last_message']);
}
?>
