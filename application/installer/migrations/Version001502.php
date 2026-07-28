<?php

class Version001502 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.5.2';
    }

    public function getDescription(): string
    {
        return 'Add odm_content_index table for full-text file content search';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}content_index` (
            `file_id` int(11) unsigned NOT NULL,
            `content_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `indexed_at` datetime DEFAULT NULL,
            PRIMARY KEY (`file_id`),
            FULLTEXT INDEX `ft_content` (`content_text`)
        ) ENGINE = MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}content_index`");
    }
}