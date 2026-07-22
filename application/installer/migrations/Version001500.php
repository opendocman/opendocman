<?php

class Version001500 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.5.0';
    }

    public function getDescription(): string
    {
        return 'Add pw_change_required column to user table for forced password change on first login';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` ADD COLUMN `pw_change_required` tinyint(1) NOT NULL DEFAULT 0");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` DROP COLUMN `pw_change_required`");
    }
}
