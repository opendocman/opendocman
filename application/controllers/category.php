<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Administer Categories

use Aura\Html\Escaper as e;
use Aura\Html\Escaper\AttrEscaper as a;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$user_obj = new User($_SESSION['uid'], $pdo);
// Check to see if user is admin
if (!$user_obj->isAdmin()) {
    header('Location:error?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

if (isset($_GET['submit']) && $_GET['submit'] == 'add') {
    draw_header(msg('area_add_new_category'), $last_message);
    ob_start();
    ?>
    <form id="categoryAddForm" action="category" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold"><?php echo msg('category')?></label>
            </div>
            <div class="col-auto">
                <input name="category" type="text" class="form-control required" maxlength="40">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="Submit" name="submit" value="Add Category"><?php echo msg('button_add_category')?></button>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
            </div>
        </div>
    </form>
     <script>
  $(document).ready(function(){
    $('#categoryAddForm').validate();
  });
  </script>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit']=='Add Category') {
    // Validate CSRF token for Add Category operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location:error?ec=4');
        exit;
    }

    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}category (name) VALUES (:category)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':category' => $_REQUEST['category']));

    // back to main page
    $last_message = urlencode(msg('message_category_successfully_added'));
    header('Location:admin?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'delete') {
    // If demo mode, don't allow them to update the demo account
    if ($GLOBALS['CONFIG']['demo'] == 'True') {
        draw_header(msg('area_delete_category'), $last_message);
        ob_start();
        echo msg('message_sorry_demo_mode');
        $content = ob_get_clean();
        $GLOBALS['smarty']->assign('content', $content);
        display_smarty_template('_content.tpl');
        draw_footer();
        exit;
    }

    draw_header(msg('area_delete_category'), $last_message);
    ob_start();

    $item = (int) $_REQUEST['item'];

    // query to show item
    echo '<div class="row g-3 mb-2">';
    echo '<div class="col-sm-1"><strong>' .msg('label_id'). ' # :</strong></div>';
    echo '<div class="col-sm-11">' . e::h($result['id']) . '</div>';
    echo '</div>';
    echo '<div class="row g-3 mb-2">';
    echo '<div class="col-sm-1"><strong>'.msg('label_name').' :</strong></div>';
    echo '<div class="col-sm-11">' . e::h($result['name']) . '</div>';
    echo '</div>';
    ?>
    <form action="category" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <input type="hidden" name="id" value="<?php echo e::h($item);
    ?>">
        <div class="row g-3 align-items-center mb-3">
            <div class="col-auto">
                <label class="col-form-label"><?php echo msg('label_reassign_to');
    ?>:</label>
            </div>
            <div class="col-auto">
                  <select name="assigned_id" class="form-select">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE id != :item  ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':item' => $_REQUEST['item']));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . e::h($row['id']) . '">' . e::h($row['name']) . '</option>';
    }
    ?>
                    </select>
            </div>
        </div>
        <div class="row g-3 align-items-center">
            <div class="col-auto"><?php echo msg('message_are_you_sure_remove')?></div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit" name="deletecategory" value="Yes"><?php echo msg('button_yes')?></button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
            </div>
        </div>
    </form>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['deletecategory'])) {
    // Validate CSRF token for Delete Category operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    // Delete category
    //
    //
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location:error?ec=4');
        exit;
    }

    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}category where id=:id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $_REQUEST['id']));

    // Set all old category_id's to the new re-assigned category
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET category = :assigned_id WHERE category = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':assigned_id' => $_REQUEST['assigned_id'],
        ':id' => $_REQUEST['id']
    ));

    // back to main page
    $last_message = msg('message_category_successfully_deleted') . ' id:' . $_REQUEST['id'];
    header('Location: admin?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'deletepick') {
    $deletepick='';
    draw_header(msg('area_delete_category'). ' : ' .msg('choose'), $last_message);
    ob_start();
    ?>
    <form action="category" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <input type="hidden" name="state" value="<?php echo(e::h($_REQUEST['state']+1));
    ?>">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold"><?php echo msg('category')?></label>
            </div>
            <div class="col-auto">
                <select name="item" class="form-select">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        $str = '<option value="' . e::h($row['id']) . '"';
        $str .= '>' . e::h($row['name']) . '</option>';
        echo $str;
    }
    $deletepick='';
    ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit" name="submit" value="delete"><?php echo msg('button_delete')?></button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
            </div>
        </div>
    </form>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Show Category') {
    // query to show item
    draw_header(msg('area_view_category'), $last_message);
    ob_start();
    $category_id = (int) $_REQUEST['item'];

    // Select name
    $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE id = :category_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':category_id' => $category_id
    ));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<div class="row g-3 border-bottom pb-2 fw-bold">';
        echo '<div class="col-sm-6">' . msg('label_name') . '</div>';
        echo '<div class="col-sm-6">' . msg('label_id') . '</div>';
        echo '</div>';
        echo '<div class="row g-3 mb-2">';
        echo '<div class="col-sm-6">' . e::h($row['name']) . '</div>';
        echo '<div class="col-sm-6">' . e::h($category_id) . '</div>';
        echo '</div>';
    }
    ?>
