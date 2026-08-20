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

    public function testResolveUserBySubjectReturnsNullWhenNoMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], null);
        $this->assertNull($ingest->resolveUserBySubject('Q3 [odm-abc123] report'));
    }

    public function testResolveUserBySubjectReturnsUserIdOnMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], ['id' => 7]);
        $this->assertSame(7, $ingest->resolveUserBySubject('Q3 [odm-abc123] report'));
    }

    public function testProcessRejectsMissingToken(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf']], null);
        $msg = new EmailMessage('m1', 'no token here', 'a@b.com');
        $result = $ingest->process($msg);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testProcessCreatesOneDocPerValidAttachment(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m2', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [
            ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'],
            ['name' => 'b.pdf', 'path' => '/tmp/b.pdf', 'mime' => 'application/pdf'],
        ];
        $result = $ingest->process($msg);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['rejected']);
        $this->assertSame(7, $this->createdCalls[0]['owner_id']);
    }

    public function testProcessRejectsBadMimePerAttachment(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m3', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [
            ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'],
            ['name' => 'x.exe', 'path' => '/tmp/x.exe', 'mime' => 'application/x-msdownload'],
        ];
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testPublishableIsZeroWhenAuthorizationOn(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'True', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m4', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
        $ingest->process($msg);
        $this->assertSame('0', $this->createdCalls[0]['publishable']);
    }

    public function testPublishableIsOneWhenAuthorizationOff(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m5', 'Subject [odm-abc123]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
        $ingest->process($msg);
        $this->assertSame('1', $this->createdCalls[0]['publishable']);
    }

    public function testAuditWritesCreatedRowForValidAttachment(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m7', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
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
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m7', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [
            ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'],
            ['name' => 'x.exe', 'path' => '/tmp/x.exe', 'mime' => 'application/x-msdownload'],
        ];
        $ingest->process($msg);

        $this->assertCount(2, $this->auditWrites);
        $rejected = $this->auditWrites[1];
        $this->assertSame('rejected', $rejected[':outcome']);
        $this->assertNull($rejected[':did']);
        $this->assertStringContainsString('application/x-msdownload', $rejected[':reason']);
    }

    public function testAuditWritesRejectedRowForMissingToken(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, null);
        $msg = new EmailMessage('m8', 'no token here', 'a@b.com');
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
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = new EmailIngest($this->mockPdo(['id' => 7]), $config, function (array $params, string $mime): int {
            throw new \RuntimeException('disk full while writing');
        });
        $msg = new EmailMessage('m9', 'Report [odm-abc123]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
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