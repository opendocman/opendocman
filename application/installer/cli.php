#!/usr/bin/env php
<?php

require_once __DIR__ . '/ConfigManager.php';
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/MigrationRunner.php';
require_once __DIR__ . '/SchemaBuilder.php';
require_once __DIR__ . '/migrations/MigrationInterface.php';
require_once __DIR__ . '/migrations/Version001000.php';
require_once __DIR__ . '/migrations/Version0011rc2.php';
require_once __DIR__ . '/migrations/Version001100.php';
require_once __DIR__ . '/migrations/Version0012p1.php';
require_once __DIR__ . '/migrations/Version0012p3.php';
require_once __DIR__ . '/migrations/Version001240.php';
require_once __DIR__ . '/migrations/Version001252.php';
require_once __DIR__ . '/migrations/Version001256.php';
require_once __DIR__ . '/migrations/Version001257.php';
require_once __DIR__ . '/migrations/Version001261.php';
require_once __DIR__ . '/migrations/Version001262.php';
require_once __DIR__ . '/migrations/Version001263.php';
require_once __DIR__ . '/migrations/Version001280.php';
require_once __DIR__ . '/migrations/Version001290.php';
require_once __DIR__ . '/migrations/Version001300.php';
require_once __DIR__ . '/migrations/Version001400.php';
require_once __DIR__ . '/migrations/Version001401.php';
require_once __DIR__ . '/migrations/Version001402.php';
require_once __DIR__ . '/migrations/Version001500.php';
require_once __DIR__ . '/migrations/Version001501.php';
require_once __DIR__ . '/migrations/Version001600.php';
require_once __DIR__ . '/../models/Snapshot.class.php';
require_once __DIR__ . '/../models/SnapshotManager.class.php';

class CliCommand
{
    public function run(array $argv): void
    {
        if (count($argv) < 2) {
            $this->printUsage();
            return;
        }

        $command = $argv[1];

        switch ($command) {
            case 'dump-sql':
                $this->dumpSql($argv);
                break;
            case 'migrate':
                $this->migrate();
                break;
            case 'status':
                $this->status();
                break;
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
            default:
                $this->printUsage();
                break;
        }
    }

    private function dumpSql(array $argv): void
    {
        $prefix = 'odm_';
        $adminPassword = 'admin';
        $dataDir = '/var/www/document_repository/';
        $snapshotDir = '/var/www/snapshots/';

        for ($i = 2; $i < count($argv); $i++) {
            if (strpos($argv[$i], '--prefix=') === 0) {
                $prefix = substr($argv[$i], 9);
            } elseif (strpos($argv[$i], '--admin-password=') === 0) {
                $adminPassword = substr($argv[$i], 17);
            } elseif (strpos($argv[$i], '--datadir=') === 0) {
                $dataDir = substr($argv[$i], 10);
            } elseif (strpos($argv[$i], '--snapshotdir=') === 0) {
                $snapshotDir = substr($argv[$i], 14);
            }
        }

        $builder = new SchemaBuilder();
        echo $builder->buildFullDump($prefix, [
            'admin_password' => $adminPassword,
            'datadir' => $dataDir,
            'snapshotdir' => $snapshotDir,
        ]);
    }

