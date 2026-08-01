<?php

class MigrationRunner
{
    private PDO $pdo;
    private string $prefix;
    private array $migrations = [];

    public function __construct(PDO $pdo, string $prefix)
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function registerMigration(MigrationInterface $migration): void
    {
        $this->migrations[] = $migration;
    }

    public function registerMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            $this->registerMigration($migration);
        }
    }

    public function ensureTrackingTable(): void
    {
        $table = $this->prefix . 'migrations';
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `version`     VARCHAR(16) NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `executed_at` DATETIME NOT NULL,
                `batch`       INT UNSIGNED NOT NULL DEFAULT 1,
                PRIMARY KEY (`version`)
            ) ENGINE = MYISAM
        ");
    }

    public function getAppliedVersions(): array
    {
        $table = $this->prefix . 'migrations';
        try {
            $stmt = $this->pdo->query("SELECT version FROM `{$table}` ORDER BY batch ASC, version ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getPendingMigrations(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->getAppliedVersions();

        usort($this->migrations, function ($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        return array_filter($this->migrations, function ($m) use ($applied) {
            return !in_array($m->getVersion(), $applied, true);
        });
    }

    public function run(): array
    {
        $results = [];
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            return $results;
        }

        $batch = $this->getNextBatch();
        $table = $this->prefix . 'migrations';
        $odmsysTable = $this->prefix . 'odmsys';
        $latestVersion = '';

        foreach ($pending as $migration) {
            $version = $migration->getVersion();
            try {
                $migration->up($this->pdo, $this->prefix);

                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$table}` (`version`, `name`, `executed_at`, `batch`)
                    VALUES (:version, :name, NOW(), :batch)
                ");
                $stmt->execute([
                    ':version' => $version,
                    ':name' => $migration->getDescription(),
                    ':batch' => $batch,
                ]);

                $latestVersion = $version;
                $results[] = ['version' => $version, 'status' => 'success', 'message' => ''];
            } catch (Exception $e) {
                $results[] = ['version' => $version, 'status' => 'error', 'message' => $e->getMessage()];
                break;
            }
        }

        if ($latestVersion !== '') {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE `{$odmsysTable}` SET sys_value = :version WHERE sys_name = 'version'
                ");
                $stmt->execute([':version' => $latestVersion]);
            } catch (PDOException $e) {
            }
        }

        return $results;
    }

    /**
     * Apply pending migrations up to (and including) the target version.
     */
    public function migrateTo(string $targetVersion): array
    {
        $pending = $this->getPendingMigrations();

        $toApply = array_filter($pending, function ($m) use ($targetVersion) {
            return version_compare($m->getVersion(), $targetVersion, '<=');
        });

        if (empty($toApply)) {
            return [];
        }

        $results = [];
        $batch = $this->getNextBatch();
        $table = $this->prefix . 'migrations';
        $odmsysTable = $this->prefix . 'odmsys';
        $latestVersion = '';

        usort($toApply, function ($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        foreach ($toApply as $migration) {
            $version = $migration->getVersion();
            try {
                $migration->up($this->pdo, $this->prefix);

                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$table}` (`version`, `name`, `executed_at`, `batch`)
                    VALUES (:version, :name, NOW(), :batch)
                ");
                $stmt->execute([
                    ':version' => $version,
                    ':name' => $migration->getDescription(),
                    ':batch' => $batch,
                ]);

                $latestVersion = $version;
                $results[] = ['version' => $version, 'status' => 'success', 'message' => ''];
            } catch (Exception $e) {
                $results[] = ['version' => $version, 'status' => 'error', 'message' => $e->getMessage()];
                break;
            }
        }

        if ($latestVersion !== '') {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE `{$odmsysTable}` SET sys_value = :version WHERE sys_name = 'version'
                ");
                $stmt->execute([':version' => $latestVersion]);
            } catch (PDOException $e) {
            }
        }

        return $results;
    }

    /**
     * Rollback applied migrations whose version exceeds the target.
     * Migrations are rolled back newest-first.
     */
    public function rollbackTo(string $targetVersion): array
    {
        $this->ensureTrackingTable();
        $applied = $this->getAppliedVersions();
        $table = $this->prefix . 'migrations';
        $odmsysTable = $this->prefix . 'odmsys';
        $results = [];

        $toRollback = array_filter($applied, function ($v) use ($targetVersion) {
            return version_compare($v, $targetVersion, '>');
        });

        usort($toRollback, function ($a, $b) {
            return version_compare($b, $a);
        });

        foreach ($toRollback as $version) {
            $migration = $this->findMigrationByVersion($version);
            if ($migration === null) {
                $results[] = ['version' => $version, 'status' => 'error', 'message' => "Migration class not found for version {$version}"];
                break;
            }

            try {
                $migration->down($this->pdo, $this->prefix);
                $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE version = :version");
                $stmt->execute([':version' => $version]);
                $results[] = ['version' => $version, 'status' => 'success', 'message' => ''];
            } catch (Exception $e) {
                $results[] = ['version' => $version, 'status' => 'error', 'message' => $e->getMessage()];
                break;
            }
        }

        $hasError = array_filter($results, function ($r) { return $r['status'] === 'error'; });
        if (!empty($results) && empty($hasError)) {
            try {
                $stmt = $this->pdo->prepare("UPDATE `{$odmsysTable}` SET sys_value = :version WHERE sys_name = 'version'");
                $stmt->execute([':version' => $targetVersion]);
            } catch (PDOException $e) {
            }
        }

        return $results;
    }

    private function getNextBatch(): int
    {
        $table = $this->prefix . 'migrations';
        try {
            $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM `{$table}`");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 1;
        }
    }

    public function status(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->getAppliedVersions();
        $appliedMap = array_flip($applied);

        usort($this->migrations, function ($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        $rows = [];
        foreach ($this->migrations as $m) {
            $rows[] = [
                'version' => $m->getVersion(),
                'name' => $m->getDescription(),
                'applied' => isset($appliedMap[$m->getVersion()]),
            ];
        }
        return $rows;
    }

    /**
     * Seed the tracking table with all registered migrations up to the given version.
     * This handles the case where the database was created at a version higher than
     * older migrations — those schema changes are already applied but not tracked.
     */
    public function seedAppliedUpTo(string $currentVersion): void
    {
        $this->ensureTrackingTable();
        $applied = $this->getAppliedVersions();
        $table = $this->prefix . 'migrations';
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO `{$table}` (`version`, `name`, `executed_at`, `batch`)
            VALUES (:version, :name, NOW(), 0)
        ");

        usort($this->migrations, function ($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        foreach ($this->migrations as $migration) {
            $version = $migration->getVersion();
            if (version_compare($version, $currentVersion, '>')) {
                break;
            }
            if (in_array($version, $applied, true)) {
                continue;
            }
            $stmt->execute([
                ':version' => $version,
                ':name' => $migration->getDescription(),
            ]);
        }
    }

    private function findMigrationByVersion(string $version): ?MigrationInterface
    {
        foreach ($this->migrations as $migration) {
            if ($migration->getVersion() === $version) {
                return $migration;
            }
        }
        return null;
    }
}