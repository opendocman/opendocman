<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001704 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.4';
    }

    public function getDescription(): string
    {
        return 'Add default_signup_department setting';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'default_signup_department', '', 'Default department assigned to new self-registered users (blank = unassigned)', '')"
        );
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'default_signup_department'");
    }
}
