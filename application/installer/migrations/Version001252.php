<?php

class Version001252 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.5.2';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.5.2 - Rename tables to include prefix, create odmsys';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE admin RENAME AS {$prefix}admin");
        $pdo->exec("ALTER TABLE category RENAME AS {$prefix}category");
        $pdo->exec("ALTER TABLE data RENAME AS {$prefix}data");
        $pdo->exec("ALTER TABLE department RENAME AS {$prefix}department");
        $pdo->exec("ALTER TABLE dept_perms RENAME AS {$prefix}dept_perms");
        $pdo->exec("ALTER TABLE dept_reviewer RENAME AS {$prefix}dept_reviewer");
        $pdo->exec("ALTER TABLE log RENAME AS {$prefix}log");
        $pdo->exec("ALTER TABLE rights RENAME AS {$prefix}rights");
        $pdo->exec("ALTER TABLE user RENAME AS {$prefix}user");
        $pdo->exec("ALTER TABLE user_perms RENAME AS {$prefix}user_perms");
        $pdo->exec("ALTER TABLE udf RENAME AS {$prefix}udf");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}odmsys (
            id  int(11) auto_increment unique,
            sys_name  varchar(16),
            sys_value    varchar(255)
        ) ENGINE = MYISAM");

        $pdo->exec("INSERT INTO {$prefix}odmsys VALUES (NULL,'version','1.2.6')");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}