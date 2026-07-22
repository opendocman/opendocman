<?php

class Version001402 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.4.2';
    }

    public function getDescription(): string
    {
        return 'Migrate deprecated themes (tweeter, default) to bootstrap5';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $stmt = $pdo->prepare("UPDATE `{$prefix}settings` SET `value` = 'bootstrap5' WHERE `name` = 'theme'");
        $stmt->execute();

        $pdo->exec("UPDATE `{$prefix}odmsys` SET `sys_value`='1.4.2' WHERE `sys_name`='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}
