<?php

class Version0012p1 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2p1';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2p1 - Widen all IDs to int(11) unsigned';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE admin MODIFY id int(11) unsigned NOT NULL");
        $pdo->exec("ALTER TABLE category MODIFY id int(11) unsigned NOT NULL auto_increment");
        $pdo->exec("ALTER TABLE data MODIFY id int(11) unsigned NOT NULL auto_increment");
        $pdo->exec("ALTER TABLE data MODIFY category int(11) unsigned NOT NULL");
        $pdo->exec("ALTER TABLE data MODIFY owner int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE data MODIFY reviewer int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE department MODIFY id int(11) unsigned NOT NULL auto_increment");
        $pdo->exec("ALTER TABLE dept_perms MODIFY fid int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE dept_perms MODIFY dept_id int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE dept_reviewer MODIFY dept_id int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE dept_reviewer MODIFY user_id int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE log MODIFY id int(11) unsigned NOT NULL");
        $pdo->exec("ALTER TABLE user MODIFY id int(11) unsigned NOT NULL auto_increment");
        $pdo->exec("ALTER TABLE user MODIFY department int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE user_perms MODIFY fid int(11) unsigned NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE user_perms MODIFY uid int(11) unsigned NOT NULL");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}