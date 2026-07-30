<?php
namespace Test\Integration;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once APPLICATION_PATH . '/models/Snapshot.class.php';
require_once APPLICATION_PATH . '/models/SnapshotManager.class.php';

class SnapshotRestoreTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/odm_int_test_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpDir);
        parent::tearDown();
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_link($path)) {
                unlink($path);
            } else {
                is_dir($path) ? $this->rrmdir($path) : unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testFullCreateRestoreCycle(): void
    {
        $snapshotDir = $this->tmpDir . '/snapshots';
        $dataDir = $this->tmpDir . '/data';
        mkdir($snapshotDir, 0700, true);
        mkdir($dataDir, 0700, true);

        file_put_contents($dataDir . '/1.dat', 'original file content');
        file_put_contents($dataDir . '/2.dat', 'another file');

        $pdo = \Mockery::mock(\PDO::class);

        // SHOW TABLES (query, no execute)
        $tableStmt = \Mockery::mock(\PDOStatement::class);
        $tableStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(['odm_settings']);
        $pdo->shouldReceive('quote')->with('odm_%')->once()->andReturn("'odm_%'");
        $pdo->shouldReceive('query')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt);

        // SHOW CREATE TABLE
        $createStmt = \Mockery::mock(\PDOStatement::class);
        $createStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->once()->andReturn(
            ['Table' => 'odm_settings', 'Create Table' => "CREATE TABLE `odm_settings` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `name` varchar(255) NOT NULL,\n  `value` text,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB"]
        );
        $pdo->shouldReceive('query')->with("SHOW CREATE TABLE `odm_settings`")->once()->andReturn($createStmt);

        // SHOW COLUMNS
        $colStmt = \Mockery::mock(\PDOStatement::class);
        $colStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(
            ['id', 'name', 'value']
        );
        $pdo->shouldReceive('query')->with("SHOW COLUMNS FROM `odm_settings`")->once()->andReturn($colStmt);

        // SELECT * (query, no execute)
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_NUM)->once()->andReturn(
            [[1, 'demo', 'True']]
        );
        $pdo->shouldReceive('query')->with("SELECT * FROM `odm_settings`")->once()->andReturn($dataStmt);

        // PDO quote
        $pdo->shouldReceive('quote')->with(1)->once()->andReturn("'1'");
        $pdo->shouldReceive('quote')->with('demo')->once()->andReturn("'demo'");
        $pdo->shouldReceive('quote')->with('True')->once()->andReturn("'True'");

        // Create snapshot
        $manager = new \SnapshotManager($pdo, $snapshotDir, $dataDir, 'odm_');
        $snapshot = $manager->create('test-cycle', 'Integration test');

        $this->assertSame('test-cycle', $snapshot->name);
        $this->assertFileExists($snapshotDir . '/test-cycle/db.sql.gz');
        $this->assertFileExists($snapshotDir . '/test-cycle/files.tar.gz');
        $this->assertFileExists($snapshotDir . '/test-cycle/metadata.json');

        // Restore path mocks
        $tableStmt2 = \Mockery::mock(\PDOStatement::class);
        $tableStmt2->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(
            ['odm_settings']
        );
        $pdo->shouldReceive('quote')->with('odm_%')->once()->andReturn("'odm_%'");
        $pdo->shouldReceive('query')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt2);

        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 0')->twice()->andReturn(0);
        $pdo->shouldReceive('exec')->with("DROP TABLE IF EXISTS `odm_settings`")->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 1')->twice()->andReturn(0);

        $pdo->shouldReceive('exec')->with("SET sql_mode = ''")->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with(\Mockery::pattern('/CREATE TABLE/'))->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with(\Mockery::pattern('/INSERT INTO/'))->once()->andReturn(1);

        $manager->restore('test-cycle');

        $this->assertFileExists($dataDir . '/1.dat');
        $this->assertFileExists($dataDir . '/2.dat');
    }
}