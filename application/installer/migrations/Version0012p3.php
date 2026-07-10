<?php

class Version0012p3 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2p3';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2p3 - Add pw_reset_code to user table';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE user ADD pw_reset_code CHAR(32) default NULL");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}