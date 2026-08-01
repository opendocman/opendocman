<?php

class MigrationLoader
{
    /**
     * @return MigrationInterface[]
     */
    public static function getAll(): array
    {
        require_once __DIR__ . '/migrations/MigrationInterface.php';

        $files = glob(__DIR__ . '/migrations/Version*.php');
        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            require_once $file;
            $className = basename($file, '.php');
            $migration = new $className();
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException("Migration class {$className} must implement MigrationInterface");
            }
            $migrations[] = $migration;
        }

        usort($migrations, function ($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        return $migrations;
    }
}