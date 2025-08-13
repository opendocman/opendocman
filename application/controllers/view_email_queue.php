<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Email Queue Monitoring Interface
 *
 * This script provides a web interface to monitor the email queue status,
 * view pending/failed emails, and manually process the queue.
 */

 session_start();

 if (!isset($_SESSION['uid'])) {
     redirect_visitor();
 }

 $pdo = $GLOBALS['pdo'];
 $user_obj = new User($_SESSION['uid'], $pdo);

 if (!$user_obj->isAdmin()) {
     header('Location: error?ec=4');
     exit;
 }

// Include EmailQueue class
require_once 'models/EmailQueue.class.php';

$email_queue = new EmailQueue($pdo);
$table_name = $GLOBALS['CONFIG']['db_prefix'] . 'email_queue';

// Define app directory for cron setup instructions
$app_dir = dirname(__DIR__);

// Handle POST actions and redirect to prevent resubmission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $redirect_params = [];

    switch ($action) {
        case 'process_queue':
            if (isset($_POST['batch_size'])) {
                $batch_size = min(max((int)$_POST['batch_size'], 1), 100);
                $stats = $email_queue->processQueue($batch_size, 30);
                $message = str_replace(
                    array('{count}', '{sent}', '{failed}'),
                    array($stats['processed'], $stats['sent'], $stats['failed']),
                    msg('email_queue_processed_emails')
                );
                $redirect_params['message'] = urlencode($message);
            }
            break;

        case 'retry_failed':
            $query = "UPDATE `{$table_name}` SET status = 'processing', attempts = 0 WHERE status = 'failed'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            $message = str_replace('{count}', $affected, msg('email_queue_reset_failed_emails'));
            $redirect_params['message'] = urlencode($message);
            break;

        case 'delete_sent':
            $days = isset($_POST['days']) ? max((int)$_POST['days'], 1) : 30;
            $deleted = $email_queue->cleanup($days);
            $message = str_replace(
                array('{count}', '{days}'),
                array($deleted, $days),
                msg('email_queue_deleted_old_emails')
            );
            $redirect_params['message'] = urlencode($message);
            break;

        case 'delete_email':
            if (isset($_POST['email_id'])) {
                $email_id = (int)$_POST['email_id'];
                $query = "DELETE FROM `{$table_name}` WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':id' => $email_id]);
                $message = msg('email_queue_email_deleted');
                $redirect_params['message'] = urlencode($message);
            }
            break;
    }

    // Redirect to prevent resubmission
    $redirect_url = 'view_email_queue';
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . http_build_query($redirect_params);
    }
    header("Location: $redirect_url");
    exit;
}

// Handle GET parameters for messages
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';

// Get queue statistics
$stats = $email_queue->getStats();

// Get recent emails
$limit = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 10), 500) : 50;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clause = '';
$params = [];
if (!empty($status_filter) && in_array($status_filter, ['pending', 'sent', 'failed', 'retry'])) {
    $where_clause = 'WHERE status = :status';
    $params[':status'] = $status_filter;
}

