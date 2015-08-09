<?php
/*
category.php - Administer Categories
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

// includes
include('odm-load.php');

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$user_obj = new User($_SESSION['uid'], $pdo);
// Check to see if user is admin
if (!$user_obj->isAdmin()) {
    header('Location:error.php?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (isset($_GET['submit']) && $_GET['submit'] == 'add') {
    draw_header(msg('area_add_new_category'), $last_message);
    ?>
    <form id="categoryAddForm" action="category.php?last_message=<?php echo $last_message;
    ?>" method="GET" enctype="multipart/form-data">
        <table border="0" cellspacing="5" cellpadding="5">
            <tr>
                <td><b><?php echo msg('category')?></b></td>
                <td colspan="3"><input name="category" type="text" class="required" maxlength="40"></td>
            <td>
                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="Add Category"><?php echo msg('button_add_category')?></button>
                </div>
            </td>
            <td>
                <div class="buttons">
                    <button class="negative cancel" type="submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                </div>
             </td>
            </tr>
        </table>
    </form>
     <script>
  $(document).ready(function(){
    $('#categoryAddForm').validate();
  });
  </script>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit']=='Add Category') {
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location:error.php?ec=4');
        exit;
    }

    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}category (name) VALUES (:category)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':category' => $_REQUEST['category']));

    // back to main page
    $last_message = urlencode(msg('message_category_successfully_added'));
    header('Location:admin.php?last_message=' . $last_message);
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'delete') {
    // If demo mode, don't allow them to update the demo account
    if ($GLOBALS['CONFIG']['demo'] == 'True') {
        draw_header(msg('area_delete_category'), $last_message);
        echo msg('message_sorry_demo_mode');
        draw_footer();
        exit;
    }

    draw_header(msg('area_delete_category'), $last_message);

    $item = (int) $_REQUEST['item'];

    // query to show item
    echo '<table border=0>';
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE id = :item";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':item' => $_REQUEST['item']));
    $result = $stmt->fetch();

    echo '<tr><td>' .msg('label_id'). ' # :</td><td>' . $result['id'] . '</td></tr>';
    echo '<tr><td>'.msg('label_name').' :</td><td>' . $result['name'] . '</td></tr>';
    ?>
    <form action="category.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $item;
    ?>">
        <tr>
            <td>
                <?php echo msg('label_reassign_to');
    ?>:
            </td>
            <td>
                  <select name="assigned_id">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE id != :item  ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':item' => $_REQUEST['item']));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
    }
    ?>
                    </select>
            </td>
        </tr>
        <tr>
            <td valign="top"><?php echo msg('message_are_you_sure_remove')?></td>
            <td align="center">
                <div class="buttons">
                    <button class="positive" type="submit" name="deletecategory" value="Yes"><?php echo msg('button_yes')?></button>
                </div>
                <div class="buttons">
                    <button class="negative cancel" type="submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                </div>
            </td>
    </form>
</tr>
</TABLE>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['deletecategory'])) {
    // Delete category
    // 
    // 
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location:error.php?ec=4');
        exit;
    }

    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}category where id=:id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_REQUEST[id]));

    // Set all old category_id's to the new re-assigned category
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET category = :assigned_id WHERE category = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':assigned_id' => $_REQUEST['assigned_id'],
        ':id' => $_REQUEST[id]
    ));

    // back to main page
    $last_message = urlencode(msg('message_category_successfully_deleted') . ' id:' . $_REQUEST['id']);
    header('Location: admin.php?last_message=' . $last_message);
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'deletepick') {
    $deletepick='';
    draw_header(msg('area_delete_category'). ' : ' .msg('choose'), $last_message);
    ?>
    <table border="0" cellspacing="5" cellpadding="5">
        <form action="<?php echo $_SERVER['PHP_SELF'];
    ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="state" value="<?php echo($_REQUEST['state']+1);
    ?>">
            <tr>
                <td><b><?php echo msg('category')?></b></td>
                <td colspan=3><select name="item">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        $str = '<option value="' . $row['id'] . '"';
        $str .= '>' . $row['name'] . '</option>';
        echo $str;
    }
    $deletepick='';
    ?>
                    </select></td>

                <td></td>
                <td colspan="2" align="center">
                    <div class="buttons">
                        <button class="positive" type="submit" name="submit" value="delete"><?php echo msg('button_delete')?></button>
                        <button class="negative cancel" type="submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
        </form>
    </table>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Show Category') {
    // query to show item
    draw_header(msg('area_view_category'), $last_message);
    $category_id = (int) $_REQUEST['item'];
        
    // Select name
    $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE id = :category_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':category_id' => $category_id
    ));
    $result = $stmt->fetchAll();

    echo('<table name="main" cellspacing="15" border="0">');
    foreach ($result as $row) {
        echo '<th>' . msg('label_name') . '</th><th>' . msg('label_id') . '</th>';
        echo '<tr>';
        echo '<td>' . $row['name'] . '</td>';
        echo '<td>' . $category_id . '</td>';
        echo '</tr>';
    }
    ?>
<form action="admin.php?last_message=<?php echo $last_message;
    ?>" method="POST" enctype="multipart/form-data">
    <tr>
        <td colspan="4" align="center"><div class="buttons"><button class="regular" type="submit" name="submit" value="Back"><?php echo msg('button_back')?></button></div></td>
    </tr>
</form>
</table>
<!-- ADD THE LIST OF FILES HERE -->
<?php
    echo msg('categoryviewpage_list_of_files_title') . '<br />';
    $query = "SELECT id, realname FROM `{$GLOBALS['CONFIG']['db_prefix']}data` WHERE category = :category_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':category_id' => $category_id
    ));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<a href="edit.php?id=' . $row['id'] . '&state=3">ID: ' . $row['id'] . ',' . $row['realname'] . '</a><br />';
    }
    
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'showpick') {
    draw_header(msg('area_view_category') . ' : ' . msg('choose'), $last_message);
    ?>
    <table border="0" cellspacing="5" cellpadding="5">
        <form action="<?php echo $_SERVER['PHP_SELF'];
    ?>?last_message=<?php echo $last_message;
    ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="state" value="<?php echo($_REQUEST['state']+1);
    ?>">
            <tr>
                <td><b><?php echo msg('category')?></b></td>
                <td colspan="3"><select name="item">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
    }
    ?>
                    </select></td>

                <td></td>
                <td colspan="3" align="center">
                    <div class="buttons">
                        <button class="positive" type="Submit" name="submit" value="Show Category"><?php echo msg('area_view_category')?></button>
                        <button class="negative cancel" type="Submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
        </form>
    </table>
</body>
</html>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Update') {
    draw_header(msg('area_update_category'), $last_message);
    ?>
<form id="updateCategoryForm" action="category.php?last_message=<?php echo $last_message;
    ?>" method="POST" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
        <tr>
<?php
    $item = (int)$_REQUEST['item'];
    // query to get a list of users
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category where id = :item";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':item' => $item
    ));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<tr>';
        echo '<td colspan="2">' . msg('category') . ': <input type="textbox" name="name" value="' . $row['name'] . '" class="required" maxlength="40"></td>';
        echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
    }
    ?>


            <td align="center">

                <div class="buttons">
                    <button class="positive" type="Submit" name="updatecategory" value="Modify Category"><?php echo msg('area_update_category')?></button>
                </div>
            </td>
            <td align="center">
                <div class="buttons">
                    <button class="negative cancel" type="Submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                </div>
            </td>
        </tr>
    </table>
 </form>
 <script>
  $(document).ready(function(){
    $('#updateCategoryForm').validate();
  });
  </script>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'updatepick') {
    draw_header(msg('area_update_category'). ': ' .msg('choose'), $last_message);
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF'];
    ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="state" value="<?php echo($_REQUEST['state']+1);
    ?>">
        <table border="0">
            <tr>
                <td><b><?php echo msg('choose')?> <?php echo msg('category')?>:</b></td>
                <td colspan="3"><select name="item">
                            <?php
                            // query to get a list of users
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
    }
    ?>
                </td>

                <td align="center">
                    <div class="buttons">
                        <button class="positive" type="submit" name="submit" value="Update"><?php echo msg('choose')?></button>
                    </div>
                </td>
                <td align="center">
                    <div class="buttons">
                        <button class="negative cancel" type="submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
    </form></TD>
</tr>
</table>
    <?php
    draw_footer();
} elseif (isset($_REQUEST['updatecategory'])) {
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location: error.php?ec=4');
        exit;
    }
    $id = (int) $_REQUEST['id'];

    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}category SET name = :name where id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':name' => $_REQUEST['name'],
        ':id' => $id
    ));

    // back to main page
    $last_message = urlencode(msg('message_category_successfully_updated') .' : ' . $_REQUEST['name']);
    header('Location: admin.php?last_message=' . $last_message);
} elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == 'Cancel') {
    $last_message=urlencode(msg('message_action_cancelled'));
    header('Location: admin.php?last_message=' . $last_message);
}
