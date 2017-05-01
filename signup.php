<?php
use Aura\Html\Escaper as e;

/*
add.php - adds files to the repository
Copyright (C) 2002-2007 Stephen Lawrence Jr., Jon Miner
Copyright (C) 2008-2014 Stephen Lawrence Jr.

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


// You can add signup_header.html and signup_footer.html files to display on this page automatically

include('odm-load.php');
if ($GLOBALS['CONFIG']['allow_signup'] == 'True') {

    // Submitted so insert data now
    if (isset($_REQUEST['adduser'])) {
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
                  md5(:password),
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
                ':password' => $_POST['password'],
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
        <html>
        <head><title>Sign Up</title></head>
        <body>
<?php
    if (is_readable("signup_header.html")) {
        include("signup_header.html");
    }
    ?>
                
            <font size=6>Sign Up</font>
        <br><script type="text/javascript" src="FormCheck.js"></script>


        <table border="0" cellspacing="5" cellpadding="5">
        <form name="add_user" action="signup.php" method="POST" enctype="multipart/form-data">
        <tr><td><b><?php echo msg('label_last_name');
    ?></b></td><td><input name="last_name" type="text"></td></tr>
        <tr><td><b><?php echo msg('label_first_name');
    ?></b></td><td><input name="first_name" type="text"></td></tr>
        <tr><td><b><?php echo msg('username');
    ?></b></td><td><input name="username" type="text"></td></tr>
        <tr>
        <td><b>Phone Number</b></td>
        <td>
        <input name="phonenumber" type="text">
        </td>
        </tr>
        <tr>
        <td><b>Example</b></td>
        <td><b>999 9999999</b></td>
        </tr>
        <tr>
        <td><b>E-mail Address</b></td>
        <td>
        <input name="Email" type="text">
        </td>
        </tr>
        <tr>
        <?php
        // If mysqlauthentication, then ask for password
        if ($GLOBALS['CONFIG']['authen'] =='mysql') {
            $rand_password = makeRandomPassword();
            echo '<INPUT type="hidden" name="password" value="' . $rand_password . '">';
        }
    ?>

        <tr>
        <td><b>Department</b></td>
        <td>
        <select name="department">
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
        </td>
        <tr>
        <td></td>
        <td columnspan=3 align="center"><input type="Submit" name="adduser" onClick="return validatemod(add_user);" value="<?php echo msg('submit');
    ?>">
        </form>
        </td>
        </tr>
        </table>
<?php
   if (is_readable("signup_footer.html")) {
       include("signup_footer.html");
   }
    ?>

        </body>
        </html>
        <?php

}
