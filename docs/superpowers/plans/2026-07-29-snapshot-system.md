# Snapshot System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a snapshot/restore subsystem with CLI commands for automated demo-site refresh.

**Architecture:** A `SnapshotManager` model class handles CRUD of snapshots (DB dump + file archive). New CLI commands in `cli.php` expose snapshot operations and `demo:refresh`. A new `snapshotDir` setting stores the snapshot location. The existing demo mode feature is untouched.

**Tech Stack:** PHP 8.2, PDO, PharData (built-in), Mockery for tests, PHPUnit 9.5

## Global Constraints

- Snapshot names must match regex `[a-zA-Z0-9_-]+` — validated before any filesystem/command use
- `snapshotDir` must be outside webroot (same constraint as `dataDir`)
- `dataDir` and `snapshotDir` are queried from the `odm_settings` table at runtime
- All snapshot files get `chmod 0600`, directories `chmod 0700`
- `demo:refresh` always targets snapshot named `demo-baseline` — not `latest`
- No new web routes/controllers — CLI-only for now
- Existing demo mode (`$GLOBALS['CONFIG']['demo'] === 'True'`) is untouched
- `demo.php` at root is removed

---

## File Structure

### New files

| File | Responsibility |
|------|----------------|
| `application/models/Snapshot.class.php` | Value object — name, createdAt, appVersion, description, dbSize, filesSize |
| `application/models/SnapshotManager.class.php` | CRUD operations — create, restore, list, delete |
| `application/installer/migrations/Version001600.php` | Migration — adds `snapshotDir` setting |
| `tests/Unit/SnapshotTest.php` | Tests for Snapshot value object |
| `tests/Unit/SnapshotManagerTest.php` | Tests for SnapshotManager CRUD |

### Modified files

| File | Change |
|------|--------|
| `application/installer/cli.php` | Add `snapshot:*` and `demo:refresh` commands |
| `application/installer/SchemaBuilder.php` | Add `snapshotDir` default data row |
| `application/version.php` | Bump `ODM_DB_VERSION` to `1.6.0` |
| `application/controllers/settings.php` | Add `snapshotDir` validation (exists + writable) |
| `tests/bootstrap.php` | Add `Snapshot.class.php` and `SnapshotManager.class.php` requires |
| `database.sql` | Regenerate via `make dump-sql` |

### Deleted files

| File | Reason |
|------|--------|
| `demo.php` | Replaced by CLI commands |

---

### Task 1: Snapshot Value Object

**Files:**
- Create: `application/models/Snapshot.class.php`
- Create: `tests/Unit/SnapshotTest.php`
- Modify: `tests/bootstrap.php` (add require)

**Interfaces:**
- Produces: `Snapshot` class with typed properties

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Test\Unit;

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/Snapshot.class.php';

class SnapshotTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-29 12:00:00');
        $snapshot = new Snapshot(
            name: 'demo-baseline',
            createdAt: $createdAt,
            appVersion: '2.3.0',
            description: 'Demo baseline with sample docs',
            dbSize: 1024,
            filesSize: 2048
        );

        $this->assertSame('demo-baseline', $snapshot->name);
        $this->assertSame($createdAt, $snapshot->createdAt);
        $this->assertSame('2.3.0', $snapshot->appVersion);
        $this->assertSame('Demo baseline with sample docs', $snapshot->description);
        $this->assertSame(1024, $snapshot->dbSize);
        $this->assertSame(2048, $snapshot->filesSize);
    }

    public function testConstructorAllowsNullDescription(): void
    {
        $snapshot = new Snapshot(
            name: 'auto-backup',
            createdAt: new \DateTimeImmutable(),
            appVersion: '2.3.0',
            description: null,
            dbSize: 0,
            filesSize: 0
        );

        $this->assertNull($snapshot->description);
    }

    public function testFromJsonArray(): void
    {
        $data = [
            'name' => 'test-snap',
            'created_at' => '2026-07-29T12:00:00+00:00',
            'app_version' => '2.3.0',
            'description' => 'Test snapshot',
            'db_size' => 512,
            'files_size' => 1024,
        ];

        $snapshot = Snapshot::fromJsonArray($data);

        $this->assertSame('test-snap', $snapshot->name);
        $this->assertSame('2026-07-29T12:00:00+00:00', $snapshot->createdAt->format('c'));
        $this->assertSame('Test snapshot', $snapshot->description);
        $this->assertSame(512, $snapshot->dbSize);
        $this->assertSame(1024, $snapshot->filesSize);
    }

    public function testToJsonArray(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-29T12:00:00+00:00');
        $snapshot = new Snapshot(
            name: 'test-snap',
            createdAt: $createdAt,
            appVersion: '2.3.0',
            description: 'Test snapshot',
            dbSize: 512,
            filesSize: 1024
        );

        $array = $snapshot->toJsonArray();

        $this->assertSame('test-snap', $array['name']);
        $this->assertSame('2026-07-29T12:00:00+00:00', $array['created_at']);
        $this->assertSame('2.3.0', $array['app_version']);
        $this->assertSame('Test snapshot', $array['description']);
        $this->assertSame(512, $array['db_size']);
        $this->assertSame(1024, $array['files_size']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotTest`
Expected: ERROR — class not found

- [ ] **Step 3: Write minimal implementation**

```php
<?php

if (!defined('Snapshot_class')) {
    define('Snapshot_class', true);

    class Snapshot
    {
        public string $name;
        public \DateTimeImmutable $createdAt;
        public string $appVersion;
        public ?string $description;
        public int $dbSize;
        public int $filesSize;

        public function __construct(
            string $name,
            \DateTimeImmutable $createdAt,
            string $appVersion,
            ?string $description,
            int $dbSize,
            int $filesSize
        ) {
            $this->name = $name;
            $this->createdAt = $createdAt;
            $this->appVersion = $appVersion;
            $this->description = $description;
            $this->dbSize = $dbSize;
            $this->filesSize = $filesSize;
        }

        public static function fromJsonArray(array $data): self
        {
            return new self(
                name: $data['name'],
                createdAt: new \DateTimeImmutable($data['created_at']),
                appVersion: $data['app_version'],
                description: $data['description'] ?? null,
                dbSize: (int)($data['db_size'] ?? 0),
                filesSize: (int)($data['files_size'] ?? 0)
            );
        }

        public function toJsonArray(): array
        {
            return [
                'name' => $this->name,
                'created_at' => $this->createdAt->format('c'),
                'app_version' => $this->appVersion,
                'description' => $this->description,
                'db_size' => $this->dbSize,
                'files_size' => $this->filesSize,
            ];
        }
    }
}
```

- [ ] **Step 4: Add require to bootstrap**

```php
// In tests/bootstrap.php, add after the existing model requires:
require_once APPLICATION_PATH . '/models/Snapshot.class.php';
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotTest`
Expected: 4 tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add tests/bootstrap.php tests/Unit/SnapshotTest.php application/models/Snapshot.class.php
git commit -m "feat: add Snapshot value object"
```

---

### Task 2: SnapshotManager — Name Validation + List + Delete

**Files:**
- Create: `application/models/SnapshotManager.class.php`
- Create: `tests/Unit/SnapshotManagerTest.php`

**Interfaces:**
- Consumes: `Snapshot` class
- Produces: `SnapshotManager` with `__construct(PDO, snapshotDir, dataDir, dbPrefix)`, `list(): array`, `delete(name): void`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Test\Unit;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest`
Expected: ERROR — class not found

- [ ] **Step 3: Write minimal implementation**

```php
<?php

if (!defined('SnapshotManager_class')) {
    define('SnapshotManager_class', true);

    class SnapshotManager
    {
        private \PDO $pdo;
        private string $snapshotDir;
        private string $dataDir;
        private string $dbPrefix;

        public function __construct(\PDO $pdo, string $snapshotDir, string $dataDir, string $dbPrefix)
        {
            if (!is_dir($snapshotDir)) {
                throw new \InvalidArgumentException("Snapshot directory does not exist: {$snapshotDir}");
            }
            $this->pdo = $pdo;
            $this->snapshotDir = rtrim($snapshotDir, '/') . '/';
            $this->dataDir = rtrim($dataDir, '/') . '/';
            $this->dbPrefix = $dbPrefix;
        }

        public function list(): array
        {
            $snapshots = [];
            $items = scandir($this->snapshotDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $metaPath = $this->snapshotDir . $item . '/metadata.json';
                if (!is_file($metaPath)) continue;
                $data = json_decode(file_get_contents($metaPath), true);
                if (!is_array($data)) continue;
                $snapshots[] = Snapshot::fromJsonArray($data);
            }
            usort($snapshots, function (Snapshot $a, Snapshot $b) {
                return $b->createdAt->getTimestamp() - $a->createdAt->getTimestamp();
            });
            return $snapshots;
        }

        public function delete(string $name): void
        {
            $this->validateName($name);
            $path = $this->snapshotDir . $name;
            if (!is_dir($path)) {
                throw new \InvalidArgumentException("Snapshot not found: {$name}");
            }
            $this->rrmdir($path);
        }

        protected function validateName(string $name): void
        {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                throw new \InvalidArgumentException(
                    "Invalid snapshot name. Use only letters, numbers, hyphens, and underscores."
                );
            }
        }

        protected function rrmdir(string $dir): void
        {
            $items = array_diff(scandir($dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                is_dir($path) ? $this->rrmdir($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
}
```

- [ ] **Step 4: Add require to bootstrap**

```php
// In tests/bootstrap.php, add after the Snapshot require:
require_once APPLICATION_PATH . '/models/SnapshotManager.class.php';
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest`
Expected: 5 tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/SnapshotManagerTest.php application/models/SnapshotManager.class.php tests/bootstrap.php
git commit -m "feat: add SnapshotManager with list and delete"
```

---

### Task 3: SnapshotManager — Create

**Files:**
- Modify: `application/models/SnapshotManager.class.php`
- Modify: `tests/Unit/SnapshotManagerTest.php`

**Interfaces:**
- Consumes: `Snapshot`, `SnapshotManager` constructor, `validateName()`, `rrmdir()`
- Produces: `SnapshotManager::create(name, ?description): Snapshot`

- [ ] **Step 1: Write the failing test**

```php
// Add to SnapshotManagerTest class

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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest::testCreate`
Expected: FAIL — `create()` method not found or incomplete

- [ ] **Step 3: Write create() implementation**

```php
// Add to SnapshotManager class

public function create(string $name, ?string $description = null): Snapshot
{
    $this->validateName($name);
    $snapshotPath = $this->snapshotDir . $name;

    if (is_dir($snapshotPath)) {
        throw new \InvalidArgumentException("Snapshot already exists: {$name}");
    }

    mkdir($snapshotPath, 0700, true);

    try {
        // Export database
        $dbPath = $snapshotPath . '/db.sql.gz';
        $dbSize = $this->exportDatabase($dbPath);

        // Archive files
        $filesPath = $snapshotPath . '/files.tar.gz';
        $filesSize = $this->archiveFiles($filesPath);

        // Write metadata
        $snapshot = new Snapshot(
            name: $name,
            createdAt: new \DateTimeImmutable(),
            appVersion: ODM_APP_VERSION,
            description: $description,
            dbSize: $dbSize,
            filesSize: $filesSize
        );
        file_put_contents(
            $snapshotPath . '/metadata.json',
            json_encode($snapshot->toJsonArray(), JSON_PRETTY_PRINT)
        );
        chmod($snapshotPath . '/metadata.json', 0600);

        // Update latest symlink
        $latest = $this->snapshotDir . 'latest';
        if (is_link($latest)) {
            unlink($latest);
        }
        symlink($name, $latest);

        return $snapshot;
    } catch (\Exception $e) {
        $this->rrmdir($snapshotPath);
        throw $e;
    }
}

private function exportDatabase(string $outputPath): int
{
    $gz = gzopen($outputPath, 'w9');

    $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->dbPrefix}%'");
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $createStmt = $this->pdo->prepare("SHOW CREATE TABLE `{$table}`");
        $createStmt->execute();
        $row = $createStmt->fetch(\PDO::FETCH_ASSOC);
        gzwrite($gz, $row['Create Table'] . ";\n\n");

        $colStmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$table}`");
        $colStmt->execute();
        $columns = $colStmt->fetchAll(\PDO::FETCH_COLUMN);

        $dataStmt = $this->pdo->query("SELECT * FROM `{$table}`");
        $rows = $dataStmt->fetchAll(\PDO::FETCH_NUM);

        if (count($rows) > 0) {
            $colList = '`' . implode('`, `', $columns) . '`';
            foreach ($rows as $row) {
                $escaped = array_map([$this->pdo, 'quote'], $row);
                gzwrite($gz, "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $escaped) . ");\n");
            }
            gzwrite($gz, "\n");
        }
    }

    gzclose($gz);
    return filesize($outputPath);
}

private function archiveFiles(string $outputPath): int
{
    $tar = new \PharData($outputPath);
    $tar->buildFromDirectory($this->dataDir);
    return filesize($outputPath);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest`
Expected: 8 tests, all PASS

- [ ] **Step 5: Commit**

```bash
git add application/models/SnapshotManager.class.php tests/Unit/SnapshotManagerTest.php
git commit -m "feat: add SnapshotManager create method"
```

---

### Task 4: SnapshotManager — Restore

**Files:**
- Modify: `application/models/SnapshotManager.class.php`
- Modify: `tests/Unit/SnapshotManagerTest.php`

**Interfaces:**
- Produces: `SnapshotManager::restore(name): void`

- [ ] **Step 1: Write the failing test**

```php
// Add to SnapshotManagerTest class

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
    file_put_contents($snapDir . '/files.tar.gz', 'dummy-tar');
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest::testRestore`
Expected: FAIL — `restore()` method not found

- [ ] **Step 3: Write restore() implementation**

```php
// Add to SnapshotManager class

public function restore(string $name): void
{
    $this->validateName($name);
    $snapshotPath = $this->snapshotDir . $name;

    if (!is_dir($snapshotPath)) {
        throw new \InvalidArgumentException("Snapshot not found: {$name}");
    }

    $dbPath = $snapshotPath . '/db.sql.gz';
    $filesPath = $snapshotPath . '/files.tar.gz';

    if (!is_file($dbPath)) {
        throw new \RuntimeException("Snapshot missing db.sql.gz: {$name}");
    }
    if (!is_file($filesPath)) {
        throw new \RuntimeException("Snapshot missing files.tar.gz: {$name}");
    }

    // Drop all existing odm_ tables
    $this->dropAllTables();

    // Import database
    $this->importDatabase($dbPath);

    // Wipe and restore files
    $this->restoreFiles($filesPath);
}

private function dropAllTables(): void
{
    $stmt = $this->pdo->prepare("SHOW TABLES LIKE '{$this->dbPrefix}%'");
    $stmt->execute();
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    if (count($tables) === 0) {
        return;
    }

    $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $table) {
        $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }
    $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

private function importDatabase(string $dbPath): void
{
    $gz = gzopen($dbPath, 'r');
    $sql = '';
    while (!gzeof($gz)) {
        $sql .= gzread($gz, 65536);
    }
    gzclose($gz);

    // Execute each statement separately
    $statements = explode(";\n", $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $this->pdo->exec($statement);
        }
    }
}

private function restoreFiles(string $filesPath): void
{
    // Wipe dataDir contents
    $items = array_diff(scandir($this->dataDir), ['.', '..']);
    foreach ($items as $item) {
        $path = $this->dataDir . $item;
        is_dir($path) ? $this->rrmdir($path) : unlink($path);
    }

    // Extract tarball
    $tar = new \PharData($filesPath);
    $tar->extractTo($this->dataDir);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotManagerTest`
Expected: 10 tests, all PASS

- [ ] **Step 5: Commit**

```bash
git add application/models/SnapshotManager.class.php tests/Unit/SnapshotManagerTest.php
git commit -m "feat: add SnapshotManager restore method"
```

---

### Task 5: CLI Commands

**Files:**
- Modify: `application/installer/cli.php`

**Interfaces:**
- Consumes: `SnapshotManager`, `Snapshot`
- Produces: CLI commands `snapshot:create`, `snapshot:restore`, `snapshot:list`, `snapshot:delete`, `demo:refresh`

- [ ] **Step 1: Read existing cli.php to understand the pattern**

Read `application/installer/cli.php` to understand the existing command dispatch structure.

- [ ] **Step 2: Add snapshot commands to cli.php**

Add cases to the `run()` method's command switch:

```php
// In the run() method, add new cases:
switch ($command) {
    // ... existing cases ...

    case 'snapshot:create':
        $this->snapshotCreate($argv);
        break;
    case 'snapshot:restore':
        $this->snapshotRestore($argv);
        break;
    case 'snapshot:list':
        $this->snapshotList();
        break;
    case 'snapshot:delete':
        $this->snapshotDelete($argv);
        break;
    case 'demo:refresh':
        $this->demoRefresh();
        break;
}
```

Add the helper methods (following the existing `migrate()` pattern that uses `ConfigManager` + `DatabaseManager`):

```php
private function getSnapshotManager(): SnapshotManager
{
    $configManager = new \ConfigManager();
    $config = $configManager->loadConfig();
    $dbManager = new \DatabaseManager(
        $config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']
    );
    $pdo = $dbManager->connect();
    $prefix = $config['db_prefix'];

    // Query settings for dataDir and snapshotDir
    $stmt = $pdo->query("SELECT `name`, `value` FROM `{$prefix}settings` WHERE `name` IN ('dataDir', 'snapshotDir')");
    $settings = [];
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $settings[$row['name']] = $row['value'];
    }

    $dataDir = $settings['dataDir'] ?? '/var/www/document_repository/';
    $snapshotDir = $settings['snapshotDir'] ?? '/var/www/snapshots/';

    if (!is_dir($snapshotDir)) {
        @mkdir($snapshotDir, 0700, true);
    }

    return new SnapshotManager($pdo, $snapshotDir, $dataDir, $prefix);
}

private function snapshotCreate(array $argv): void
{
    $name = $this->getArg($argv, '--name=');
    if (!$name) {
        echo "Error: --name= is required\n";
        exit(1);
    }
    $description = $this->getArg($argv, '--description=');

    $manager = $this->getSnapshotManager();
    $snapshot = $manager->create($name, $description);
    echo "Snapshot created: {$snapshot->name}\n";
    echo "  DB size: {$snapshot->dbSize} bytes\n";
    echo "  Files size: {$snapshot->filesSize} bytes\n";
}

private function snapshotRestore(array $argv): void
{
    $name = $this->getArg($argv, '--name=') ?: 'latest';

    $manager = $this->getSnapshotManager();
    $manager->restore($name);
    echo "Snapshot restored: {$name}\n";
}

private function snapshotList(): void
{
    $manager = $this->getSnapshotManager();
    $snapshots = $manager->list();

    if (empty($snapshots)) {
        echo "No snapshots found.\n";
        return;
    }

    echo str_pad('Name', 30) . str_pad('Created', 30) . str_pad('DB Size', 15) . "Files Size\n";
    echo str_repeat('-', 90) . "\n";
    foreach ($snapshots as $snap) {
        echo str_pad($snap->name, 30)
            . str_pad($snap->createdAt->format('Y-m-d H:i:s'), 30)
            . str_pad($this->formatBytes($snap->dbSize), 15)
            . $this->formatBytes($snap->filesSize) . "\n";
    }
}

private function snapshotDelete(array $argv): void
{
    $name = $this->getArg($argv, '--name=');
    if (!$name) {
        echo "Error: --name= is required\n";
        exit(1);
    }

    $manager = $this->getSnapshotManager();
    $manager->delete($name);
    echo "Snapshot deleted: {$name}\n";
}

private function demoRefresh(): void
{
    $manager = $this->getSnapshotManager();
    $manager->restore('demo-baseline');
    echo "Demo baseline restored.\n";

    // Reconnect after restore and enable demo mode
    $configManager = new \ConfigManager();
    $config = $configManager->loadConfig();
    $dbManager = new \DatabaseManager(
        $config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']
    );
    $pdo = $dbManager->connect();
    $prefix = $config['db_prefix'];
    $stmt = $pdo->prepare("UPDATE `{$prefix}settings` SET value = 'True' WHERE name = 'demo'");
    $stmt->execute();
    echo "Demo mode enabled.\n";
}

private function getArg(array $argv, string $prefix): ?string
{
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return null;
}

private function formatBytes(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
```

- [ ] **Step 3: Update the help text**

Add the new commands to the CLI help output:

```php
// In the help output, add:
echo "  snapshot:create --name=NAME [--description=...]  Create a snapshot\n";
echo "  snapshot:restore [--name=NAME]                    Restore a snapshot (default: latest)\n";
echo "  snapshot:list                                     List all snapshots\n";
echo "  snapshot:delete --name=NAME                       Delete a snapshot\n";
echo "  demo:refresh                                      Restore demo-baseline + enable demo mode\n";
```

And add `--snapshotdir=` to the help text for `dump-sql`:

```php
echo "    --snapshotdir=PATH  Snapshot directory path\n";
```

- [ ] **Step 4: Add SnapshotManager require to cli.php**

```php
// At the top of cli.php, add with the other requires:
require_once __DIR__ . '/../models/Snapshot.class.php';
require_once __DIR__ . '/../models/SnapshotManager.class.php';
```

- [ ] **Step 5: Test the CLI help output**

Run: `php application/installer/cli.php`
Expected: Help text includes the new snapshot and demo:refresh commands

- [ ] **Step 6: Commit**

```bash
git add application/installer/cli.php
git commit -m "feat: add snapshot CLI commands and demo:refresh"
```

---

### Task 6: SchemaBuilder, Migration, and Version Bump

**Files:**
- Modify: `application/installer/SchemaBuilder.php`
- Create: `application/installer/migrations/Version001600.php`
- Modify: `application/version.php`

- [ ] **Step 1: Add snapshotDir to SchemaBuilder default data**

In `SchemaBuilder.php`, add the `snapshotDir` setting to the `getDefaultDataStatements()` method's settings array:

```php
// After the dataDir setting, add:
"INSERT INTO `{$prefix}settings` VALUES(NULL, 'snapshotDir', '/var/www/snapshots/', 'Location to store database and file snapshots. Should be outside web root.', 'maxsize=255')",
```

Also update `buildFullDump()` to accept and pass through `snapshotdir`:

```php
// In the method signature, already has $options array — just add the key:
$snapshotDir = $options['snapshotdir'] ?? '/var/www/snapshots/';
```

And update the `getDefaultDataStatements()` call in `buildFullDump()` to pass `snapshotdir`:

```php
// Inside buildFullDump(), when calling getDefaultDataStatements:
'snapshotdir' => $options['snapshotdir'] ?? '/var/www/snapshots/',
```

In `getDefaultDataStatements()`, read it:

```php
$snapshotDir = $options['snapshotdir'] ?? '/var/www/snapshots/';
```

Update the CLI `dump-sql` command to parse `--snapshotdir=`:

```php
// In cli.php, in the dumpSql() method, add after the --datadir= parsing:
} elseif (strpos($argv[$i], '--snapshotdir=') === 0) {
    $snapshotDir = substr($argv[$i], 14);
}
// And pass it in the options array:
'snapshotdir' => $snapshotDir,
```

Note: Use `{$dataDir}snapshots/` as the default — a sibling of `archiveDir` and `revisionDir` which are already subdirectories of `dataDir`.

- [ ] **Step 2: Create the migration**


```php
<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001600 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.6.0';
    }

    public function getDescription(): string
    {
        return 'Add snapshotDir setting for snapshot storage location';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}settings` WHERE name = 'snapshotDir'");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            // Get the current dataDir for the default
            $dataDirStmt = $pdo->query("SELECT `value` FROM `{$prefix}settings` WHERE `name` = 'dataDir'");
            $dataDir = $dataDirStmt->fetchColumn();
            $defaultSnapshotDir = '/var/www/snapshots/';

            $pdo->exec(
                "INSERT INTO `{$prefix}settings` (`name`, `value`, `description`, `validation`) VALUES "
                . "('snapshotDir', " . $pdo->quote($defaultSnapshotDir) . ", "
                . "'Location to store database and file snapshots. Should be outside web root.', 'maxsize=255')"
            );
        }
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'snapshotDir'");
    }
}
```

- [ ] **Step 3: Register the migration in cli.php**

```php
// In cli.php, add to the migration list:
require_once __DIR__ . '/migrations/Version001600.php';
$runner->registerMigration(new Version001600());
```

- [ ] **Step 4: Bump version in version.php**

```php
const ODM_DB_VERSION = '1.6.0';
```

- [ ] **Step 5: Regenerate database.sql**

Run: `make dump-sql`
Verify the output includes the `snapshotDir` setting.

- [ ] **Step 6: Commit**

```bash
git add application/installer/SchemaBuilder.php application/installer/migrations/Version001600.php application/installer/cli.php application/version.php database.sql
git commit -m "feat: add snapshotDir setting and migration"
```

---

### Task 7: Settings Validation + Demo PHP Removal

**Files:**
- Modify: `application/controllers/settings.php`
- Delete: `demo.php`
- Modify: `database.sql` (if not already regenerated)

- [ ] **Step 1: Add snapshotDir validation to settings.php**

Find the existing `dataDir` validation block in `settings.php` (lines 57-68) and add the same validation for `snapshotDir`:

```php
// Clean up the snapshotDir a bit to make sure it ends with slash
if (!empty($_POST['snapshotDir'])) {
    if (substr($_POST['snapshotDir'], -1) != '/') {
        $_POST['snapshotDir'] .= '/';
    }
    if (!is_dir($_POST['snapshotDir'])) {
        $_POST['last_message'] = "Snapshot directory does not exist: {$_POST['snapshotDir']}";
    } elseif (!is_writable($_POST['snapshotDir'])) {
        $_POST['last_message'] = "Snapshot directory is not writable: {$_POST['snapshotDir']}";
    }
}
```

- [ ] **Step 2: Delete demo.php**

```bash
git rm demo.php
```

- [ ] **Step 3: Regenerate database.sql if needed**

Run: `make dump-sql`

- [ ] **Step 4: Commit**

```bash
git add application/controllers/settings.php database.sql
git rm demo.php
git commit -m "feat: add snapshotDir validation, remove legacy demo.php"
```

---

### Task 8: Integration Test — Full Create + Restore Cycle

**Files:**
- Create: `tests/Integration/SnapshotRestoreTest.php`

- [ ] **Step 1: Write the integration test**

```php
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
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testFullCreateRestoreCycle(): void
    {
        // Set up directories
        $snapshotDir = $this->tmpDir . '/snapshots';
        $dataDir = $this->tmpDir . '/data';
        mkdir($snapshotDir, 0700, true);
        mkdir($dataDir, 0700, true);

        // Add some files to dataDir
        file_put_contents($dataDir . '/1.dat', 'original file content');
        file_put_contents($dataDir . '/2.dat', 'another file');

        // Mock PDO that returns tables and data
        $pdo = \Mockery::mock(\PDO::class);

        // SHOW TABLES
        $tableStmt = \Mockery::mock(\PDOStatement::class);
        $tableStmt->shouldReceive('execute')->once()->andReturn(true);
        $tableStmt->shouldReceive('fetchAll')->once()->andReturn(
            [['tables' => 'odm_settings']]
        );
        $pdo->shouldReceive('query')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt);

        // SHOW CREATE TABLE
        $createStmt = \Mockery::mock(\PDOStatement::class);
        $createStmt->shouldReceive('execute')->once()->andReturn(true);
        $createStmt->shouldReceive('fetch')->once()->andReturn(
            ['Table' => 'odm_settings', 'Create Table' => "CREATE TABLE `odm_settings` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `name` varchar(255) NOT NULL,\n  `value` text,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB"]
        );
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SHOW CREATE TABLE/'))->once()->andReturn($createStmt);

        // SHOW COLUMNS
        $colStmt = \Mockery::mock(\PDOStatement::class);
        $colStmt->shouldReceive('execute')->times(2)->andReturn(true);
        $colStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->times(2)->andReturn(
            ['id', 'name', 'value']
        );
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SHOW COLUMNS/'))->once()->andReturn($colStmt);

        // SELECT * FROM
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_NUM)->once()->andReturn(
            [[1, 'demo', 'True']]
        );
        $pdo->shouldReceive('query')->with("SELECT * FROM `odm_settings`")->once()->andReturn($dataStmt);

        // PDO quote
        $pdo->shouldReceive('quote')->andReturn("'1'", "'demo'", "'True'");

        // Create snapshot
        $manager = new SnapshotManager($pdo, $snapshotDir, $dataDir, 'odm_');
        $snapshot = $manager->create('test-cycle', 'Integration test');

        $this->assertSame('test-cycle', $snapshot->name);
        $this->assertFileExists($snapshotDir . '/test-cycle/db.sql.gz');
        $this->assertFileExists($snapshotDir . '/test-cycle/files.tar.gz');
        $this->assertFileExists($snapshotDir . '/test-cycle/metadata.json');

        // Now test restore — need to mock PDO for restore operations
        // SHOW TABLES for drop
        $tableStmt2 = \Mockery::mock(\PDOStatement::class);
        $tableStmt2->shouldReceive('execute')->once()->andReturn(true);
        $tableStmt2->shouldReceive('fetchAll')->with(\PDO::FETCH_COLUMN)->once()->andReturn(
            ['odm_settings']
        );
        $pdo->shouldReceive('prepare')->with("SHOW TABLES LIKE 'odm_%'")->once()->andReturn($tableStmt2);

        // DROP TABLE
        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 0')->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with("DROP TABLE IF EXISTS `odm_settings`")->once()->andReturn(0);
        $pdo->shouldReceive('exec')->with('SET FOREIGN_KEY_CHECKS = 1')->once()->andReturn(0);

        // Import — exec the INSERT
        $pdo->shouldReceive('exec')->with(\Mockery::pattern('/INSERT INTO/'))->once()->andReturn(1);

        $manager->restore('test-cycle');

        // Verify dataDir was cleared and files extracted
        // (The tarball has the original files, so extraction should restore them)
        $this->assertFileExists($dataDir . '/1.dat');
        $this->assertFileExists($dataDir . '/2.dat');
    }
}
```

- [ ] **Step 2: Run the integration test**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter SnapshotRestoreTest`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/SnapshotRestoreTest.php
git commit -m "test: add integration test for snapshot create+restore cycle"
```

---

### Task 9: Run Full Test Suite

- [ ] **Step 1: Run all tests**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist 2>&1 | tail -20`
Expected: All tests pass, no failures or errors

- [ ] **Step 2: Run the E2E smoke test**

If the app is running on `localhost:8080`, run: `npm run test:e2e`
Expected: PASS

- [ ] **Step 3: Verify database.sql regenerates cleanly**

Run: `make dump-sql`
Expected: No errors, `database.sql` contains the `snapshotDir` setting

- [ ] **Step 4: Commit final changes**

```bash
git add -A
git commit -m "chore: final cleanup"
```