    private function migrate(): void
    {
        $configManager = new ConfigManager();
        if (!$configManager->configExists()) {
            fwrite(STDERR, "Error: No config file found. Run setup-config first.\n");
            exit(1);
        }
        $configManager->loadConfig();

        $dbManager = new DatabaseManager(
            APP_DB_HOST,
            APP_DB_NAME,
            APP_DB_USER,
            APP_DB_PASS
        );

        try {
            $pdo = $dbManager->connect();
        } catch (Exception $e) {
            fwrite(STDERR, "Error: Database connection failed - " . $e->getMessage() . "\n");
            exit(1);
        }

        $prefix = $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_';
        $runner = new MigrationRunner($pdo, $prefix);
        $runner->registerMigrations([
            new Version001000(),
            new Version0011rc2(),
            new Version001100(),
            new Version0012p1(),
            new Version0012p3(),
            new Version001240(),
            new Version001252(),
            new Version001256(),
            new Version001257(),
            new Version001261(),
            new Version001262(),
            new Version001263(),
            new Version001280(),
            new Version001290(),
            new Version001300(),
            new Version001400(),
            new Version001401(),
            new Version001402(),
            new Version001500(),
            new Version001501(),
            new Version001600(),
        ]);

        $currentVersion = $dbManager->getDbVersion($prefix);
        if ($currentVersion !== null) {
            $runner->seedAppliedUpTo($currentVersion);
        }

        $results = $runner->run();
        if (empty($results)) {
            echo "No pending migrations.\n";
            return;
        }

        foreach ($results as $result) {
            $status = $result['status'] === 'success' ? 'OK' : 'ERROR';
            echo "[{$status}] v{$result['version']}";
            if ($result['message']) {
                echo " - {$result['message']}";
            }
            echo "\n";
        }
    }

    private function status(): void
    {
        $configManager = new ConfigManager();
        if (!$configManager->configExists()) {
            fwrite(STDERR, "Error: No config file found.\n");
            exit(1);
        }
        $configManager->loadConfig();

        $dbManager = new DatabaseManager(
            APP_DB_HOST,
            APP_DB_NAME,
            APP_DB_USER,
            APP_DB_PASS
        );

        try {
            $pdo = $dbManager->connect();
        } catch (Exception $e) {
            fwrite(STDERR, "Error: Database connection failed - " . $e->getMessage() . "\n");
            exit(1);
        }

        $prefix = $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_';
        $runner = new MigrationRunner($pdo, $prefix);
        $runner->registerMigrations([
            new Version001000(),
            new Version0011rc2(),
            new Version001100(),
            new Version0012p1(),
            new Version0012p3(),
            new Version001240(),
            new Version001252(),
            new Version001256(),
            new Version001257(),
            new Version001261(),
            new Version001262(),
            new Version001263(),
            new Version001280(),
            new Version001290(),
            new Version001300(),
            new Version001400(),
            new Version001401(),
            new Version001402(),
            new Version001500(),
            new Version001501(),
            new Version001600(),
        ]);

        $rows = $runner->status();
        echo str_pad('Version', 16) . str_pad('Applied', 8) . "Name\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($rows as $row) {
            echo str_pad($row['version'], 16)
                . str_pad($row['applied'] ? 'YES' : 'NO', 8)
                . $row['name'] . "\n";
        }
    }

    private function getSnapshotManager(): SnapshotManager
    {
        $configManager = new \ConfigManager();
        $config = $configManager->loadConfig();
        $dbManager = new \DatabaseManager(
            $config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']
        );
        $pdo = $dbManager->connect();
        $prefix = $config['db_prefix'];

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

    private function printUsage(): void
    {
        echo "OpenDocMan Installer CLI\n";
        echo "Usage: php cli.php <command> [options]\n\n";
        echo "Commands:\n";
        echo "  dump-sql              Generate database.sql from SchemaBuilder\n";
        echo "    --prefix=PREFIX     Table prefix (default: odm_)\n";
        echo "    --admin-password=MD5 Admin password hash (default: md5('admin'))\n";
        echo "    --datadir=PATH      Data directory path\n";
        echo "    --snapshotdir=PATH  Snapshot directory path\n";
        echo "  migrate               Run pending migrations\n";
        echo "  status                Show migration status\n";
        echo "  snapshot:create --name=NAME [--description=...]  Create a snapshot\n";
        echo "  snapshot:restore [--name=NAME]                    Restore a snapshot (default: latest)\n";
        echo "  snapshot:list                                     List all snapshots\n";
        echo "  snapshot:delete --name=NAME                       Delete a snapshot\n";
        echo "  demo:refresh                                      Restore demo-baseline + enable demo mode\n";
    }
}

$cli = new CliCommand();
$cli->run($argv);