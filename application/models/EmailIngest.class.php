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
     * Extract the `odm-xxxx` token from a subject line like "[odm-ab3x7] Q3 invoices"
     * or a bare "odm-ab3x7" anywhere in the subject.
     */
    private function extractToken(string $subject): ?string
    {
        if (preg_match('/(?:\[)?(odm-[a-f0-9]+)(?:\])?/i', $subject, $m) === 1) {
            return $m[1];
        }
        return null;
    }

    /**
     * Remove the ingest token (bracketed or bare) from a subject line so the
     * token never ends up in the document description or elsewhere user-facing.
     */
    private function stripTokenFromSubject(string $subject): string
    {
        $stripped = preg_replace('/\s*\[(odm-[a-f0-9]+)\]\s*/i', ' ', $subject);
        $stripped = preg_replace('/\s*odm-[a-f0-9]+\s*/i', ' ', $stripped);
        return trim(preg_replace('/\s+/', ' ', $stripped));
    }

    public function resolveUserBySubject(string $subject): ?int
    {
        $token = $this->extractToken($subject);
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
        $userId = $this->resolveUserBySubject($message->subject);
        $token = $this->extractToken($message->subject) ?? '';

        if ($userId === null) {
            $this->writeAudit($message, null, 'rejected', 'no valid token', null);
            $stats['rejected']++;
            return $stats;
        }

        foreach ($message->attachments as $att) {
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
                'description' => $this->stripTokenFromSubject($message->subject),
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
        $t = $this->extractToken($message->subject);
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