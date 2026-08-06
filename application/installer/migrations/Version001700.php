<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001700 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.0';
    }

    public function getDescription(): string
    {
        return 'Add category_perms table for permission inheritance templates';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "CREATE TABLE `{$prefix}category_perms` (
                cat_id int(11) unsigned NOT NULL,
                dept_id int(11) unsigned default NULL,
                user_id int(11) unsigned default NULL,
                rights tinyint(4) NOT NULL default '0',
                KEY cat_perms_idx (cat_id, dept_id, user_id),
                KEY cat_id (cat_id),
                KEY dept_id (dept_id),
                KEY user_id (user_id)
            ) ENGINE = MYISAM"
        );
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}category_perms`");
    }
}