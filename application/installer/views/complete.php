<!DOCTYPE html>
<html>
<head>
    <title>OpenDocMan Installer - Complete</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3 style="color: green;">Installation Complete!</h3>
    <hr>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <?php if ($isDocker): ?>
            <p><strong>Docker Environment Detected</strong></p>
            <p><strong>Access URL:</strong> <a href="http://<?php echo htmlentities($hostname); ?>:<?php echo htmlentities($httpPort); ?>" target="_blank">http://<?php echo htmlentities($hostname); ?>:<?php echo htmlentities($httpPort); ?></a></p>
        <?php endif; ?>
        <p><strong>Login Credentials:</strong></p>
        <ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> <code><?php echo htmlentities($adminPassword); ?></code>
                <?php if ($isDocker): ?>
                    <em>(from environment)</em>
                <?php else: ?>
                    <em>(WRITE THIS DOWN!)</em>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    <p><a href="../index" class="button">Login to OpenDocMan</a></p>
    <p><a href="../settings?submit=update" class="button">Configure Site Settings</a></p>
</div>
</body>
</html>