<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Welcome</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Welcome to the OpenDocMan Installer</h3>
    <hr>
    <p>This wizard will guide you through setting up OpenDocMan.</p>
    <p>Before proceeding, please ensure:</p>
    <ul>
        <li>PHP <?php echo $requiredVersion ?? '7.4+'; ?> or higher is installed</li>
        <li>PDO MySQL extension is enabled</li>
        <li>A MySQL/MariaDB database has been created</li>
        <li>The <code>templates_c</code> directory is writable</li>
    </ul>
    <p><a href="?step=1" class="button">Next: Configure Database</a></p>
    <hr>
    <p><a href="../../docs/opendocman.txt" target="_blank">Installation Instructions (text)</a></p>
</div>
</body>
</html>