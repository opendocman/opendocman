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