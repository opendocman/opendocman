<?php

class Version001501 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.5.1';
    }

    public function getDescription(): string
    {
        return 'Add UNIQUE constraint on category name to prevent duplicates';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        // Deduplicate using PHP logic to handle cascading name conflicts
        $stmt = $pdo->query("SELECT id, name FROM `{$prefix}category` ORDER BY id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seen = [];
        $updates = [];
        foreach ($rows as $row) {
            $name = $row['name'];
            $id = (int) $row['id'];
            if (!isset($seen[$name])) {
                $seen[$name] = true;
                continue;
            }
            // Duplicate found — find a unique name by appending the ID
            $newName = $name . ' (dup ' . $id . ')';
            while (isset($seen[$newName])) {
                $newName = $name . ' (dup ' . $id . '-' . random_int(1000, 9999) . ')';
            }
            $seen[$newName] = true;
            $updates[] = ['id' => $id, 'name' => substr($newName, 0, 255)];
        }

        $updateStmt = $pdo->prepare("UPDATE `{$prefix}category` SET name = :name WHERE id = :id");
        foreach ($updates as $u) {
            $updateStmt->execute([':name' => $u['name'], ':id' => $u['id']]);
        }

        $pdo->exec("ALTER TABLE `{$prefix}category` ADD UNIQUE (name(200))");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}category` DROP INDEX name");
    }
}