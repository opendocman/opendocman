<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

if (!defined('CategoryPerms')) {
    define('CategoryPerms', true);

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
            $this->pdo->prepare("DELETE FROM {$this->table} WHERE cat_id = :cat_id")
                ->execute([':cat_id' => $catId]);
            if (empty($perms)) {
                return;
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (cat_id, dept_id, user_id, rights) VALUES (:cat_id, :dept_id, :user_id, :rights)"
            );
            foreach ($perms as $perm) {
                $rights = (int)$perm['rights'];
                if ($rights === 0) {
                    continue;
                }
                $stmt->execute([
                    ':cat_id' => $catId,
                    // 0 is the "unset" sentinel for the unused dimension. The
                    // columns are NOT NULL (they are part of the primary key),
                    // so NULL cannot be stored; default MariaDB also coerces
                    // PK columns to NOT NULL regardless of the declared schema.
                    ':dept_id' => (int)($perm['dept_id'] ?? 0),
                    ':user_id' => (int)($perm['user_id'] ?? 0),
                    ':rights' => $rights,
                ]);
            }
        }

        public function deleteTemplate(int $catId): void
        {
            $this->pdo->prepare("DELETE FROM {$this->table} WHERE cat_id = :cat_id")
                ->execute([':cat_id' => $catId]);
        }
    }
}