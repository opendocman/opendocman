<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001600 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.6.0';
    }

    public function getDescription(): string
    {
        return 'Add snapshotDir setting for snapshot storage location';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}settings` WHERE name = 'snapshotDir'");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            $defaultSnapshotDir = '/var/www/snapshots/';

            $pdo->exec(
                "INSERT INTO `{$prefix}settings` (`name`, `value`, `description`, `validation`) VALUES "
                . "('snapshotDir', " . $pdo->quote($defaultSnapshotDir) . ", "
                . "'Location to store database and file snapshots. Should be outside web root.', 'maxsize=255')"
            );
        }
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'snapshotDir'");
    }
}