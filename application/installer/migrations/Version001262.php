<?php

class Version001262 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.6.2';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.6.2 - Alter udf table_name column width';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE {$prefix}udf CHANGE `table_name` `table_name` varchar(50)");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.6.3' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}