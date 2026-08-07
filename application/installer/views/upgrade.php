<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Upgrade Required</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Database Upgrade Required</h3>
    <hr>
    <table>
        <tr>
            <td><strong>Current Database Version:</strong></td>
            <td><?php echo htmlentities($currentVersion); ?></td>
        </tr>
        <tr>
            <td><strong>Required Database Version:</strong></td>
            <td><?php echo htmlentities($requiredVersion); ?></td>
        </tr>
    </table>
    <br>
    <p>Your database needs to be upgraded. Please backup your data first.</p>
    <a href="?op=backup-reminder" class="button">Begin Upgrade</a>
    <a href="?op=requirements" class="button">Check Requirements</a>
    <br><br>
    <hr>
    <h4 style="color: #cc0000;">Danger Zone</h4>
    <a href="?op=install&force_fresh=1" class="button" style="background-color: #cc0000; color: white;"
       onclick="return confirm('WARNING: This will DELETE ALL EXISTING DATA. This cannot be undone. Are you absolutely sure?')">
        Force Fresh Install - DELETE ALL DATA
    </a>
</div>
</body>
</html>