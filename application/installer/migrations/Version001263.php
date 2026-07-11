<?php

class Version001263 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.6.3';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.6.3 - Add can_add and can_checkin to user table';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE {$prefix}user ADD COLUMN can_add tinyint(1) NULL DEFAULT 1");
        $pdo->exec("ALTER TABLE {$prefix}user ADD COLUMN can_checkin tinyint(1) NULL DEFAULT 1");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.8' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}