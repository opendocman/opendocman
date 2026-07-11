<!DOCTYPE html>
<html>
<head>
    <title>OpenDocMan Installer - Database Configuration</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Database Configuration</h3>
    <hr>
    <?php if (!empty($errorMessage)): ?>
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px; color: #721c24;">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="?step=2">
        <table>
            <tr>
                <td><label for="db_name">Database Name:</label></td>
                <td><input type="text" name="db_name" id="db_name" value="<?php echo htmlentities($defaults['db_name']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="db_user">Database Username:</label></td>
                <td><input type="text" name="db_user" id="db_user" value="<?php echo htmlentities($defaults['db_user']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="db_pass">Database Password:</label></td>
                <td><input type="password" name="db_pass" id="db_pass" value="<?php echo htmlentities($defaults['db_pass']); ?>"></td>
            </tr>
            <tr>
                <td><label for="db_host">Database Host:</label></td>
                <td><input type="text" name="db_host" id="db_host" value="<?php echo htmlentities($defaults['db_host']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="db_prefix">Table Prefix:</label></td>
                <td><input type="text" name="db_prefix" id="db_prefix" value="<?php echo htmlentities($defaults['db_prefix']); ?>"></td>
            </tr>
            <tr>
                <td><label for="admin_password">Admin Password:</label></td>
                <td><input type="text" name="admin_password" id="admin_password" value="<?php echo htmlentities($defaults['admin_password']); ?>" required minlength="5">
                    <br><small>At least 5 characters</small></td>
            </tr>
            <tr>
                <td><label for="data_dir">Data Directory:</label></td>
                <td><input type="text" name="data_dir" id="data_dir" value="<?php echo htmlentities($defaults['data_dir']); ?>" size="50"></td>
            </tr>
        </table>
        <hr>
        <input type="submit" value="Write Config &amp; Continue" class="button">
    </form>
</div>
</body>
</html>