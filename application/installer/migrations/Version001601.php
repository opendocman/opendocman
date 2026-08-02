<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001601 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.6.1';
    }

    public function getDescription(): string
    {
        return 'Add incomingDir config setting for incoming revision staging';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $stmt = $pdo->prepare("SELECT `value` FROM `{$prefix}settings` WHERE `name` = :name");
        $stmt->execute([':name' => 'dataDir']);
        $dataDir = $stmt->fetchColumn();
        $incomingDir = rtrim($dataDir, '/') . '/incoming/';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}settings` WHERE name = 'incomingDir'");
        $countStmt->execute();
        if ((int)$countStmt->fetchColumn() === 0) {
            $insertStmt = $pdo->prepare(
                "INSERT INTO `{$prefix}settings` (`name`, `value`, `description`, `validation`) VALUES "
                . "(:name, :value, :description, :validation)"
            );
            $insertStmt->execute([
                ':name' => 'incomingDir',
                ':value' => $incomingDir,
                ':description' => 'Location for incoming file revisions that have not yet been approved. Default is inside dataDir.',
                ':validation' => 'maxsize=255',
            ]);

            if (!is_dir($incomingDir)) {
                @mkdir($incomingDir, 0777, true);
            }
        }
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'incomingDir'");
    }
}