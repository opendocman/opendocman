<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/EmailMessage.class.php';
require_once APPLICATION_PATH . '/models/EmailIngest.class.php';

class EmailIngestTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private array $createdCalls = [];
    private array $auditWrites = [];

    /**
     * Build a mock PDO whose prepare() routes by SQL text:
     * - queries containing "FROM ... user" return the given user row (or null)
     * - the email_audit INSERT captures its bound params into $this->auditWrites
     * - any other query just executes
     */
    private function mockPdo(?array $userRow): PDO
    {
        $pdo = \Mockery::mock('PDO');
        $pdo->shouldReceive('prepare')->andReturnUsing(function (string $sql) use ($userRow) {
            $stmt = \Mockery::mock('PDOStatement');
            if (strpos($sql, 'FROM') !== false && strpos($sql, 'user') !== false) {
                $stmt->shouldReceive('fetch')->andReturn($userRow);
            }
            if (strpos($sql, 'email_audit') !== false) {
                $stmt->shouldReceive('execute')->andReturnUsing(function (array $params) {
                    $this->auditWrites[] = $params;
                    return true;
                });
            } else {
                $stmt->shouldReceive('execute')->andReturn(true);
            }
            return $stmt;
        });
        return $pdo;
    }

    public function creator(array $params, string $mime): int
    {
        $this->createdCalls[] = $params;
        return 100 + count($this->createdCalls);
    }

    private function makeIngest(array $config, ?array $userRow): EmailIngest
    {
        $this->createdCalls = [];
        $this->auditWrites = [];
        return new EmailIngest($this->mockPdo($userRow), $config, [$this, 'creator']);
    }

    private function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'db_prefix' => 'odm_',
            'authorization' => 'False',
            'allowedFileTypes' => ['application/pdf'],
            'mail_default_category' => 3,
            'mail_default_department' => 2,
            'max_filesize' => 5000000,
            'email_max_attachments' => 2,
        ], $overrides);
    }

    private function msg(string $id, string $subject, string $body, array $atts = []): EmailMessage
    {
        $m = new EmailMessage($id, $subject, 'a@b.com');
        $m->body = $body;
        $m->attachments = $atts;
        return $m;
    }

    private function pdf(string $name = 'a.pdf', int $size = 100): array
    {
        return ['name' => $name, 'path' => '/tmp/' . $name, 'mime' => 'application/pdf', 'size' => $size];
    }

    public function testResolveUserByBodyReturnsNullWhenNoMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], null);
        $this->assertNull($ingest->resolveUserByBody('Q3 [odm-abc123] report'));
    }

    public function testResolveUserByBodyReturnsUserIdOnMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], ['id' => 7]);
        $this->assertSame(7, $ingest->resolveUserByBody('Please file this. [odm-abc123] thanks'));
    }

    public function testResolveUserByBodyMatchesBareToken(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], ['id' => 7]);
        $this->assertSame(7, $ingest->resolveUserByBody('odm-abc123'));
        $this->assertSame(7, $ingest->resolveUserByBody('token: odm-abc123 rest'));
    }

    public function testProcessCreatesOneDocForTokenInBody(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), ['id' => 7]);
        $msg = $this->msg('mB', 'Contract draft', 'Please attach. [odm-abc123]', [$this->pdf()]);
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(7, $this->createdCalls[0]['owner_id']);
    }

    public function testDescriptionIsSubject(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), ['id' => 7]);
        $msg = $this->msg('mD1', 'Q3 invoices', 'token in body [odm-abc123]', [$this->pdf()]);
        $ingest->process($msg);
        $this->assertSame('Q3 invoices', $this->createdCalls[0]['description']);
        $this->assertStringNotContainsString('odm-', $this->createdCalls[0]['description']);
    }

    public function testProcessRejectsMissingToken(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), null);
        $msg = $this->msg('m1', 'no token here', 'ordinary body', [$this->pdf()]);
        $result = $ingest->process($msg);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testProcessCreatesOneDocPerValidAttachment(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(['email_max_attachments' => 10]), ['id' => 7]);
        $msg = $this->msg('m2', 'Report', '[odm-abc123]', [$this->pdf('a.pdf'), $this->pdf('b.pdf')]);
        $result = $ingest->process($msg);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['rejected']);
        $this->assertSame(7, $this->createdCalls[0]['owner_id']);
    }

    public function testProcessRejectsBadMimePerAttachment(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), ['id' => 7]);
        $msg = $this->msg('m3', 'Report', '[odm-abc123]', [
            $this->pdf('a.pdf'),
            ['name' => 'x.exe', 'path' => '/tmp/x.exe', 'mime' => 'application/x-msdownload', 'size' => 100],
        ]);
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testProcessRejectsAttachmentOverMaxSize(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(['max_filesize' => 1000]), ['id' => 7]);
        $msg = $this->msg('mSz', 'Big file', '[odm-abc123]', [
            $this->pdf('small.pdf', 100),
            $this->pdf('big.pdf', 5000),
        ]);
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['rejected']);
        $this->assertSame('rejected', $this->auditWrites[1][':outcome']);
        $this->assertStringContainsString('max file size', $this->auditWrites[1][':reason']);
    }

    public function testProcessRejectsBeyondAttachmentCap(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(['email_max_attachments' => 1]), ['id' => 7]);
        $msg = $this->msg('mCap', 'Too many', '[odm-abc123]', [$this->pdf('a.pdf'), $this->pdf('b.pdf')]);
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['rejected']);
        $this->assertStringContainsString('too many attachments', $this->auditWrites[1][':reason']);
    }

    public function testPublishableIsZeroWhenAuthorizationOn(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(['authorization' => 'True']), ['id' => 7]);
        $msg = $this->msg('m4', 'Report', '[odm-abc123]', [$this->pdf()]);
        $ingest->process($msg);
        $this->assertSame('0', $this->createdCalls[0]['publishable']);
    }

    public function testPublishableIsOneWhenAuthorizationOff(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(['authorization' => 'False']), ['id' => 7]);
        $msg = $this->msg('m5', 'Subject', '[odm-abc123]', [$this->pdf()]);
        $ingest->process($msg);
        $this->assertSame('1', $this->createdCalls[0]['publishable']);
    }

    public function testAuditWritesCreatedRowForValidAttachment(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), ['id' => 7]);
        $msg = $this->msg('m7', 'Report', '[odm-abc123]', [$this->pdf()]);
        $ingest->process($msg);

        $this->assertCount(1, $this->auditWrites);
        $row = $this->auditWrites[0];
        $this->assertSame('created', $row[':outcome']);
        $this->assertSame(101, $row[':did']);
        $this->assertSame(hash('sha256', 'odm-abc123'), $row[':hash']);
        $this->assertSame('', $row[':reason']);
    }

    public function testAuditWritesRejectedRowForDisallowedMime(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), ['id' => 7]);
        $msg = $this->msg('m7', 'Report', '[odm-abc123]', [
            $this->pdf('a.pdf'),
            ['name' => 'x.exe', 'path' => '/tmp/x.exe', 'mime' => 'application/x-msdownload', 'size' => 100],
        ]);
        $ingest->process($msg);

        $this->assertCount(2, $this->auditWrites);
        $rejected = $this->auditWrites[1];
        $this->assertSame('rejected', $rejected[':outcome']);
        $this->assertNull($rejected[':did']);
        $this->assertStringContainsString('application/x-msdownload', $rejected[':reason']);
    }

    public function testAuditWritesRejectedRowForMissingToken(): void
    {
        $ingest = $this->makeIngest($this->baseConfig(), null);
        $msg = $this->msg('m8', 'no token here', 'plain body', [$this->pdf()]);
        $result = $ingest->process($msg);

        $this->assertSame(1, $result['rejected']);
        $this->assertCount(1, $this->auditWrites);
        $row = $this->auditWrites[0];
        $this->assertSame('rejected', $row[':outcome']);
        $this->assertNull($row[':hash']);
        $this->assertNull($row[':did']);
    }

    public function testAuditWritesErrorRowWhenCreatorThrows(): void
    {
        $ingest = new EmailIngest($this->mockPdo(['id' => 7]), $this->baseConfig(), function (array $params, string $mime): int {
            throw new \RuntimeException('disk full while writing');
        });
        $msg = $this->msg('m9', 'Report', '[odm-abc123]', [$this->pdf()]);
        $result = $ingest->process($msg);

        $this->assertSame(1, $result['errors']);
        $this->assertSame(0, $result['created']);
        $this->assertCount(1, $this->auditWrites);
        $row = $this->auditWrites[0];
        $this->assertSame('error', $row[':outcome']);
        $this->assertNull($row[':did']);
        $this->assertStringContainsString('disk full', $row[':reason']);
    }
}