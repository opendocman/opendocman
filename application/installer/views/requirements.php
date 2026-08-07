<?php /* view template — uses $results (CheckResult[]) and $allPassed (bool) */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Requirements Check</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
    <style>
        .severity-required { color: #c00; }
        .severity-recommended { color: #b85; }
        .warning-icon { color: #e68a00; }
        .pass-icon { color: green; }
        .fail-icon { color: red; }
    </style>
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
                <th>Severity</th>
            </tr>
            <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo htmlentities($result->name); ?></td>
                    <td><?php echo htmlentities($result->required); ?></td>
                    <td style="color: <?php echo $result->passed ? 'green' : ($result->severity === 'recommended' ? '#e68a00' : 'red'); ?>;">
                        <?php if ($result->passed): ?>
                            <span class="pass-icon">&#10003;</span>
                        <?php elseif ($result->severity === 'recommended'): ?>
                            <span class="warning-icon">&#9888;</span>
                        <?php else: ?>
                            <span class="fail-icon">&#10007;</span>
                        <?php endif; ?>
                        <?php echo htmlentities($result->actual); ?>
                    </td>
                    <td class="<?php echo $result->severity === 'recommended' ? 'severity-recommended' : 'severity-required'; ?>">
                        <?php echo $result->severity === 'recommended' ? 'Recommended' : 'Required'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <hr>

        <?php
        $hasRequiredFailures = false;
        foreach ($results as $result) {
            if ($result->severity === 'required' && !$result->passed) {
                $hasRequiredFailures = true;
                break;
            }
        }
        ?>

        <?php if ($allPassed): ?>
            <p style="color: green;"><strong>All requirements met!</strong></p>
            <p>
                <a href="?op=install" class="button">Proceed with Installation</a>
            </p>
        <?php elseif ($hasRequiredFailures): ?>
            <p style="color: red;"><strong>Please fix the required items above before proceeding.</strong></p>
            <p><a href="?op=requirements" class="button">Re-check</a></p>
        <?php else: ?>
            <p style="color: #e68a00;">
                <strong>All required checks pass, but some recommended items need attention.</strong>
                These are not blocking but may affect functionality.
            </p>
            <p>
                <a href="?op=install" class="button">Proceed Anyway</a>
                &nbsp;
                <a href="?op=requirements" class="button">Re-check</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>