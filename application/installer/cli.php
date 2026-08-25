#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
    die('This script must be run from the command line.');
}

require_once __DIR__ . '/ConfigManager.php';
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/MigrationRunner.php';
require_once __DIR__ . '/SchemaBuilder.php';
require_once __DIR__ . '/MigrationLoader.php';
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
                $this->migrate($argv);
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
            case 'mail:poll':
                $this->mailPoll();
                break;
            default:
                $this->printUsage();
                break;
        }
    }

    private function dumpSql(array $argv): void
    {
        require_once __DIR__ . '/../version.php';

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
            'force_password_change' => true,
            'datadir' => $dataDir,
            'snapshotdir' => $snapshotDir,
        ]);
    }

    private function migrate(array $argv): void
    {
        $targetVersion = null;
        for ($i = 2; $i < count($argv); $i++) {
            if (strpos($argv[$i], '--target=') === 0) {
                $targetVersion = substr($argv[$i], 9);
            }
        }

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
        $runner->registerMigrations(MigrationLoader::getAll());

        $currentVersion = $dbManager->getDbVersion($prefix);
        if ($currentVersion !== null) {
            $runner->seedAppliedUpTo($currentVersion);
        }

        $action = 'UP';
        $destinationVersion = null;
        if ($targetVersion !== null && $currentVersion !== null) {
            $cmp = version_compare($currentVersion, $targetVersion);
            if ($cmp < 0) {
                $results = $runner->migrateTo($targetVersion);
                $destinationVersion = $targetVersion;
            } elseif ($cmp > 0) {
                $action = 'DOWN';
                $results = $runner->rollbackTo($targetVersion);
                $destinationVersion = $targetVersion;
            } else {
                echo "Already at version {$targetVersion}.\n";
                return;
            }
        } else {
            $results = $runner->run();
            if (!empty($results)) {
                $destinationVersion = end($results)['version'];
            }
        }

        if (empty($results)) {
            echo "No pending migrations.\n";
            return;
        }

        if ($destinationVersion !== null) {
            echo "[{$action}] v{$destinationVersion}\n";
        } else {
            foreach ($results as $result) {
                $status = $result['status'] === 'success' ? 'OK' : 'ERROR';
                echo "[{$status}] v{$result['version']}";
                if ($result['message']) {
                    echo " - {$result['message']}";
                }
                echo "\n";
            }
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
        $runner->registerMigrations(MigrationLoader::getAll());

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
        if (!$configManager->configExists()) {
            fwrite(STDERR, "Error: No config file found. Run setup-config first.\n");
            exit(1);
        }
        $configManager->loadConfig();
        require_once __DIR__ . '/../version.php';

        $dbManager = new \DatabaseManager(
            APP_DB_HOST,
            APP_DB_NAME,
            APP_DB_USER,
            APP_DB_PASS
        );

        try {
            $pdo = $dbManager->connect();
        } catch (Exception $e) {
            // Database might not exist — try to create it
            $noDbManager = new \DatabaseManager(
                APP_DB_HOST,
                'mysql',
                APP_DB_USER,
                APP_DB_PASS
            );
            try {
                $noDbPdo = $noDbManager->connect();
                $noDbPdo->exec("CREATE DATABASE IF NOT EXISTS `" . APP_DB_NAME . "`");
                $pdo = $dbManager->connect();
            } catch (Exception $e2) {
                fwrite(STDERR, "Error: Database connection failed - " . $e->getMessage() . "\n");
                exit(1);
            }
        }

        $prefix = $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_';

        $dataDir = '/var/www/document_repository/';
        $snapshotDir = null;

        try {
            $stmt = $pdo->query("SELECT `name`, `value` FROM `{$prefix}settings` WHERE `name` IN ('dataDir', 'snapshotDir')");
            $settings = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $settings[$row['name']] = $row['value'];
            }
            $dataDir = $settings['dataDir'] ?? $dataDir;
            $snapshotDir = $settings['snapshotDir'] ?? null;
        } catch (\Exception $e) {
            // odm_settings table doesn't exist yet — use defaults
        }

        if ($snapshotDir === null) {
            // Check SchemaBuilder default first, then fall back to temp dir
            $snapshotDir = '/var/www/snapshots/';
            if (!is_dir($snapshotDir)) {
                $snapshotDir = sys_get_temp_dir() . '/odm_snapshots/';
            }
        }

        fwrite(STDERR, "Snapshot directory: {$snapshotDir}\n");

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

        try {
            $snapshot = $manager->create($name, $description);
            echo "Snapshot created: {$snapshot->name}\n";
            echo "  DB size: {$snapshot->dbSize} bytes\n";
            echo "  Files size: {$snapshot->filesSize} bytes\n";
        } catch (\InvalidArgumentException $e) {
            echo "Error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function snapshotRestore(array $argv): void
    {
        $name = $this->getArg($argv, '--name=') ?: 'latest';

        $manager = $this->getSnapshotManager();
        try {
            $manager->restore($name);
            echo "Snapshot restored: {$name}\n";
            $this->migrate();
        } catch (\InvalidArgumentException $e) {
            echo "Error: {$e->getMessage()}\n";
            exit(1);
        }
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
        try {
            $manager->delete($name);
            echo "Snapshot deleted: {$name}\n";
        } catch (\InvalidArgumentException $e) {
            echo "Error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function demoRefresh(): void
    {
        $manager = $this->getSnapshotManager();
        $manager->restore('demo-baseline');
        echo "Demo baseline restored.\n";

        $configManager = new \ConfigManager();
        if (!$configManager->configExists()) {
            fwrite(STDERR, "Error: No config file found. Run setup-config first.\n");
            exit(1);
        }
        $configManager->loadConfig();

        $dbManager = new \DatabaseManager(
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
        $stmt = $pdo->prepare("UPDATE `{$prefix}settings` SET value = 'True' WHERE name = 'demo'");
        $stmt->execute();
        echo "Demo mode enabled.\n";

        $this->migrate([]);
    }

    private function mailPoll(): void
    {
        require_once __DIR__ . '/../version.php';
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/../controllers/helpers/functions.php';
        require_once __DIR__ . '/../models/EmailInbox.class.php';
        require_once __DIR__ . '/../models/EmailIngest.class.php';
        require_once __DIR__ . '/../models/Settings.class.php';
        require_once __DIR__ . '/../models/FileTypes.class.php';
        require_once __DIR__ . '/../models/Document.class.php';
        require_once __DIR__ . '/../models/TextExtractor.class.php';
        require_once __DIR__ . '/../models/TextExtractorFactory.class.php';

        $configManager = new ConfigManager();
        if (!$configManager->configExists()) {
            fwrite(STDERR, "Error: No config file found. Run setup-config first.\n");
            exit(1);
        }
        $configManager->loadConfig();

        $dbManager = new DatabaseManager(APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
        try {
            $pdo = $dbManager->connect();
        } catch (Exception $e) {
            fwrite(STDERR, "Error: Database connection failed - " . $e->getMessage() . "\n");
            exit(1);
        }

        (new Settings($pdo))->load();
        (new FileTypes($pdo))->load();

        $c = $GLOBALS['CONFIG'];
        if (($c['mail_enabled'] ?? 'False') !== 'True') {
            fwrite(STDERR, "Mail ingest is disabled (mail_enabled is not True).\n");
            return;
        }

        try {
            $inbox = new EmailInbox([
                'host' => $c['mail_host'] ?? '',
                'port' => $c['mail_port'] ?? 993,
                'protocol' => $c['mail_protocol'] ?? 'imap',
                'encryption' => $c['mail_encryption'] ?? 'ssl',
                'user' => $c['mail_user'] ?? '',
                'pass' => $c['mail_pass'] ?? '',
                'folder' => $c['mail_folder'] ?? 'INBOX',
                'validate_cert' => ($c['mail_validate_cert'] ?? 'True') !== 'False',
            ]);

            $ingest = new EmailIngest($pdo, $c);

            $messages = $inbox->fetchMessages();
            $totals = ['created' => 0, 'rejected' => 0, 'errors' => 0];
            foreach ($messages as $message) {
                try {
                    $stats = $ingest->process($message);
                    foreach (array_keys($totals) as $k) {
                        $totals[$k] += $stats[$k];
                    }
                    $inbox->markRead($message->id);
                    if (($c['mail_delete'] ?? 'False') === 'True') {
                        $inbox->delete($message->id);
                    }
                } catch (Throwable $e) {
                    fwrite(STDERR, "Error: Failed to process message " . $message->id . " - " . $e->getMessage() . "\n");
                    $totals['errors']++;
                    continue;
                }
            }
            $inbox->cleanup();

            echo "mail:poll complete — created {$totals['created']}, rejected {$totals['rejected']}, errors {$totals['errors']}\n";
        } catch (EmailInboxException $e) {
            fwrite(STDERR, "Error: Mailbox error - " . $e->getMessage() . "\n");
            exit(1);
        } catch (Exception $e) {
            fwrite(STDERR, "Error: Mail poll failed - " . $e->getMessage() . "\n");
            exit(1);
        }
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
        echo "    --admin-password=Admin password in plaintext (default: admin)\n";
        echo "    --datadir=PATH      Data directory path\n";
        echo "    --snapshotdir=PATH  Snapshot directory path\n";
        echo "  migrate               Run pending migrations\n";
        echo "    --target=VERSION  Migrate up to or rollback down to a specific version\n";
        echo "  status                Show migration status\n";
        echo "  snapshot:create --name=NAME [--description=...]  Create a snapshot\n";
        echo "  snapshot:restore [--name=NAME]                    Restore a snapshot (default: latest)\n";
        echo "  snapshot:list                                     List all snapshots\n";
        echo "  snapshot:delete --name=NAME                       Delete a snapshot\n";
        echo "  demo:refresh                                      Restore demo-baseline + enable demo mode\n";
        echo "  mail:poll                                         Poll the inbox and ingest email documents\n";
    }
}

$cli = new CliCommand();
$cli->run($argv);