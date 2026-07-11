<?php

class Version001100 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.1';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.1 - Modify data table, add indexes';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `data`
            MODIFY category tinyint(4) unsigned NOT NULL DEFAULT '0',
            MODIFY status smallint(6) NULL DEFAULT NULL,
            ADD INDEX id (id),
            ADD INDEX `id_2` (id),
            ADD INDEX publishable (publishable),
            ADD INDEX description (description)");

        $pdo->exec("ALTER TABLE `dept_perms`
            ADD INDEX rights (rights),
            ADD INDEX `dept_id` (`dept_id`),
            ADD INDEX fid (fid)");

        $pdo->exec("ALTER TABLE log
            ADD revision varchar(255) NULL DEFAULT NULL AFTER note,
            ADD INDEX id (id),
            ADD INDEX `modified_on` (`modified_on`)");

        $pdo->exec("ALTER TABLE `user_perms`
            ADD INDEX fid (fid),
            ADD INDEX uid (uid),
            ADD INDEX rights (rights)");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}