<?php
/*
   department.php - Administer Departments
   Copyright (C) 2002-2011 Stephen Lawrence Jr.

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
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

// includes
include('odm-load.php');
// Make sure user is admin
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;
//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if(!$user_obj->isAdmin() == true)
{
    header('Location:' . $secureurl->encode('error.php?ec=4'));
    exit;
}

$secureurl = new phpsecureurl;
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);



/*
   Add A New Department
*/
if(isset($_GET['submit']) && $_GET['submit']=='add')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    draw_header(msg('area_add_new_department'));
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('area_add_new_department'), $_POST['last_message']);
    ?>

<center>
    <table border="0" cellspacing="5" cellpadding="5">
        <form action="commitchange.php" method="POST" enctype="multipart/form-data">
            <tr>
                <td><b><?php echo msg('department')?></b></td>
                <td colspan="3"><input name="department" type="text"></td>

            <input type="hidden" name="submit" value="Add Department">
            <td></td>
            <td align="center"><div class="buttons"><button class="positive" type="submit" name="submit" value="Add Department"><?php echo msg('button_add_department')?></buttons></td>
                        </form>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>">

                            <td align="center">
                                <div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button></div>
                            </td>
                        </form>
                        </tr>
                        </table>
                        </center>
<?php
    draw_footer();
}
elseif(isset($_POST['submit']) && $_POST['submit'] == 'Show Department')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    // query to show item
    draw_header(msg('area_department_information'));
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('area_department_information'), $_POST['last_message']);
    echo '<center>';
    //select name
    $query = "SELECT name,id FROM {$GLOBALS['CONFIG']['db_prefix']}department where id='$_POST[item]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    echo '<table name="main" cellspacing="15" border="0">';
    echo '<th>ID</th><th>' . msg('department') . '</th>';
    list($ldepartment) = mysql_fetch_row($result);
    echo '<tr><td>' . $_POST['item'] . '</td>';
    echo '<td>' . $ldepartment . '</td></tr>';
?>
                        <tr>
                            <td align="center" colspan="2"><b><?php echo msg('label_users_in_department')?></b></td>
                        </tr>
<?php
    // Display all users assigned to this department
    $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}department.id, {$GLOBALS['CONFIG']['db_prefix']}user.first_name, {$GLOBALS['CONFIG']['db_prefix']}user.last_name FROM {$GLOBALS['CONFIG']['db_prefix']}department, {$GLOBALS['CONFIG']['db_prefix']}user where {$GLOBALS['CONFIG']['db_prefix']}department.id='$_POST[item]' and {$GLOBALS['CONFIG']['db_prefix']}user.department='$_POST[item]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($lid, $lfirst_name, $llast_name) = mysql_fetch_row($result))
    {
        echo '<tr><td colspan="2">'.$lfirst_name.' '.$llast_name.'</td></tr>';
    }
?>
                        <form action="admin.php?last_message=<?php echo $_POST['last_message']; ?>" method="POST" enctype="multipart/form-data">
                            <tr>
                                <td colspan="4" align="center"><div class="buttons"><button class="regular" type="Submit" name="" value="Back"><?php echo msg('button_back')?></button></div></td>
                            </tr>
                            </table>
                        </form>
<?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'showpick')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    draw_header(msg('area_choose_department'));
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('area_choose_department'), $_POST['last_message']);
    $showpick='';
