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