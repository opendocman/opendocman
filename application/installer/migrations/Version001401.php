<?php

class Version001401 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.4.1';
    }

    public function getDescription(): string
    {
        return 'Migrate files to subfolder-by-ID structure with original filenames';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        // Query dataDir from the settings table (config globals NOT available during upgrade)
        $stmt = $pdo->query("SELECT `value` FROM `{$prefix}settings` WHERE `name` = 'dataDir'");
        $dataDir = rtrim($stmt->fetchColumn(), '/') . '/';
        if ($dataDir === '/') {
            return; // No dataDir configured, nothing to migrate
        }
        $archiveDir = $dataDir . 'archiveDir/';
        $revisionDir = $dataDir . 'revisionDir/';

        $stmt = $pdo->query("SELECT `id`, `realname` FROM `{$prefix}data` ORDER BY `id` ASC");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            $id = $file['id'];
            $realname = $file['realname'];

            // Sanitize the realname (no external helpers available in migration context)
            $realname = str_replace(['../', '..\\'], '', $realname);
            $realname = str_replace(['/', '\\'], '', $realname);
            $realname = str_replace("\0", '', $realname);
            $realname = ltrim($realname, '. -');
            if ($realname === '') {
                $realname = 'untitled';
            }

            // --- dataDir ---
            $oldDataPath = $dataDir . $id . '.dat';
            $newDataDir = $dataDir . $id . '/';
            $newDataPath = $newDataDir . $realname;

            if (file_exists($oldDataPath)) {
                if (!is_dir($newDataDir)) {
                    mkdir($newDataDir, 0775, true);
                }
                rename($oldDataPath, $newDataPath);
            }

            // --- archiveDir ---
            $oldArchivePath = $archiveDir . $id . '.dat';
            $newArchiveDir = $archiveDir . $id . '/';
            $newArchivePath = $newArchiveDir . $realname;

            if (file_exists($oldArchivePath)) {
                if (!is_dir($newArchiveDir)) {
                    mkdir($newArchiveDir, 0775, true);
                }
                rename($oldArchivePath, $newArchivePath);
            }

            // --- revisionDir ---
            $oldRevisionDir = $revisionDir . $id . '/';
            if (is_dir($oldRevisionDir)) {
                $ext = '';
                $dotPos = strrpos($realname, '.');
                if ($dotPos !== false) {
                    $ext = substr($realname, $dotPos);
                }
                $basename = ($dotPos !== false) ? substr($realname, 0, $dotPos) : $realname;

                $revFiles = glob($oldRevisionDir . $id . '_*.dat');
                if ($revFiles === false) {
                    $revFiles = [];
                }
                foreach ($revFiles as $revFile) {
                    $filename = basename($revFile);
                    if (preg_match('/^' . preg_quote($id, '/') . '_(\d+)\.dat$/', $filename, $matches)) {
                        $revNum = $matches[1];
                        $newRevPath = $oldRevisionDir . $basename . '-rev' . $revNum . $ext;
                        rename($revFile, $newRevPath);
                    }
                }
            }
        }

        // Update DB version
        $pdo->exec("UPDATE `{$prefix}odmsys` SET `sys_value`='1.4.1' WHERE `sys_name`='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        // Reversal not supported for file migrations
    }
}
