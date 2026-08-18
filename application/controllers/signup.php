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

// (C) 2002-2007 Stephen Lawrence Jr., Jon Miner
// Adds files to the repository

use Aura\Html\Escaper as e;

$pdo = $GLOBALS['pdo'];

if ($GLOBALS['CONFIG']['allow_signup'] == 'True') {

    // Submitted so insert data now
    if (isset($_REQUEST['adduser'])) {
        // Validate CSRF token for signup operation
        if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
            header('Location: error?ec=1&last_message=' . urlencode('CSRF token validation failed'));
            exit;
        }
        // Check to make sure user does not already exist
        $query = "
          SELECT
            username
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}user
          WHERE
            username = :username
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();

        // If the above statement returns more than 0 rows, the user exists, so display error
        if ($stmt->rowCount() > 0) {
            echo msg('message_user_exists');
            exit;
        } else {
            $phonenumber = (!empty($_REQUEST['phonenumber']) ? $_REQUEST['phonenumber'] : '');
            // INSERT into user
            $query = "
              INSERT INTO
                {$GLOBALS['CONFIG']['db_prefix']}user
                (
                  username,
                  password,
                  department,
                  phone,
                  Email,
                  last_name,
                  first_name
                ) VALUES (
                  :username,
                  :password,
                  :department,
                  :phonenumber,
                  :email,
                  :last_name,
                  :first_name
                  )";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':username', $_POST['username']);
            $stmt->execute(array(
                ':username' => $_POST['username'],
                ':password' => PasswordHasher::hash($_POST['password']),
                ':department' => $_POST['department'],
                ':phonenumber' => $phonenumber,
                ':email' => $_POST['Email'],
                ':last_name' => $_POST['last_name'],
                ':first_name' => $_POST['first_name']
            ));

            // INSERT into admin
            $userid = $pdo->lastInsertId();

            // mail user telling him/her that his/her account has been created.
            echo msg ('message_account_created') . ' ' . $_POST['username'].'<br />';
            if($GLOBALS['CONFIG']['authen'] == 'mysql')
            {
                echo msg('message_account_created_password') . ': '. e::h($_REQUEST['password']) . PHP_EOL . PHP_EOL;
                echo '<br><a href="' . $GLOBALS['CONFIG']['base_url'] . '">' . msg('login'). '</a>';
                exit;
            }
        }
    }
    ?>
<?php
    draw_header(msg('signup'), $last_message);
    ob_start();
    if (is_readable("signup_header.html")) {
        include("signup_header.html");
    }
    ?>
                
            <font size=6>Sign Up</font>
        <br><script type="text/javascript" src="FormCheck.js"></script>


        <div class="container">
        <form name="add_user" action="signup" method="POST" enctype="multipart/form-data">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('label_last_name');
    ?></label>
            <div class="col-sm-9">
                <input name="last_name" type="text" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('label_first_name');
    ?></label>
            <div class="col-sm-9">
                <input name="first_name" type="text" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold"><?php echo msg('username');
    ?></label>
            <div class="col-sm-9">
                <input name="username" type="text" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold">Phone Number</label>
            <div class="col-sm-9">
                <input name="phonenumber" type="text" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">Example</label>
            <div class="col-sm-9">
                <p class="form-control-plaintext fw-bold">999 9999999</p>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold">E-mail Address</label>
            <div class="col-sm-9">
                <input name="Email" type="text" class="form-control">
            </div>
        </div>
        <?php
        // If mysqlauthentication, then ask for password
        if ($GLOBALS['CONFIG']['authen'] =='mysql') {
            $rand_password = makeRandomPassword();
            echo '<INPUT type="hidden" name="password" value="' . $rand_password . '">';
        }
    ?>
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label fw-bold">Department</label>
            <div class="col-sm-9">
                <select name="department" class="form-select">
        <?php	
        // query to get a list of departments
        $query = "
          SELECT
            id,
            name
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}department
          ORDER BY
            name
        ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        echo '<option value=' . e::h($row['id']) . '>' . e::h($row['name']) . '</option>';
    }

    ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <input type="Submit" name="adduser" class="btn btn-primary" onClick="return validatemod(add_user);" value="<?php echo msg('submit');
    ?>">
            </div>
        </div>
        </form>
        </div>
<?php
   if (is_readable("signup_footer.html")) {
       include("signup_footer.html");
   }
$content = ob_get_clean();
     $GLOBALS['smarty']->assign('content', $content);
     display_smarty_template('_content.tpl');
     draw_footer();
 }
?>
