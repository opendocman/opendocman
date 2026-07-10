<?php

class DatabaseManager
{
    private ?PDO $pdo = null;
    private string $dsn = '';
    private string $user = '';
    private string $pass = '';

    public function __construct(string $host, string $dbName, string $user, string $pass)
    {
        $this->dsn = "mysql:host={$host};dbname={$dbName};charset=utf8";
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->pdo;
    }

    public function testConnection(): array
    {
        try {
            $pdo = new PDO($this->dsn, $this->user, $this->pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo = $pdo;
            return ['success' => true, 'message' => ''];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function odmsysTableExists(string $prefix): bool
    {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}odmsys'");
            return $stmt->fetch(PDO::FETCH_NUM) !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDbVersion(string $prefix): ?string
    {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->prepare("SELECT sys_value FROM {$prefix}odmsys WHERE sys_name = 'version'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_COLUMN);
            return $row !== false ? $row : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function createDatabase(): void
    {
        $pdo = $this->connect();
        $dbName = $this->getDbNameFromDsn();
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
    }

    public function dropAllTables(string $prefix): void
    {
        $pdo = $this->connect();
        $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    private function getDbNameFromDsn(): string
    {
        if (preg_match('/dbname=([^;]+)/', $this->dsn, $matches)) {
            return $matches[1];
        }
        return '';
    }
}