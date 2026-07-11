<?php

class Version001300 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.3.0';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.3.0 - Remove base_url setting';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'base_url'");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.3.6' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}