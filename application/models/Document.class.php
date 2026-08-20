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

class Document
{
    public static function create(PDO $pdo, array $params): int
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $publishable = (string) $params['publishable'];
        $isPublic = $params['is_public'] ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO {$prefix}data (status, category, owner, realname, created, description, department, comment, default_rights, publishable, is_public) VALUES (0, :category, :owner_id, :realname, NOW(), :description, :current_user_dept, :comment, 0, {$publishable}, {$isPublic})");
        $stmt->bindParam(':category', $params['category']);
        $stmt->bindParam(':owner_id', $params['owner_id']);
        $stmt->bindParam(':realname', $params['realname']);
        $stmt->bindParam(':description', $params['description']);
        $stmt->bindParam(':current_user_dept', $params['department']);
        $stmt->bindParam(':comment', $params['comment']);
        $stmt->execute();
        $fileId = (int) $pdo->lastInsertId();

        $historyStmt = $pdo->prepare("INSERT INTO {$prefix}log (id, modified_on, modified_by, note, revision) VALUES ('{$fileId}', NOW(), :username, 'Initial import', 'current')");
        $historyStmt->bindParam(':username', $params['username']);
        $historyStmt->execute();

        foreach ($params['dept_perms'] as $deptId => $deptPerm) {
            $s = $pdo->prepare("INSERT INTO {$prefix}dept_perms (fid, rights, dept_id) VALUES ({$fileId}, :perm, :did)");
            $s->bindParam(':perm', $deptPerm);
            $s->bindParam(':did', $deptId);
            $s->execute();
        }
        foreach ($params['user_perms'] as $userId => $permission) {
            $s = $pdo->prepare("INSERT INTO {$prefix}user_perms (fid, uid, rights) VALUES ({$fileId}, :uid, :rights)");
            $s->bindParam(':uid', $userId);
            $s->bindParam(':rights', $permission);
            $s->execute();
        }

        $newFilePath = getFilePath($fileId, $params['realname'], 'data');
        $newFileDir = dirname($newFilePath);
        if (!is_dir($newFileDir)) {
            mkdir($newFileDir, 0775, true);
        }
        if ($params['source_is_upload']) {
            move_uploaded_file($params['source_path'], $newFilePath);
        } else {
            copy($params['source_path'], $newFilePath);
        }

        $mime = $params['mime'];
        if (TextExtractorFactory::isExtractable($mime)) {
            $extractor = TextExtractorFactory::create($mime);
            if ($extractor !== null) {
                $contentText = $extractor->extract($newFilePath);
                $indexStmt = $pdo->prepare("INSERT INTO {$prefix}content_index (file_id, content_text, indexed_at) VALUES (:file_id, :content_text, NOW())");
                $indexStmt->execute([':file_id' => $fileId, ':content_text' => $contentText]);
            }
        }

        return $fileId;
    }
}