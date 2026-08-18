<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001703 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.3';
    }

    public function getDescription(): string
    {
        return 'Make category_perms dept_id/user_id NOT NULL DEFAULT 0 (0-sentinel for permission templates)';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $table = "`{$prefix}category_perms`";

        // Existing rows on MySQL may have stored NULL in the unused dimension.
        // Normalize them to the 0-sentinel before making the columns NOT NULL.
        $pdo->exec("UPDATE {$table} SET dept_id = 0 WHERE dept_id IS NULL");
        $pdo->exec("UPDATE {$table} SET user_id = 0 WHERE user_id IS NULL");

        $pdo->exec(
            "ALTER TABLE {$table}
                MODIFY dept_id int(11) unsigned NOT NULL DEFAULT 0,
                MODIFY user_id int(11) unsigned NOT NULL DEFAULT 0"
        );
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $table = "`{$prefix}category_perms`";
        $pdo->exec(
            "ALTER TABLE {$table}
                MODIFY dept_id int(11) unsigned default NULL,
                MODIFY user_id int(11) unsigned default NULL"
        );
    }
}