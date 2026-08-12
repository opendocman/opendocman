<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001701 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.1';
    }

    public function getDescription(): string
    {
        return 'Add is_public column, public_sharing setting, and public download access_log action';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` ADD COLUMN `is_public` tinyint(1) DEFAULT 0 AFTER `publishable`");
        $pdo->exec("INSERT INTO `{$prefix}settings` (name, value, description, validation) VALUES ('public_sharing', 'False', '(True/False) Enable public file sharing page. When enabled, files marked as public and approved will be visible without authentication.', 'bool')");
        $pdo->exec("ALTER TABLE `{$prefix}access_log` MODIFY COLUMN `action` enum('A','B','C','V','D','M','X','I','O','Y','R','U') NOT NULL");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` DROP COLUMN `is_public`");
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'public_sharing'");
        $pdo->exec("ALTER TABLE `{$prefix}access_log` MODIFY COLUMN `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL");
    }
}