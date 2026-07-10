<?php

class Version001280 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.8';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.8 - Add max_query setting';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'max_query', '500', 'Set this to the maximum number of rows you want to be returned in a file listing. If your file list is slow decrease this value.', 'num')");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.9' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}