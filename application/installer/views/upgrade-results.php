<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Upgrade Results</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>Upgrade Complete</h3>
    <hr>
    <?php if ($hasError): ?>
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24;">
            <h4>Some migrations encountered errors</h4>
        </div>
    <?php endif; ?>
    <table>
        <tr>
            <th>Version</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
        <?php foreach ($results as $result): ?>
            <tr>
                <td><?php echo htmlentities($result['version']); ?></td>
                <td style="color: <?php echo $result['status'] === 'success' ? 'green' : 'red'; ?>;">
                    <?php echo $result['status'] === 'success' ? '&#10003; Applied' : '&#10007; Failed'; ?>
                </td>
                <td><?php echo htmlentities($result['message']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <?php if ($hasError): ?>
        <p style="color: red;">Please check the errors above and try again.</p>
    <?php else: ?>
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">
            <h3 style="color: #155724;">&#10003; Database Upgrade Complete!</h3>
            <p>Your database has been successfully upgraded.</p>
        </div>
        <p><a href="../index" class="button" style="background-color: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px;">Continue to Application</a></p>
    <?php endif; ?>
</div>
</body>
</html>