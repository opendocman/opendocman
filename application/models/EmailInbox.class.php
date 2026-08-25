<?php

require_once __DIR__ . '/EmailMessage.class.php';
require_once __DIR__ . '/EmailInboxException.class.php';

/**
 * EmailInbox - thin adapter over webklex/php-imap (v6.x).
 *
 * This is the ONLY place in the codebase that touches the IMAP library. It
 * normalizes the library's objects into the EmailMessage DTO so the ingest
 * layer never depends on webklex specifics.
 *
 * The brief's draft API was approximate and did not match the real v6 API
 * (namespace, ClientManager::make(), folder->messages()->unseen()->get(),
 * getMessageByUid(), case-sensitive flag names, config key names). The method
 * calls below were verified against vendor/webklex/php-imap source.
 */
class EmailInbox
{
    /** @var \Webklex\PHPIMAP\Client */
    private $client;
    private string $folder;

    /**
     * Temp-file cleanup ownership:
     *
     * Each EmailInbox instance owns a private subdirectory under
     * sys_get_temp_dir() (odm_att_<random>/) where it writes attachment bytes.
     * Every file written there is tracked in $tempFiles. Call cleanup() after
     * a polling cycle to remove all written attachments (and the directory if
     * it becomes empty). __destruct() calls cleanup() as a safety net so no
     * orphaned temp files accumulate even if the ingest layer forgets.
     *
     * The ingest layer (Task 6) must call $inbox->cleanup() once per batch.
     */
    private ?string $tempDir = null;
    private array $tempFiles = [];

    /**
     * @param array $config Expected keys: host, port, protocol, encryption,
     *                       user, pass. (The app-facing key names differ from
     *                       the library's username/password, so they are
     *                       mapped here at the boundary.)
     */
    public function __construct(array $config)
    {
        $this->folder = $config['folder'] ?? 'INBOX';

        $encryption = $config['encryption'] ?? 'ssl';
        // The library treats encryption as a transport; "none" must map to a
        // falsy value or it is treated as a truthy but unrecognized transport.
        if ($encryption === 'none' || $encryption === 'false' || $encryption === false || $encryption === '') {
            $encryption = false;
        }

        $clientConfig = [
            'host' => $config['host'],
            'port' => (int) ($config['port'] ?? 993),
            'protocol' => $config['protocol'] ?? 'imap',
            'encryption' => $encryption,
            'username' => $config['user'],
            'password' => $config['pass'],
        ];

        if (isset($config['validate_cert'])) {
            $clientConfig['validate_cert'] = (bool) $config['validate_cert'];
        }

        $manager = new \Webklex\PHPIMAP\ClientManager();
        $this->client = $manager->make($clientConfig);
    }

    /**
     * Fetch all unread messages in the configured folder.
     *
     * @return EmailMessage[]
     */
    public function fetchMessages(): array
    {
        return $this->withoutWarnings(function (): array {
            $folderObj = $this->resolveFolder();
            $messages = $folderObj->messages()->unseen()->get();

            $result = [];
            foreach ($messages as $msg) {
                $em = new EmailMessage(
                    (string) $msg->getUid(),
                    (string) $msg->getSubject(),
                    $this->extractSender($msg)
                );

                $textBody = (string) $msg->getTextBody();
                $htmlBody = (string) $msg->getHTMLBody();
                $em->body = trim($textBody !== '' ? $textBody : $htmlBody);

                foreach ($msg->getAttachments() as $att) {
                    $name = $att->getName();
                    if ($name === null || $name === '') {
                        continue;
                    }

                    $path = $this->writeAttachment($att);
                    if ($path === null) {
                        continue;
                    }

                    // Content-derived MIME only. The email's declared
                    // Content-Type header is attacker-controlled and must not
                    // influence what we accept. If finfo cannot classify the
                    // bytes we fall back to octet-stream, which never matches
                    // the allowlist so the attachment is rejected.
                    $mime = $att->getMimeType();
                    if ($mime === null || $mime === '') {
                        $mime = 'application/octet-stream';
                    }

                    $em->attachments[] = [
                        'name' => $name,
                        'path' => $path,
                        'mime' => $mime,
                        'size' => (int) @filesize($path),
                    ];
                }

                $result[] = $em;
            }

            return $result;
        });
    }

    /**
     * Run a callable with PHP warnings/notices promoted to exceptions, restoring
     * the previous error handler afterward. Lets a low-level network failure
     * (e.g. stream_socket_client) surface as an exception the CLI can catch
     * cleanly instead of leaking a raw warning to stderr.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     * @throws \ErrorException
     */
    private function withoutWarnings(callable $callback)
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Mark a message as read by its UID.
     */
    public function markRead(string $id): void
    {
        $this->resolveFolder()
            ->messages()
            ->getMessageByUid((int) $id)
            ->setFlag('Seen');
    }

    /**
     * Delete a message by its UID (marks \Deleted and expunges).
     */
    public function delete(string $id): void
    {
        $this->resolveFolder()
            ->messages()
            ->getMessageByUid((int) $id)
            ->delete();
    }

    /**
     * Remove all temp files created by this instance's fetchMessages() calls.
     *
     * Call after a polling cycle. Also removes the instance's private temp
     * directory if it has become empty. Idempotent and safe to call more than
     * once.
     */
    public function cleanup(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempFiles = [];

        if ($this->tempDir !== null && is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
        $this->tempDir = null;
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Resolve the configured folder, throwing a catchable EmailInboxException
     * if the folder cannot be resolved (getFolder() returns ?Folder).
     *
     * @throws EmailInboxException
     * @return \Webklex\PHPIMAP\Folder
     */
    private function resolveFolder()
    {
        $folderObj = $this->client->getFolder($this->folder);
        if ($folderObj === null) {
            throw new EmailInboxException(
                "IMAP/POP3 folder \"{$this->folder}\" could not be resolved on the configured account."
            );
        }
        return $folderObj;
    }

    /**
     * Write an attachment's bytes to this instance's private temp directory and
     * track the created file for later cleanup().
     *
     * @param \Webklex\PHPIMAP\Attachment $att
     * @return string|null the file path, or null if the write failed.
     */
    private function writeAttachment($att): ?string
    {
        $dir = $this->tempDir();
        $path = tempnam($dir, 'odm_att_');
        if ($path === false) {
            return null;
        }

        $bytes = $att->getContent();
        if ($bytes === null || $bytes === '') {
            @unlink($path);
            return null;
        }

        if (file_put_contents($path, $bytes) === false) {
            @unlink($path);
            return null;
        }

        $this->tempFiles[] = $path;
        return $path;
    }

    /**
     * Lazily create and return this instance's private temp subdirectory.
     */
    private function tempDir(): string
    {
        if ($this->tempDir === null) {
            $this->tempDir = rtrim(sys_get_temp_dir(), '/\\')
                . DIRECTORY_SEPARATOR . 'odm_att_'
                . bin2hex(random_bytes(8))
                . DIRECTORY_SEPARATOR;
        }
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0700, true);
        }
        return $this->tempDir;
    }

    /**
     * Extract the first sender's email address from a webklex Message.
     *
     * @param \Webklex\PHPIMAP\Message $msg
     */
    private function extractSender($msg): string
    {
        $from = $msg->getFrom();
        $first = $from !== null ? $from->first() : null;
        if ($first === null || $first === false) {
            return '';
        }
        return (string) ($first->mail ?? '');
    }
}