<?php

class Version0011rc2 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.1rc2';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.1rc2 - Change data.category column type';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE data CHANGE COLUMN category category smallint(5) unsigned NOT NULL default '0'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}