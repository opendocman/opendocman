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
 * EmailQueue class for asynchronous email processing
 * This class handles queuing and batch processing of email notifications
 * to improve upload performance by removing email sending from the critical path
 */
class EmailQueue
{
    private $connection;
    private $table_name;
    
    // Email status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRY = 'retry';
    
    // Maximum retry attempts
    const MAX_RETRIES = 3;
    
    public function __construct(PDO $pdo)
    {
        $this->connection = $pdo;
        $this->table_name = $GLOBALS['CONFIG']['db_prefix'] . 'email_queue';
        $this->createQueueTable();
    }
    
    /**
     * Create the email queue table if it doesn't exist
     */
    private function createQueueTable()
    {
        $query = "
            CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `to_email` varchar(255) NOT NULL,
                `to_name` varchar(255) DEFAULT NULL,
                `from_email` varchar(255) NOT NULL,
                `from_name` varchar(255) DEFAULT NULL,
                `subject` varchar(500) NOT NULL,
                `body` text NOT NULL,
                `headers` text DEFAULT NULL,
                `status` enum('pending','sent','failed','retry') DEFAULT 'pending',
                `retry_count` int(3) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `sent_at` timestamp NULL DEFAULT NULL,
                `error_message` text DEFAULT NULL,
                `priority` int(3) DEFAULT 5,
                `file_id` int(11) DEFAULT NULL,
                INDEX `idx_status` (`status`),
                INDEX `idx_created_at` (`created_at`),
                INDEX `idx_priority` (`priority`),
                INDEX `idx_file_id` (`file_id`),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        try {
            $this->connection->exec($query);
        } catch (PDOException $e) {
            error_log("EmailQueue: Error creating queue table: " . $e->getMessage());
        }
    }
    
    /**
     * Add an email to the queue
     * 
     * @param string $to_email Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $from_email Sender email address
     * @param string $to_name Recipient name (optional)
     * @param string $from_name Sender name (optional)
     * @param string $headers Additional headers (optional)
     * @param int $priority Priority level (1-10, lower is higher priority)
     * @param int $file_id Associated file ID (optional)
     * @return bool Success status
     */
    public function addToQueue($to_email, $subject, $body, $from_email, $to_name = null, $from_name = null, $headers = null, $priority = 5, $file_id = null)
    {
        // Validate required fields
        if (empty($to_email) || empty($subject) || empty($body) || empty($from_email)) {
            error_log("EmailQueue: Missing required fields for email queue entry");
            return false;
        }
        
        // Validate email addresses
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            error_log("EmailQueue: Invalid email address - To: {$to_email}, From: {$from_email}");
            return false;
        }
        
        $query = "
            INSERT INTO `{$this->table_name}` 
            (to_email, to_name, from_email, from_name, subject, body, headers, priority, file_id, status)
            VALUES 
            (:to_email, :to_name, :from_email, :from_name, :subject, :body, :headers, :priority, :file_id, :status)
        ";
        
