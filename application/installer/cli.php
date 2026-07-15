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

        for ($i = 2; $i < count($argv); $i++) {
            if (strpos($argv[$i], '--prefix=') === 0) {
                $prefix = substr($argv[$i], 9);
            } elseif (strpos($argv[$i], '--admin-password=') === 0) {
                $adminPassword = substr($argv[$i], 17);
            } elseif (strpos($argv[$i], '--datadir=') === 0) {
                $dataDir = substr($argv[$i], 10);
            }
        }

        $builder = new SchemaBuilder();
        echo $builder->buildFullDump($prefix, [
            'admin_password' => $adminPassword,
            'datadir' => $dataDir,
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

    private function printUsage(): void
    {
        echo "OpenDocMan Installer CLI\n";
        echo "Usage: php cli.php <command> [options]\n\n";
        echo "Commands:\n";
        echo "  dump-sql              Generate database.sql from SchemaBuilder\n";
        echo "    --prefix=PREFIX     Table prefix (default: odm_)\n";
        echo "    --admin-password=MD5 Admin password hash (default: md5('admin'))\n";
        echo "    --datadir=PATH      Data directory path\n";
        echo "  migrate               Run pending migrations\n";
        echo "  status                Show migration status\n";
    }
}

$cli = new CliCommand();
$cli->run($argv);