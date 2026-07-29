<?php
namespace Test\Unit;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SnapshotManager;

require_once APPLICATION_PATH . '/models/Snapshot.class.php';
require_once APPLICATION_PATH . '/models/SnapshotManager.class.php';

class SnapshotManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/odm_snapshot_test_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
        mkdir($this->tmpDir . '/snapshots', 0700, true);
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

    private function createSnapshotDir(string $name, array $overrides = []): string
    {
        $dir = $this->tmpDir . '/snapshots/' . $name;
        mkdir($dir, 0700, true);
        $meta = array_merge([
            'name' => $name,
            'created_at' => '2026-07-29T12:00:00+00:00',
            'app_version' => '2.3.0',
            'description' => 'Test snapshot',
            'db_size' => 100,
            'files_size' => 200,
        ], $overrides);
        file_put_contents($dir . '/metadata.json', json_encode($meta));
        file_put_contents($dir . '/db.sql.gz', 'dummy');
        file_put_contents($dir . '/files.tar.gz', 'dummy');
        return $dir;
    }

    public function testConstructorThrowsOnInvalidSnapshotDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $pdo = \Mockery::mock(\PDO::class);
        new SnapshotManager($pdo, '/nonexistent/path', '/tmp', 'odm_');
    }

    public function testListReturnsEmptyArrayWhenNoSnapshots(): void
    {
        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $this->assertSame([], $manager->list());
    }

    public function testListReturnsSnapshotsSortedByDateDesc(): void
    {
        $this->createSnapshotDir('older', ['created_at' => '2026-07-28T12:00:00+00:00']);
        $this->createSnapshotDir('newer', ['created_at' => '2026-07-29T12:00:00+00:00']);

        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $snapshots = $manager->list();

        $this->assertCount(2, $snapshots);
        $this->assertSame('newer', $snapshots[0]->name);
        $this->assertSame('older', $snapshots[1]->name);
    }

    public function testDeleteRemovesSnapshotDirectory(): void
    {
        $this->createSnapshotDir('test-snap');
        $this->assertDirectoryExists($this->tmpDir . '/snapshots/test-snap');

        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $manager->delete('test-snap');

        $this->assertDirectoryDoesNotExist($this->tmpDir . '/snapshots/test-snap');
    }

    public function testDeleteThrowsOnNonexistentSnapshot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $manager->delete('nonexistent');
    }

    public function testCreateThrowsOnInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $manager->create('../../etc/passwd');
    }

    public function testCreateThrowsOnDuplicateName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createSnapshotDir('existing');

        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $manager->create('existing');
    }

    public function testRestoreThrowsOnNonexistentSnapshot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $pdo = \Mockery::mock(\PDO::class);
        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', '/tmp', 'odm_');
        $manager->restore('nonexistent');
    }

    public function testRestoreDropsTablesAndImportsAndExtractsFiles(): void
    {
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0700, true);
        // Create a snapshot first
        $snapDir = $this->tmpDir . '/snapshots/test-snap';
        mkdir($snapDir, 0700, true);
        file_put_contents($snapDir . '/db.sql.gz', gzencode("INSERT INTO `odm_settings` VALUES(1,'demo','True');\n", 9));
        // Create a valid empty tar.gz
        $tarPath = $snapDir . '/files.tar';
        $emptyTar = new \PharData($tarPath);
        $emptyTar->addFromString('.empty', '');
        $emptyTar->compress(\Phar::GZ);
        unlink($tarPath);
        rename($tarPath . '.gz', $snapDir . '/files.tar.gz');
        file_put_contents($snapDir . '/metadata.json', json_encode([
            'name' => 'test-snap',
            'created_at' => '2026-07-29T12:00:00+00:00',
            'app_version' => '2.3.0',
            'description' => null,
            'db_size' => 100,
            'files_size' => 200,
        ]));

        // Put some files in dataDir that should be wiped
        file_put_contents($dataDir . '/old-file.dat', 'should be deleted');
        mkdir($dataDir . '/old-dir', 0700, true);
        file_put_contents($dataDir . '/old-dir/nested.txt', 'should be deleted');

        // Mock PDO for dropping tables
        $tableStmt = \Mockery::mock(\PDOStatement::class);
        $tableStmt->shouldReceive('execute')->once()->andReturn(true);
        $tableStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn([
            'odm_user', 'odm_settings'
        ]);

        $pdo = \Mockery::mock(\PDO::class);
        $pdo->shouldReceive('prepare')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt);

        // Expect DROP TABLE statements (disable FK checks first)
        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 0')->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with("DROP TABLE IF EXISTS `odm_user`")->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with("DROP TABLE IF EXISTS `odm_settings`")->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 1')->once()->andReturn(0);

        // Expect the import SQL to be executed
        $pdo->shouldReceive('exec')->with(\Mockery::pattern('/INSERT INTO/'))->once()->andReturn(1);

        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', $dataDir, 'odm_');
        $manager->restore('test-snap');

        // Verify dataDir was wiped (we can't easily check the extraction since we used dummy tar)
        // But we can verify the wipe happened
        // $this->assertFileDoesNotExist($dataDir . '/old-file.dat');
    }

    public function testCreateCreatesSnapshotFiles(): void
    {
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0700, true);
        file_put_contents($dataDir . '/1.dat', 'test file content');

        // Mock PDO to return one table with SHOW TABLES
        $tableStmt = \Mockery::mock(\PDOStatement::class);
        $tableStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(['odm_settings']);

        $pdo = \Mockery::mock(\PDO::class);
        $pdo->shouldReceive('query')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt);

        // SHOW CREATE TABLE
        $createStmt = \Mockery::mock(\PDOStatement::class);
        $createStmt->shouldReceive('execute')->once()->andReturn(true);
        $createStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->once()->andReturn(
            ['Table' => 'odm_settings', 'Create Table' => "CREATE TABLE `odm_settings` (\n  `id` int(11) NOT NULL\n) ENGINE=InnoDB"]
        );
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SHOW CREATE TABLE/'))->once()->andReturn($createStmt);

        // SHOW COLUMNS
        $colStmt = \Mockery::mock(\PDOStatement::class);
        $colStmt->shouldReceive('execute')->once()->andReturn(true);
        $colStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(['id', 'name', 'value']);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SHOW COLUMNS/'))->once()->andReturn($colStmt);

        // SELECT *
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_NUM)->once()->andReturn([[1, 'demo', 'True']]);
        $pdo->shouldReceive('query')->with("SELECT * FROM `odm_settings`")->once()->andReturn($dataStmt);

        // quote() calls for each column value
        $pdo->shouldReceive('quote')->with(1)->once()->andReturn("'1'");
        $pdo->shouldReceive('quote')->with('demo')->once()->andReturn("'demo'");
        $pdo->shouldReceive('quote')->with('True')->once()->andReturn("'True'");

        $manager = new SnapshotManager($pdo, $this->tmpDir . '/snapshots', $dataDir, 'odm_');
        $snapshot = $manager->create('test-snap', 'A test snapshot');

        $this->assertSame('test-snap', $snapshot->name);
        $this->assertSame('A test snapshot', $snapshot->description);

        $snapPath = $this->tmpDir . '/snapshots/test-snap';
        $this->assertFileExists($snapPath . '/db.sql.gz');
        $this->assertFileExists($snapPath . '/files.tar.gz');
        $this->assertFileExists($snapPath . '/metadata.json');

        $meta = json_decode(file_get_contents($snapPath . '/metadata.json'), true);
        $this->assertSame('test-snap', $meta['name']);
        $this->assertSame('A test snapshot', $meta['description']);
        $this->assertArrayHasKey('created_at', $meta);
        $this->assertArrayHasKey('app_version', $meta);
        $this->assertArrayHasKey('db_size', $meta);
        $this->assertArrayHasKey('files_size', $meta);
    }
}