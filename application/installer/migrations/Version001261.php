<?php

class Version001261 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.6.1';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.6.1 - Create access_log table';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}access_log` (
            `file_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
            `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL
        ) ENGINE = MYISAM");

        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.6.2' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}