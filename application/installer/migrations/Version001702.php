<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001702 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.2';
    }

    public function getDescription(): string
    {
        return 'Widen user password column to varchar(255) for bcrypt hashes';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` MODIFY COLUMN `password` varchar(255) NOT NULL default ''");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` MODIFY COLUMN `password` varchar(50) NOT NULL default ''");
    }

}