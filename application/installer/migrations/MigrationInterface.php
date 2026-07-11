<?php

interface MigrationInterface
{
    public function getVersion(): string;
    public function getDescription(): string;
    public function up(PDO $pdo, string $prefix): void;
    public function down(PDO $pdo, string $prefix): void;
}