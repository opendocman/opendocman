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