<form action="admin" method="POST" enctype="multipart/form-data">
    <?php echo $GLOBALS['csrf']->getTokenField(); ?>
    <div class="row g-3">
        <div class="col-12"><button class="btn btn-secondary" type="submit" name="submit" value="Back"><?php echo msg('button_back')?></button></div>
    </div>
</form>
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
        echo '<a href="edit?id=' . e::h($row['id']) . '&state=3">ID: ' . e::h($row['id']) . ',' . e::h($row['realname']) . '</a><br />';
    }

    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'showpick') {
    draw_header(msg('area_view_category') . ' : ' . msg('choose'), $last_message);
    ob_start();
    ?>
    <form action="category" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <input type="hidden" name="state" value="<?php echo(e::h($_REQUEST['state']+1));
    ?>">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold"><?php echo msg('category')?></label>
            </div>
            <div class="col-auto">
                <select name="item" class="form-select">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . e::h($row['id']) . '">' . e::h($row['name']) . '</option>';
    }
    ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="Submit" name="submit" value="Show Category"><?php echo msg('area_view_category')?></button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
            </div>
        </div>
    </form>
</body>
</html>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Update') {
    draw_header(msg('area_update_category'), $last_message);
    ob_start();
    ?>
<form id="updateCategoryForm" action="category" method="POST" enctype="multipart/form-data">
    <?php echo $GLOBALS['csrf']->getTokenField(); ?>
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
        echo '<div class="row g-3 align-items-center mb-3">';
        echo '<div class="col-auto"><label class="col-form-label fw-bold">' . msg('category') . ':</label></div>';
        echo '<div class="col-auto"><input type="text" name="name" value="' . e::h($row['name']) . '" class="form-control required" maxlength="40"></div>';
        echo '<input type="hidden" name="id" value="' . e::h($row['id']) . '">';
        echo '</div>';
    }
    ?>


            <div class="row g-3">
                <div class="col-auto">
                    <button class="btn btn-primary" type="Submit" name="updatecategory" value="Modify Category"><?php echo msg('area_update_category')?></button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
                </div>
            </div>
 </form>
 <script>
  $(document).ready(function(){
    $('#updateCategoryForm').validate();
  });
  </script>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'updatepick') {
    draw_header(msg('area_update_category'). ': ' .msg('choose'), $last_message);
    ob_start();
    ?>
    <form action="category" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <input type="hidden" name="state" value="<?php echo(e::h($_REQUEST['state']+1));
    ?>">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold"><?php echo msg('choose')?> <?php echo msg('category')?>:</label>
            </div>
            <div class="col-auto">
                <select name="item" class="form-select">
                            <?php
                            // query to get a list of users
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value="' . e::h($row['id']) . '">' . e::h($row['name']) . '</option>';
    }
    ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit" name="submit" value="Update"><?php echo msg('choose')?></button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'"><?php echo msg('button_cancel')?></button>
            </div>
        </div>
    </form>
    <?php
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} elseif (isset($_REQUEST['updatecategory'])) {
    // Validate CSRF token for Update Category operation
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
        exit;
    }
    // Make sure they are an admin
    if (!$user_obj->isAdmin()) {
        header('Location: error?ec=4');
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
    $last_message = msg('message_category_successfully_updated') .' : ' . $_REQUEST['name'];
    header('Location: admin?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == 'Cancel') {
    $last_message = msg('message_action_cancelled');
    header('Location: admin?last_message=' . urlencode($last_message));
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'list_json') {
    if (!$user_obj->isAdmin()) {
        header('Content-Type: application/json');
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    $categories = Category::getAllCategories($pdo);
    $categories = array_map(function ($cat) {
        return ['id' => (int)$cat['id'], 'name' => $cat['name']];
    }, $categories);
    echo json_encode($categories);
    exit;
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'add_json') {
    if (!$user_obj->isAdmin()) {
        header('Content-Type: application/json');
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Content-Type: application/json');
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'CSRF validation failed']);
        exit;
    }
    $name = trim($_REQUEST['category'] ?? '');
    if ($name === '') {
        header('Content-Type: application/json');
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(['error' => 'Category name is required']);
        exit;
    }
    $check = $pdo->prepare("SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}category WHERE name = :name");
    $check->execute([':name' => $name]);
    if ($check->fetch()) {
        header('Content-Type: application/json');
        header('HTTP/1.0 409 Conflict');
        echo json_encode(['error' => 'Category already exists']);
        exit;
    }
    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}category (name) VALUES (:name)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':name' => $name]);
    $newId = (int)$pdo->lastInsertId();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'id' => $newId, 'name' => $name]);
    exit;
}
