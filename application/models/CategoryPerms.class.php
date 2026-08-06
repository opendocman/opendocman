<?php

class CategoryPerms
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = $GLOBALS['CONFIG']['db_prefix'] . 'category_perms';
    }

    public function getPermission(int $catId, ?int $userId = null, ?int $deptId = null): ?int
    {
        if ($userId !== null && $deptId !== null) {
            throw new InvalidArgumentException('Provide userId or deptId, not both');
        }
        $clauses = [];
        $params = [':cat_id' => $catId];
        if ($userId !== null) {
            $clauses[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        } elseif ($deptId !== null) {
            $clauses[] = 'dept_id = :dept_id';
            $params[':dept_id'] = $deptId;
        } else {
            throw new InvalidArgumentException('Either userId or deptId required');
        }
        $query = "SELECT rights FROM {$this->table} WHERE cat_id = :cat_id AND " . implode(' AND ', $clauses) . ' LIMIT 1';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (int)$row['rights'] : null;
    }

    public function getTemplate(int $catId): array
    {
        $query = "SELECT dept_id, user_id, rights FROM {$this->table} WHERE cat_id = :cat_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':cat_id' => $catId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($row) {
            $row['rights'] = (int)$row['rights'];
            return $row;
        }, $rows);
    }

    public function saveTemplate(int $catId, array $perms): void
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $this->pdo->prepare("DELETE FROM {$prefix}category_perms WHERE cat_id = :cat_id")
            ->execute([':cat_id' => $catId]);
        if (empty($perms)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$prefix}category_perms (cat_id, dept_id, user_id, rights) VALUES (:cat_id, :dept_id, :user_id, :rights)"
        );
        foreach ($perms as $perm) {
            $rights = (int)$perm['rights'];
            if ($rights === 0) {
                continue;
            }
            $stmt->execute([
                ':cat_id' => $catId,
                ':dept_id' => $perm['dept_id'] ?? null,
                ':user_id' => $perm['user_id'] ?? null,
                ':rights' => $rights,
            ]);
        }
    }

    public function deleteTemplate(int $catId): void
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $this->pdo->prepare("DELETE FROM {$prefix}category_perms WHERE cat_id = :cat_id")
            ->execute([':cat_id' => $catId]);
    }
}