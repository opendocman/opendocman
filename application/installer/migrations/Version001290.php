<?php

class Version001290 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.9';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.9 - Remove secureurl setting';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'secureurl'");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.3.0' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}