?>
                        <center>
                            <table border="0" cellspacing="5" cellpadding="5">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $_POST['last_message']; ?>" method="POST" enctype="multipart/form-data">
                                    <tr>
                                    <input type="hidden" name="state" value="<?php echo ($_GET['state']+1); ?>">
                                    <td><b><?php echo msg('department')?></b></td>
                                    <td colspan=3><select name="item">
    <?php
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($lid, $lname) = mysql_fetch_row($result))
    {
        echo '<option value="' . $lid . '">' . $lname . '</option>';
    }

    mysql_free_result ($result);
    ?>
                                        </select></td>
                                    <td colspan="" align="center"><div class="buttons"><button class="positive" type="submit" name="submit" value="Show Department"><?php echo msg('button_view_department')?></button></div>
                                </form>

                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                    <td>
                                        <div class="buttons"><button class="negative" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button></div>
                                </form>
                                </td>
                                </tr>
                            </table>
                        </center>
 <?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'delete')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    draw_header(msg('department') . ': ' . msg('label_delete'), $_POST['last_message']);
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('department') . ': ' . msg('label_delete'), $_POST['last_message']);

    $delete='';
    
    // Pull up a list of deparments excluding the one being deleted
    $reassign_list_query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE id != '{$_REQUEST['item']}' ORDER BY name";
    $reassign_list_result = mysql_query($reassign_list_query, $GLOBALS['connection']) or die ("Error in query: $reassign_list_query. " . mysql_error());

    // If the above statement returns less than 1 row they will need to create another category to re-assign to so display error
    if(mysql_num_rows($reassign_list_result) < 1)
    {
        echo msg('message_need_one_department');
        exit;
    }


    // query to show item
    echo '<center>';
    echo '<table border=0>';
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department where id={$_REQUEST['item']}";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in department lookup: $query. " . mysql_error());
    while(list($lid, $lname) = mysql_fetch_row($result))
    {
        echo '<tr><td>' .msg('label_id'). ' # :</td><td>' . $lid . '</td></tr>';
        echo '<tr><td>'.msg('label_name').' :</td><td>' . $lname . '</td></tr>';
    }


    {

    ?>
    <form action="commitchange.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $_REQUEST['item']; ?>">
        <tr>
            <td><?php echo msg('label_reassign_to');?>:<br />
                  <select name="assigned_id">
                      <?php
                            while(list($lid, $lname) = mysql_fetch_row($reassign_list_result))
                            {
                                echo '<option value="' . $lid . '">' . $lname . '</option>';
                            }
                            mysql_free_result ($reassign_list_result);
                            ?>
                    </select>
            </td>
        </tr>
        <tr>
            <td valign="top"><?php echo msg('message_are_you_sure_remove')?></td>
            <td colspan="4" align="center"><div class="buttons"><button class="positive" type="submit" name="deletedepartment" value="Yes"><?php echo msg('button_yes')?></button></div></td>
    </form>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $_POST['last_message']; ?>" method="POST" enctype="multipart/form-data">
        <td colspan="4" align="center"><div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button></div></td>
    </form>
</tr>
</TABLE>
    <?php
    }
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'deletepick')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    draw_header(msg('department') . ': ' . msg('label_delete'), $_POST['last_message']);
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('department') . ': ' . msg('label_delete'), $_POST['last_message']);
    ?>
<center>
    <table border="0" cellspacing="5" cellpadding="5">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
            <tr>
                <td><b><?php echo msg('department')?></b></td>
                <td colspan=3><select name="item">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
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

                <td></td>
                <td colspan="2" align="center">
                    <div class="buttons">
                        <button class="positive" type="submit" name="submit" value="delete"><?php echo msg('button_delete')?></button>
                        <button class="negative" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
        </form>
    </table>
</center>
    <?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'modify')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    $dept_obj = new Department($_REQUEST['item'], $GLOBALS['connection'], DB_NAME);
    draw_header(msg('area_update_department'));
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('area_update_department') .': ' . $dept_obj->getName(),$_POST['last_message']);
    ?>
                        <center>
                            <table border="0" cellspacing="5" cellpadding="5">
                                <form action="commitchange.php" method="POST" enctype="multipart/form-data">
                                    <tr>
                                            <?php
                                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department where id='$_REQUEST[item]'";
                                            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                                            while(list($lid, $lname) = mysql_fetch_row($result))
                                            {
                                                ?>
                                    <tr>
                                        <td>
                                                    <?php echo msg('department')?>:<input type="textbox" name="name" value="<?php echo $lname; ?>">
                                            <input type="hidden" name="id" value="<?php echo $lid; ?>">
                                        </td>

                                                <?php
                                            }
                                            mysql_free_result ($result);
                                            ?>
                                        <td>
                                            <div class="buttons"><button class="positive" type="Submit" name="submit" value="Update Department"><?php echo msg('button_save')?></button>
                                        </td>
                                </form>
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" >
                                    <td><div class="buttons"><button class="negative" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button></div></td>
                                </form>
                                </tr>
                            </table>
                        </center>
                            <?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'updatepick')
{
    if (!isset($_POST['last_message']))
    {
        $_POST['last_message']='';
    }
    draw_header(msg('area_choose_department'));
    draw_menu($_SESSION['uid']);
    draw_status_bar(msg('area_choose_department'),$_POST['last_message']);
    ?>
                        <center>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" enctype="multipart/form-data">
                                <INPUT type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
                                <table border="0" cellspacing="5" cellpadding="5">
                                    <tr>
                                        <td><b><?php echo msg('label_department_to_modify')?>:</b></td>
                                        <td colspan="3"><select name="item">
                                                    <?php
                                                    // query to get a list of departments
                                                    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                                                    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

                                                    while(list($lid, $lname) = mysql_fetch_row($result))
                                                    {
                                                        echo '<option value="' . $lid . '">' . $lname . '</option>';
                                                    }
                                                    mysql_free_result ($result);
                                                    ?>
                                        </td>
                                        <td>
                                            <div class="buttons"><button class="positive" type="submit" name="submit" value="modify"><?php echo msg('button_modify_department')?></button></div>
                                        </td>
                                        </form>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <td >
                                            <div class="buttons"><button class="negative" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button></div>
                                    </form>
                                    </td>
                                    </tr>
                                </table>
                        </center>
    <?php
    draw_footer();
}
elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Cancel')
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_action_cancelled'))));
}
else
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_nothing_to_do'))));
}


