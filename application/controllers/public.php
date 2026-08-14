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

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

// Check if public sharing is enabled
if (!isset($GLOBALS['CONFIG']['public_sharing']) || $GLOBALS['CONFIG']['public_sharing'] !== 'True') {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . msg('public_page_title') . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/bootstrap5/style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="alert alert-info">' . msg('message_public_disabled') . '</div>
        <a href="index" class="btn btn-primary">' . msg('login') . '</a>
    </div>
</body>
</html>';
    exit;
}

if (isset($_GET['submit']) && $_GET['submit'] === 'download' && isset($_GET['id'])) {
    // Download mode
    $fileId = (int) $_GET['id'];
    $filedata = new FileData($fileId, $pdo);

    if (!$filedata->exists() || !$filedata->getIsPublic() || $filedata->isPublishable() != 1) {
        header('HTTP/1.0 404 Not Found');
        echo 'File not found';
        exit;
    }

    $realname = $filedata->getName();
    $filePath = getFilePath($fileId, $realname, 'data');

    if (!file_exists($filePath)) {
        header('HTTP/1.0 404 Not Found');
        echo 'File not found';
        exit;
    }

    AccessLog::addLogEntry($fileId, 'U', $pdo, 0);

    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"odm_document_" . $fileId . "\"; filename*=UTF-8''" . rawurlencode($realname));
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// List mode
$query = "SELECT
    d.id,
    d.realname,
    d.description,
    d.category,
    c.name as category_name,
    d.created
FROM {$GLOBALS['CONFIG']['db_prefix']}data d
LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}category c ON d.category = c.id
WHERE d.is_public = 1 AND d.publishable = 1
ORDER BY d.created DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$GLOBALS['smarty']->assign('public_files', $files);
$GLOBALS['smarty']->assign('public_sharing', $GLOBALS['CONFIG']['public_sharing'] ?? 'False');
$GLOBALS['smarty']->assign('site_title', $GLOBALS['CONFIG']['title']);
display_smarty_template('public.tpl');