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
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
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
}