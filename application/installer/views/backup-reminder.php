<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Backup Reminder</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Backup Reminder</h3>
    <hr>
    <div style="background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <h4 style="color: #856404;">Important: Please Backup Your Data!</h4>
        <p><strong>Current Version:</strong> <?php echo htmlentities($currentVersion); ?></p>
        <p><strong>Target Version:</strong> <?php echo htmlentities($requiredVersion); ?></p>
    </div>
    <p>Before proceeding with the database upgrade, please ensure you have:</p>
    <ol>
        <li>Backed up your MySQL database</li>
        <li>Backed up your document repository files</li>
        <li>Backed up your <code>application/configs/config.php</code></li>
    </ol>
    <p>You can use the following command to backup your database:</p>
    <pre style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
mysqldump -u [username] -p [database_name] > backup.sql
    </pre>
    <br>
    <form method="get" action="">
        <input type="hidden" name="op" value="upgrade">
        <input type="submit" value="I have a backup - Proceed with Upgrade" class="button"
               onclick="return confirm('Have you backed up your database and files? Click OK to proceed.');">
    </form>
</div>
</body>
</html>