        try {
            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute([
                ':to_email' => $to_email,
                ':to_name' => $to_name,
                ':from_email' => $from_email,
                ':from_name' => $from_name,
                ':subject' => $subject,
                ':body' => $body,
                ':headers' => $headers,
                ':priority' => $priority,
                ':file_id' => $file_id,
                ':status' => self::STATUS_PENDING
            ]);
            
            if ($result) {
                error_log("EmailQueue: Email queued successfully - To: {$to_email}, Subject: {$subject}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("EmailQueue: Error adding email to queue: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add multiple emails for file upload notification
     * 
     * @param array $recipients Array of user IDs or email addresses
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $from_email Sender email
     * @param string $from_name Sender name
     * @param int $file_id Associated file ID
     * @param int $priority Priority level
     * @return int Number of emails successfully queued
     */
    public function queueFileNotification($recipients, $subject, $body, $from_email, $from_name = null, $file_id = null, $priority = 5)
    {
        $queued_count = 0;
        
        if (!is_array($recipients) || empty($recipients)) {
            return $queued_count;
        }
        
        foreach ($recipients as $recipient) {
            $to_email = '';
            $to_name = '';
            
            // If recipient is a user ID, get their email and name
            if (is_numeric($recipient)) {
                try {
                    $user_obj = new User($recipient, $this->connection);
                    $to_email = $user_obj->getEmailAddress();
                    $full_name = $user_obj->getFullName();
                    $to_name = $full_name[0] . ' ' . $full_name[1];
                } catch (Exception $e) {
                    error_log("EmailQueue: Error getting user info for ID {$recipient}: " . $e->getMessage());
                    continue;
                }
            } else {
                // Assume it's an email address
                $to_email = $recipient;
            }
            
            if (!empty($to_email)) {
                if ($this->addToQueue($to_email, $subject, $body, $from_email, $to_name, $from_name, null, $priority, $file_id)) {
                    $queued_count++;
                }
            }
        }
        
        return $queued_count;
    }
    
    /**
     * Process pending emails in the queue
     * 
     * @param int $limit Maximum number of emails to process in this batch
     * @param int $max_execution_time Maximum time to spend processing (seconds)
     * @return array Processing statistics
     */
    public function processQueue($limit = 50, $max_execution_time = 30)
    {
        $start_time = time();
        $stats = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        // Skip processing if in demo mode
        if (isset($GLOBALS['CONFIG']['demo']) && $GLOBALS['CONFIG']['demo'] == 'True') {
            error_log("EmailQueue: Skipping email processing - demo mode enabled");
            return $stats;
        }
        
        // Get pending emails ordered by priority and creation time
        $query = "
            SELECT * FROM `{$this->table_name}` 
            WHERE status IN (:pending, :retry) 
            AND retry_count < :max_retries
            ORDER BY priority ASC, created_at ASC
            LIMIT :limit
        ";
        
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':pending', self::STATUS_PENDING);
            $stmt->bindValue(':retry', self::STATUS_RETRY);
            $stmt->bindValue(':max_retries', self::MAX_RETRIES, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($emails as $email) {
                $stats['processed']++;
                
                // Check execution time limit
                if ((time() - $start_time) >= $max_execution_time) {
                    error_log("EmailQueue: Reached execution time limit, stopping processing");
                    break;
                }
                
                if ($this->sendEmail($email)) {
                    $this->markAsSent($email['id']);
                    $stats['sent']++;
                } else {
                    $this->markAsFailed($email['id']);
                    $stats['failed']++;
                }
            }
            
        } catch (PDOException $e) {
            error_log("EmailQueue: Error processing queue: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Send an individual email
     * 
     * @param array $email Email data from queue
     * @return bool Success status
     */
    private function sendEmail($email)
    {
        try {
            // Prepare headers
            $headers = "From: ";
            if (!empty($email['from_name'])) {
                $headers .= $email['from_name'] . " <" . $email['from_email'] . ">";
            } else {
                $headers .= $email['from_email'];
            }
            $headers .= "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: OpenDocMan\r\n";
            
            // Add any additional headers
            if (!empty($email['headers'])) {
                $headers .= $email['headers'];
            }
            
            // Send the email
            $result = mail(
                $email['to_email'],
                $email['subject'],
                $email['body'],
                $headers
            );
            
            if ($result) {
                error_log("EmailQueue: Email sent successfully to {$email['to_email']}");
            } else {
                error_log("EmailQueue: Failed to send email to {$email['to_email']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("EmailQueue: Exception sending email to {$email['to_email']}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark an email as sent
     * 
     * @param int $email_id Email queue ID
     */
    private function markAsSent($email_id)
    {
        $query = "
            UPDATE `{$this->table_name}` 
            SET status = :status, sent_at = NOW(), error_message = NULL
            WHERE id = :id
        ";
        
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute([
                ':status' => self::STATUS_SENT,
                ':id' => $email_id
            ]);
        } catch (PDOException $e) {
            error_log("EmailQueue: Error marking email as sent: " . $e->getMessage());
        }
    }
    
    /**
     * Mark an email as failed and increment retry count
     * 
     * @param int $email_id Email queue ID
     * @param string $error_message Error message (optional)
     */
    private function markAsFailed($email_id, $error_message = null)
    {
        $query = "
            UPDATE `{$this->table_name}` 
            SET retry_count = retry_count + 1, 
                status = CASE 
                    WHEN retry_count + 1 >= :max_retries THEN :failed_status
                    ELSE :retry_status
                END,
                error_message = :error_message
            WHERE id = :id
        ";
        
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute([
                ':max_retries' => self::MAX_RETRIES,
                ':failed_status' => self::STATUS_FAILED,
                ':retry_status' => self::STATUS_RETRY,
                ':error_message' => $error_message,
                ':id' => $email_id
            ]);
        } catch (PDOException $e) {
            error_log("EmailQueue: Error marking email as failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get queue statistics
     * 
     * @return array Queue statistics
     */
    public function getStats()
    {
        $query = "
            SELECT 
                status,
                COUNT(*) as count
            FROM `{$this->table_name}`
            GROUP BY status
        ";
        
        $stats = [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
            'retry' => 0,
            'total' => 0
        ];
        
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $stats[$row['status']] = $row['count'];
                $stats['total'] += $row['count'];
            }
            
        } catch (PDOException $e) {
            error_log("EmailQueue: Error getting stats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Clean up old processed emails
     * 
     * @param int $days_old Remove emails older than this many days
     * @return int Number of emails removed
     */
    public function cleanup($days_old = 30)
    {
        $query = "
            DELETE FROM `{$this->table_name}` 
            WHERE status = :sent_status 
            AND sent_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ";
        
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute([
                ':sent_status' => self::STATUS_SENT,
                ':days' => $days_old
            ]);
            
            $deleted_count = $stmt->rowCount();
            error_log("EmailQueue: Cleaned up {$deleted_count} old email records");
            
            return $deleted_count;
            
        } catch (PDOException $e) {
            error_log("EmailQueue: Error during cleanup: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Process queue via cron job or scheduled task
     * This method can be called from a separate PHP script
     * 
     * @param int $batch_size Number of emails to process per batch
     * @param int $max_batches Maximum number of batches to process
     * @return array Overall processing statistics
     */
    public function cronProcess($batch_size = 100, $max_batches = 10)
    {
        $total_stats = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'batches' => 0
        ];
        
        for ($i = 0; $i < $max_batches; $i++) {
            $batch_stats = $this->processQueue($batch_size, 60); // 60 second timeout per batch
            
            $total_stats['processed'] += $batch_stats['processed'];
            $total_stats['sent'] += $batch_stats['sent'];
            $total_stats['failed'] += $batch_stats['failed'];
            $total_stats['skipped'] += $batch_stats['skipped'];
            $total_stats['batches']++;
            
            // If no emails were processed in this batch, stop
            if ($batch_stats['processed'] == 0) {
                break;
            }
        }
        
        // Cleanup old emails
        $this->cleanup();
        
        error_log("EmailQueue: Cron processing complete - " . json_encode($total_stats));
        
        return $total_stats;
    }
}