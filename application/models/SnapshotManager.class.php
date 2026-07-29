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
            $snapshotDir = rtrim($snapshotDir, '/') . '/';
            if (!is_dir($snapshotDir)) {
                if (!@mkdir($snapshotDir, 0700, true)) {
                    throw new \InvalidArgumentException(
                        "Snapshot directory does not exist and could not be created: {$snapshotDir}. "
                        . "Set 'snapshotDir' in Admin → Settings to an existing writable path."
                    );
                }
            }
            $this->pdo = $pdo;
            $this->snapshotDir = $snapshotDir;
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

            $latestPath = $this->snapshotDir . 'latest';
            if (is_link($latestPath) && readlink($latestPath) === $name) {
                unlink($latestPath);
                $snapshots = $this->list();
                if (!empty($snapshots)) {
                    symlink($snapshots[0]->name, $latestPath);
                }
            }
        }

        protected function validateName(string $name): void
        {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                throw new \InvalidArgumentException(
                    "Invalid snapshot name. Use only letters, numbers, hyphens, and underscores."
                );
            }
        }

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
                chmod($dbPath, 0600);
                chmod($filesPath, 0600);
                chmod($snapshotPath, 0700);

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
            if ($gz === false) {
                throw new \RuntimeException("Failed to open gzip output: {$outputPath}");
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE " . $this->pdo->quote($this->dbPrefix . '%'));
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $createStmt = $this->pdo->query("SHOW CREATE TABLE " . $this->quoteTable($table));
                $row = $createStmt->fetch(\PDO::FETCH_ASSOC);
                gzwrite($gz, $row['Create Table'] . ";\n\n");

                $colStmt = $this->pdo->query("SHOW COLUMNS FROM " . $this->quoteTable($table));
                $columns = $colStmt->fetchAll(\PDO::FETCH_COLUMN);

                $dataStmt = $this->pdo->query("SELECT * FROM " . $this->quoteTable($table));
                $rows = $dataStmt->fetchAll(\PDO::FETCH_NUM);

                if (count($rows) > 0) {
                    $colList = '`' . implode('`, `', $columns) . '`';
                    foreach ($rows as $row) {
                        $escaped = array_map([$this->pdo, 'quote'], $row);
                        gzwrite($gz, "INSERT INTO " . $this->quoteTable($table) . " ({$colList}) VALUES (" . implode(', ', $escaped) . ");\n");
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

            $this->pdo->beginTransaction();
            try {
                $this->dropAllTables();
                $this->importDatabase($dbPath);
                $this->pdo->commit();
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }

            $this->restoreFiles($filesPath);
        }

        private function dropAllTables(): void
        {
            $stmt = $this->pdo->query("SHOW TABLES LIKE " . $this->pdo->quote($this->dbPrefix . '%'));
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (count($tables) === 0) {
                return;
            }

            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS " . $this->quoteTable($table));
            }
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        private function importDatabase(string $dbPath): void
        {
            $gz = gzopen($dbPath, 'r');
            if ($gz === false) {
                throw new \RuntimeException("Failed to open gzip input: {$dbPath}");
            }

            $sql = '';
            while (!gzeof($gz)) {
                $sql .= gzread($gz, 65536);
            }
            gzclose($gz);

            $statements = preg_split('/;\s*\n/', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $this->pdo->exec($statement);
                }
            }
        }

        private function quoteTable(string $name): string
        {
            return '`' . str_replace('`', '``', $name) . '`';
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

        protected function rrmdir(string $dir): void
        {
            $items = array_diff(scandir($dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                if (is_link($path)) {
                    unlink($path);
                } elseif (is_dir($path)) {
                    $this->rrmdir($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}