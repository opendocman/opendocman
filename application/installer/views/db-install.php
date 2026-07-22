<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Fresh Install</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Database Fresh Install</h3>
    <hr>
    <p>Welcome to OpenDocMan! Your configuration file has been found, but the database has not been set up yet.</p>
    <p>This will create all required tables and default data for version <strong><?php echo htmlentities($requiredVersion ?? '1.4.0'); ?></strong>.</p>
    <p style="color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 5px;">
        <strong>Warning:</strong> If tables already exist with your prefix, they will be skipped. Use the "Force Fresh" option below to replace existing data.
    </p>
    <a href="?op=requirements" class="button">Begin Fresh Installation</a>
    <br><br>
    <hr>
    <h4 style="color: #cc0000;">Danger Zone</h4>
    <a href="?op=install&force_fresh=1" class="button" style="background-color: #cc0000; color: white;"
       onclick="return confirm('WARNING: This will DELETE ALL existing data. Are you sure?')">
        Force Fresh Install - DELETE ALL DATA
    </a>
</div>
</body>
</html>