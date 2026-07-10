<!DOCTYPE html>
<html>
<head>
    <title>OpenDocMan Installer - Configuration Exists</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Configuration Already Exists</h3>
    <hr>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <p>Config file found at: <code><?php echo htmlentities($targetPath); ?></code></p>
    </div>
    <p><a href="/installer" class="button" style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Continue to Database Setup</a></p>
    <hr>
    <p>Need to change database credentials or other settings?</p>
    <p><a href="?step=1" class="button">Re-configure Settings</a></p>
</div>
</body>
</html>