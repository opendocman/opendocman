<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class DocumentTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = sys_get_temp_dir() . '/odm-doctest-' . uniqid() . '/';
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['dataDir'] = $this->dataDir;
    }

    protected function tearDown(): void
    {
        if ($this->dataDir !== null && is_dir($this->dataDir)) {
            $this->removeDir($this->dataDir);
        }
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testCreatePersistsDataRowAndReturnsId(): void
    {
        $stmt = \Mockery::mock('PDOStatement');
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('bindParam')->andReturn(true);

        $pdo = \Mockery::mock('PDO');
        $pdo->shouldReceive('prepare')->andReturn($stmt);
        $pdo->shouldReceive('lastInsertId')->andReturn(42);

        $id = Document::create($pdo, [
            'category' => 3,
            'owner_id' => 7,
            'realname' => 'report.pdf',
            'description' => 'desc',
            'department' => 2,
            'comment' => 'note',
            'publishable' => '0',
            'is_public' => 0,
            'dept_perms' => [2 => 3],
            'user_perms' => [7 => 4],
            'source_path' => __FILE__,
            'source_is_upload' => false,
            'mime' => 'application/octet-stream',
        ]);

        $this->assertSame(42, $id);
        $this->assertTrue(file_exists($this->dataDir . '42/report.pdf'));
        $this->assertTrue(is_readable($this->dataDir . '42/report.pdf'));
        $this->assertTrue(is_writable($this->dataDir . '42/report.pdf'));
    }
}