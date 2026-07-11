<?php

class Version001400 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.4.0';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.3.6 - Fix description index, settings name unique prefix';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` DROP key `description`");
        $pdo->exec("ALTER TABLE `{$prefix}data` ADD KEY `description` (`description`(200))");
        $pdo->exec("ALTER TABLE `{$prefix}settings` DROP INDEX `name`");
        $pdo->exec("ALTER TABLE `{$prefix}settings` ADD CONSTRAINT `name` UNIQUE (`name`(200))");
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.4.0' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}