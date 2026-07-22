<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Requirements Check</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>System Requirements Check</h3>
    <hr>
    <?php if (!isset($results)): ?>
        <p><a href="?op=requirements" class="button">Check Requirements</a></p>
    <?php else: ?>
        <table>
            <tr>
                <th>Requirement</th>
                <th>Required</th>
                <th>Status</th>
            </tr>
            <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo htmlentities($result['name']); ?></td>
                    <td><?php echo htmlentities($result['required'] ?? ''); ?></td>
                    <td style="color: <?php echo $result['passed'] ? 'green' : 'red'; ?>;">
                        <?php echo $result['passed'] ? '&#10003; ' : '&#10007; '; ?>
                        <?php echo htmlentities($result['actual']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <hr>
        <?php if ($allPassed): ?>
            <p style="color: green;"><strong>All requirements met!</strong></p>
            <a href="?op=install" class="button">Proceed with Installation</a>
        <?php else: ?>
            <p style="color: red;"><strong>Please fix the requirements above before proceeding.</strong></p>
            <a href="?op=requirements" class="button">Re-check</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>