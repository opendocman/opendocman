<!DOCTYPE html>
<html>
<head>
    <title>OpenDocMan Installer - Configuration Complete</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3 style="color: green;">Configuration Complete!</h3>
    <hr>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <p>Config file written to: <code><?php echo htmlentities($targetPath); ?></code></p>
    </div>
    <p>Your database configuration has been saved. Now you can proceed to set up the database.</p>
    <p><a href="/installer" class="button">Continue to Database Setup</a></p>
</div>
</body>
</html>