$query = "
    SELECT id, to_email, subject, status, attempts, created_at, sent_at, error_message
    FROM `{$table_name}`
    {$where_clause}
    ORDER BY created_at DESC
    LIMIT :limit
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$emails = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo msg('email_queue_monitor_title'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { background: #e8f4f8; padding: 15px; border-radius: 5px; text-align: center; min-width: 100px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #333; }
        .stat-label { font-size: 12px; color: #666; }
        .actions { margin-bottom: 20px; }
        .action-form { display: inline-block; margin-right: 10px; }
        .btn { padding: 8px 15px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #a71e2a; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #d39e00; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .status-pending { color: #007cba; }
        .status-sent { color: #28a745; }
        .status-failed { color: #dc3545; }
        .status-retry { color: #ffc107; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .filters { margin-bottom: 15px; }
        .email-details { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .error-message { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: #666; }
    </style>
    <script>
        function confirmAction(message) {
            return confirm(message);
        }

        function toggleEmailDetails(id) {
            var row = document.getElementById('details-' + id);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</head>
<body>
    <div class="header">
        <h1><?php echo msg('email_queue_monitor'); ?></h1>
        <p><?php echo msg('email_queue_monitor_description'); ?></p>
        <p><a href="admin">&larr; <?php echo msg('email_queue_back_to_opendocman'); ?></a></p>
    </div>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['pending']; ?></div>
            <div class="stat-label"><?php echo msg('email_queue_pending'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['sent']; ?></div>
            <div class="stat-label"><?php echo msg('email_queue_sent'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['failed']; ?></div>
            <div class="stat-label"><?php echo msg('email_queue_failed'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['retry']; ?></div>
            <div class="stat-label"><?php echo msg('email_queue_retry'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label"><?php echo msg('email_queue_total'); ?></div>
        </div>
    </div>

    <div class="actions">
        <h3><?php echo msg('email_queue_queue_actions'); ?></h3>

        <form method="post" class="action-form">
            <input type="hidden" name="action" value="process_queue">
            <label><?php echo msg('email_queue_batch_size_label'); ?></label>
            <input type="number" name="batch_size" value="25" min="1" max="100" style="width: 60px;">
            <button type="submit" class="btn"><?php echo msg('email_queue_process'); ?></button>
        </form>

        <?php if ($stats['failed'] > 0): ?>
        <form method="post" class="action-form" onsubmit="return confirmAction('<?php echo msg('email_queue_confirm_retry'); ?>')">
            <input type="hidden" name="action" value="retry_failed">
            <button type="submit" class="btn btn-warning"><?php echo msg('email_queue_retry_failed'); ?></button>
        </form>
        <?php endif; ?>

        <form method="post" class="action-form" onsubmit="return confirmAction('<?php echo msg('email_queue_confirm_delete'); ?>')">
            <input type="hidden" name="action" value="delete_sent">
            <label><?php echo msg('email_queue_delete_sent_older_than_label'); ?></label>
            <input type="number" name="days" value="30" min="1" max="365" style="width: 60px;">
            <span><?php echo msg('email_queue_days'); ?></span>
            <button type="submit" class="btn btn-danger"><?php echo msg('email_queue_delete_old'); ?></button>
        </form>
    </div>

    <div class="filters">
        <h3><?php echo msg('email_queue_email_list'); ?></h3>
        <form method="get" style="margin-bottom: 10px;">
            <label><?php echo msg('email_queue_filter_by_status'); ?></label>
            <select name="status" onchange="this.form.submit()">
                <option value=""><?php echo msg('email_queue_all_statuses'); ?></option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>><?php echo msg('email_queue_pending'); ?></option>
                <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>><?php echo msg('email_queue_sent'); ?></option>
                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>><?php echo msg('email_queue_failed'); ?></option>
                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>><?php echo msg('email_queue_retry'); ?></option>
            </select>

            <label style="margin-left: 20px;"><?php echo msg('email_queue_limit'); ?>:</label>
            <select name="limit" onchange="this.form.submit()">
                <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
            </select>
        </form>
    </div>

    <?php if (empty($emails)): ?>
        <p><?php echo msg('email_queue_no_emails'); ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?php echo msg('email_queue_id'); ?></th>
                    <th><?php echo msg('email_queue_to'); ?></th>
                    <th><?php echo msg('email_queue_subject'); ?></th>
                    <th><?php echo msg('email_queue_status_column'); ?></th>
                    <th><?php echo msg('email_queue_retries'); ?></th>
                    <th><?php echo msg('email_queue_created'); ?></th>
                    <th><?php echo msg('email_queue_sent_time'); ?></th>
                    <th><?php echo msg('email_queue_file_id'); ?></th>
                    <th><?php echo msg('email_queue_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><?php echo $email['id']; ?></td>
                        <td class="email-details" title="<?php echo htmlspecialchars($email['to_email']); ?>">
                            <?php echo htmlspecialchars($email['to_email']); ?>
                        </td>
                        <td class="email-details" title="<?php echo htmlspecialchars($email['subject']); ?>">
                            <?php echo htmlspecialchars($email['subject']); ?>
                        </td>
                        <td class="status-<?php echo $email['status']; ?>"><?php echo ucfirst($email['status']); ?></td>
                        <td><?php echo $email['attempts']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($email['created_at'])); ?></td>
                        <td><?php echo $email['sent_at'] ? date('Y-m-d H:i', strtotime($email['sent_at'])) : '-'; ?></td>
                        <td>
                            <button onclick="toggleEmailDetails(<?php echo $email['id']; ?>)" class="btn" style="font-size: 11px; padding: 4px 8px;"><?php echo msg('email_queue_details'); ?></button>
                            <?php if ($email['status'] !== 'sent'): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirmAction('<?php echo msg('email_queue_confirm_delete_single'); ?>')">
                                    <input type="hidden" name="action" value="delete_email">
                                    <input type="hidden" name="email_id" value="<?php echo $email['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;"><?php echo msg('email_queue_delete'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($email['error_message']): ?>
                        <tr id="details-<?php echo $email['id']; ?>" style="display: none;">
                            <td colspan="9">
                                <strong><?php echo msg('email_queue_error'); ?>:</strong> <?php echo htmlspecialchars($email['error_message']); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3><?php echo msg('email_queue_cron_setup_title'); ?></h3>
        <p><?php echo msg('email_queue_cron_description'); ?></p>
        <pre style="background: #343a40; color: #fff; padding: 10px; border-radius: 3px;">
<?php echo msg('email_queue_cron_every_minute'); ?>

* * * * * /usr/bin/php <?php echo $app_dir; ?>/cron/process_email_queue.php

<?php echo msg('email_queue_cron_every_5_minutes'); ?>

*/5 * * * * /usr/bin/php <?php echo $app_dir; ?>/cron/process_email_queue.php</pre>

        <p><strong><?php echo msg('email_queue_manual_processing_title'); ?></strong></p>
        <pre style="background: #343a40; color: #fff; padding: 10px; border-radius: 3px;">
php <?php echo $app_dir; ?>/cron/process_email_queue.php --verbose</pre>
    </div>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p><?php echo msg('email_queue_last_updated'); ?>: <?php echo date('Y-m-d H:i:s'); ?> |
        <?php echo msg('email_queue_auto_refresh'); ?> |
        <a href="?<?php echo http_build_query(array_merge($_GET, ['t' => time()])); ?>"><?php echo msg('email_queue_refresh_now'); ?></a></p>
    </div>
</body>
</html>
