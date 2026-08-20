<?php

require_once __DIR__ . '/EmailMessage.class.php';

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
        $folderObj = $this->client->getFolder($this->folder);
        $messages = $folderObj->messages()->unseen()->get();

        $result = [];
        foreach ($messages as $msg) {
            $em = new EmailMessage(
                (string) $msg->getUid(),
                (string) $msg->getSubject(),
                $this->extractSender($msg)
            );

            foreach ($msg->getAttachments() as $att) {
                $name = $att->getName();
                if ($name === null || $name === '') {
                    continue;
                }

                $path = tempnam(sys_get_temp_dir(), 'odm_att_');
                if ($path === false) {
                    continue;
                }
                file_put_contents($path, $att->getContent());

                $mime = $att->getContentType();
                if ($mime === null || $mime === '') {
                    $mime = $att->getType();
                }
                if ($mime === null || $mime === '') {
                    $mime = 'application/octet-stream';
                }

                $em->attachments[] = ['name' => $name, 'path' => $path, 'mime' => $mime];
            }

            $result[] = $em;
        }

        return $result;
    }

    /**
     * Mark a message as read by its UID.
     */
    public function markRead(string $id): void
    {
        $this->client->getFolder($this->folder)
            ->messages()
            ->getMessageByUid((int) $id)
            ->setFlag('Seen');
    }

    /**
     * Delete a message by its UID (marks \Deleted and expunges).
     */
    public function delete(string $id): void
    {
        $this->client->getFolder($this->folder)
            ->messages()
            ->getMessageByUid((int) $id)
            ->delete();
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