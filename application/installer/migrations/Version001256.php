<?php

class Version001256 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.5.6';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.5.6 - Fix broken revision numbers, update UDF table names';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $stmt = $pdo->query("SELECT id, revision from {$prefix}log WHERE revision LIKE '%(%'");
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            $rev_array = explode("-", $row['revision']);
            $rev_left = ltrim($rev_array[0], "(");
            $rev_right = rtrim($rev_array[1], ")");
            $updateStmt = $pdo->prepare("UPDATE {$prefix}log SET revision = :new_revision WHERE id = :row_id AND revision = :revision");
            $updateStmt->execute([
                ':new_revision' => intval($rev_left - $rev_right),
                ':row_id' => $row['id'],
                ':revision' => $row['revision'],
            ]);
        }

        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.5.7' WHERE sys_name='version'");

        $stmt = $pdo->query("SELECT table_name from {$prefix}udf");
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            $oldTable = $row['table_name'];
            $newTable = "{$prefix}udftbl_{$oldTable}";
            $pdo->exec("ALTER TABLE {$prefix}data CHANGE `{$oldTable}` `{$newTable}` int(11)");
            $pdo->exec("UPDATE {$prefix}udf SET table_name = '{$newTable}' WHERE table_name = '{$oldTable}'");
            $pdo->exec("ALTER TABLE `{$oldTable}` RENAME `{$newTable}`");
        }
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}