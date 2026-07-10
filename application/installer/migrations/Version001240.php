<?php

class Version001240 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.4';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.4 - Create udf table';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS udf (
            id  int(11) auto_increment unique,
            table_name  varchar(16),
            display_name    varchar(16),
            field_type  int
        ) ENGINE = MYISAM");
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}