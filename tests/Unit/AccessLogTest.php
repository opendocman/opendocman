<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/AccessLog.class.php';

class AccessLogTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Minimal CONFIG required by AccessLog
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';

        // Set a default session UID used by AccessLog::addLogEntry
        $_SESSION['uid'] = 42;
    }

    public function testAddLogEntryInsertsWithProvidedFileId(): void
    {
        $fileId = 7;
        $action = 'view';
        $expectedUid = $_SESSION['uid'];

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // Ensure SQL targets the correct prefixed table
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}access_log") !== false
                    && stripos($sql, 'INSERT INTO') !== false;
            }))
            ->andReturn($stmt);

        // Validate parameters passed to execute()
        $stmt->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function ($params) use ($fileId, $expectedUid, $action) {
                return is_array($params)
                    && array_key_exists(':file_id', $params)
                    && array_key_exists(':uid', $params)
                    && array_key_exists(':type', $params)
                    && $params[':file_id'] === $fileId
                    && $params[':uid'] === $expectedUid
                    && $params[':type'] === $action;
            }))
            ->andReturn(true);

        AccessLog::addLogEntry($fileId, $action, $pdo);
        $this->assertTrue(true, 'AccessLog::addLogEntry executed expected PDO interactions');
    }

    public function testAddLogEntryUsesGlobalIdWhenFileIdZero(): void
    {
        // AccessLog falls back to global $id when $fileId == 0
        global $id;
        $id = 123;

        $fileId = 0;
        $action = 'download';
        $expectedUid = $_SESSION['uid'];

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}access_log") !== false
                    && stripos($sql, 'INSERT INTO') !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function ($params) use ($id, $expectedUid, $action) {
                return is_array($params)
                    && $params[':file_id'] === $id
                    && $params[':uid'] === $expectedUid
                    && $params[':type'] === $action;
            }))
            ->andReturn(true);

        AccessLog::addLogEntry($fileId, $action, $pdo);
        $this->assertTrue(true, 'AccessLog::addLogEntry used global $id when fileId was zero');
    }

    public function testConstructorSetsMetadataAndMessage(): void
    {
        $msg = 'Hello Log';
        $log = new AccessLog($msg);

        $this->assertSame('AccessLog', $log->name);
        $this->assertSame('Stephen Lawrence Jr', $log->author);
        $this->assertSame('1.0', $log->version);
        $this->assertSame('http://www.opendocman.com', $log->homepage);
        $this->assertSame('AccessLog Plugin for OpenDocMan', $log->description);
        $this->assertSame($msg, $log->getAccessLog());
    }

    public function testSetAndGetAccessLog(): void
    {
        $log = new AccessLog();
        $this->assertSame('', $log->getAccessLog());

        $log->setAccessLog('New Message');
        $this->assertSame('New Message', $log->getAccessLog());
    }
}
