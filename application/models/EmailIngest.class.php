<?php

require_once __DIR__ . '/EmailMessage.class.php';

class EmailIngest
{
    private PDO $pdo;
    private array $config;
    private $documentCreator;

    public function __construct(PDO $pdo, array $config, $documentCreator = null)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->documentCreator = $documentCreator ?? function (array $params, string $mime): int {
            return Document::create($this->pdo, $params);
        };
    }

    /**
     * Extract the `odm-xxxx` token from the message body (e.g. "[odm-ab3x7]"
     * or a bare "odm-ab3x7" anywhere in the body). The token rides in the body
     * rather than the subject because subject lines are more widely logged,
     * forwarded, and displayed — the body is less exposed.
     */
    private function extractToken(string $body): ?string
    {
        if (preg_match('/(?:\[)?(odm-[a-f0-9]+)(?:\])?/i', $body, $m) === 1) {
            return $m[1];
        }
        return null;
    }

    public function resolveUserByBody(string $body): ?int
    {
        $token = $this->extractToken($body);
        if ($token === null) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->config['db_prefix']}user WHERE mail_token = :token");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }

    public function process(EmailMessage $message): array
    {
        $stats = ['created' => 0, 'rejected' => 0, 'errors' => 0];
        $userId = $this->resolveUserByBody($message->body);
        $token = $this->extractToken($message->body) ?? '';

        if ($userId === null) {
            $this->writeAudit($message, null, 'rejected', 'no valid token', null);
            $stats['rejected']++;
            return $stats;
        }

        $maxFilesize = (int) ($this->config['max_filesize'] ?? 0);
        $attachmentCap = (int) ($this->config['email_max_attachments'] ?? 20);
        foreach ($message->attachments as $index => $att) {
            if ($index >= $attachmentCap) {
                $this->writeAudit($message, $userId, 'rejected', 'too many attachments', null);
                $stats['rejected']++;
                continue;
            }

            if ($maxFilesize > 0 && (int) ($att['size'] ?? 0) > $maxFilesize) {
                $this->writeAudit(
                    $message,
                    $userId,
                    'rejected',
                    'attachment exceeds max file size: ' . $att['name'],
                    null
                );
                $stats['rejected']++;
                continue;
            }

            $mime = $att['mime'];
            if (!in_array($mime, $this->config['allowedFileTypes'], true)) {
                $this->writeAudit($message, $userId, 'rejected', 'disallowed file type: ' . $mime, null);
                $stats['rejected']++;
                continue;
            }

            $publishable = ($this->config['authorization'] === 'True') ? '0' : '1';
            $params = [
                'category' => (int) $this->config['mail_default_category'],
                'owner_id' => $userId,
                'realname' => $att['name'],
                'description' => trim($message->subject),
                'department' => (int) $this->config['mail_default_department'],
                'comment' => 'Imported via email from ' . $message->from,
                'publishable' => $publishable,
                'is_public' => 0,
                'dept_perms' => [],
                'user_perms' => [$userId => 4],
                'username' => '',
                'source_path' => $att['path'],
                'source_is_upload' => false,
                'mime' => $mime,
            ];
            try {
                $docId = call_user_func($this->documentCreator, $params, $mime);
                $this->writeAudit($message, $userId, 'created', '', $docId);
                $stats['created']++;
            } catch (Exception $e) {
                $this->writeAudit($message, $userId, 'error', $e->getMessage(), null);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private function writeAudit(EmailMessage $message, ?int $userId, string $outcome, string $reason, ?int $docId): void
    {
        $tokenHash = null;
        $t = $this->extractToken($message->body);
        if ($t !== null) {
            $tokenHash = hash('sha256', $t);
        }
        $stmt = $this->pdo->prepare("INSERT INTO {$this->config['db_prefix']}email_audit (message_id, from_address, token_hash, outcome, document_id, reason) VALUES (:mid, :from, :hash, :outcome, :did, :reason)");
        $stmt->execute([
            ':mid' => $message->id,
            ':from' => $message->from,
            ':hash' => $tokenHash,
            ':outcome' => $outcome,
            ':did' => $docId,
            ':reason' => $reason,
        ]);
    }
}