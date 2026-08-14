<?php
/**
 * Remove leftover E2E test data from a previous run.
 *
 * The E2E smoke suite creates rows with well-known name patterns
 * (E2E *, odm-*, test_doc.txt, e2euser...) and several tests do not delete
 * their own artifacts. Over successive runs these accumulate and, once a
 * listing exceeds its page size, the newly-created row lands on a later page
 * and the test assertion fails. Run this in the Playwright global setup so
 * every `make test-e2e` starts from a clean slate.
 *
 * Run: php scripts/cleanup_e2e_data.php
 *
 * Reads DB creds from .env / env vars (see seed_test_user.php).
 */

function loadEnvFile($path)
{
    if (!is_file($path)) {
        return [];
    }
    $vars = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $vars[trim($key)] = trim($value);
    }
    return $vars;
}

$env = loadEnvFile(__DIR__ . '/../.env');

$dbHost = getenv('APP_DB_HOST') ?: ($env['APP_DB_HOST'] ?? '127.0.0.1');
$dbPort = getenv('APP_DB_PORT') ?: ($env['DB_EXTERNAL_PORT'] ?? ($env['DB_PORT'] ?? '3306'));
$dbName = getenv('APP_DB_NAME') ?: ($env['APP_DB_NAME'] ?? 'opendocman');
$dbUser = getenv('APP_DB_USER') ?: ($env['APP_DB_USER'] ?? 'opendocman');
$dbPass = getenv('APP_DB_PASS') ?: ($env['APP_DB_PASS'] ?? 'opendocman');
$dbPrefix = ($env['DB_PREFIX'] ?? 'odm_');

if ($dbHost === 'db' || $dbHost === '') {
    $dbHost = '127.0.0.1';
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$data = $dbPrefix . 'data';
$log = $dbPrefix . 'log';
$category = $dbPrefix . 'category';
$categoryPerms = $dbPrefix . 'category_perms';
$department = $dbPrefix . 'department';
$deptPerms = $dbPrefix . 'dept_perms';
$deptReviewer = $dbPrefix . 'dept_reviewer';
$contentIndex = $dbPrefix . 'content_index';
$accessLog = $dbPrefix . 'access_log';
$userPerms = $dbPrefix . 'user_perms';
$user = $dbPrefix . 'user';

// Find E2E artifact file ids (uploaded by the smoke/incoming suites).
$fileStmt = $pdo->prepare(
    "SELECT id FROM {$data} WHERE realname LIKE 'E2E%' OR realname LIKE 'odm-%' OR realname IN ('test_doc.txt', 'test_doc_v2.txt') OR description LIKE 'E2E test doc %' OR description LIKE 'Non-admin owner test %' OR description LIKE 'Owner switch test %'"
);
$fileStmt->execute();
$fileIds = $fileStmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($fileIds)) {
    $in = implode(',', array_fill(0, count($fileIds), '?'));
    // A file's related rows live in: access_log/content_index/user_perms/
    // dept_perms keyed on file_id, and the log + data row keyed on id.
    $stmt = $pdo->prepare("DELETE FROM {$accessLog} WHERE file_id IN ({$in})");
    $stmt->execute($fileIds);
    $stmt = $pdo->prepare("DELETE FROM {$contentIndex} WHERE file_id IN ({$in})");
    $stmt->execute($fileIds);
    $stmt = $pdo->prepare("DELETE FROM {$userPerms} WHERE fid IN ({$in})");
    $stmt->execute($fileIds);
    $stmt = $pdo->prepare("DELETE FROM {$deptPerms} WHERE fid IN ({$in})");
    $stmt->execute($fileIds);
    $stmt = $pdo->prepare("DELETE FROM {$log} WHERE id IN ({$in})");
    $stmt->execute($fileIds);
    $stmt = $pdo->prepare("DELETE FROM {$data} WHERE id IN ({$in})");
    $stmt->execute($fileIds);
    echo 'Removed ' . count($fileIds) . " E2E file artifact(s).\n";
}

// E2E departments (created by the department CRUD suite).
$deptStmt = $pdo->prepare("SELECT id FROM {$department} WHERE name LIKE 'E2E %'");
$deptStmt->execute();
$deptIds = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($deptIds)) {
    $in = implode(',', array_fill(0, count($deptIds), '?'));
    $stmt = $pdo->prepare("DELETE FROM {$deptPerms} WHERE dept_id IN ({$in})");
    $stmt->execute($deptIds);
    $stmt = $pdo->prepare("DELETE FROM {$deptReviewer} WHERE dept_id IN ({$in})");
    $stmt->execute($deptIds);
    $stmt = $pdo->prepare("DELETE FROM {$categoryPerms} WHERE dept_id IN ({$in})");
    $stmt->execute($deptIds);
    $stmt = $pdo->prepare("DELETE FROM {$department} WHERE id IN ({$in})");
    $stmt->execute($deptIds);
    // Null-out any users pointing at the removed dept (e.g. e2euser defaults).
    $stmt = $pdo->prepare("UPDATE {$user} SET department = 1 WHERE department IN ({$in})");
    $stmt->execute($deptIds);
    echo 'Removed ' . count($deptIds) . " E2E department(s).\n";
}

// E2E categories (created by the category CRUD, inline add, and permission suites).
$catStmt = $pdo->prepare("SELECT id FROM {$category} WHERE name LIKE 'E2E %'");
$catStmt->execute();
$catIds = $catStmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($catIds)) {
    $in = implode(',', array_fill(0, count($catIds), '?'));
    $stmt = $pdo->prepare("DELETE FROM {$categoryPerms} WHERE cat_id IN ({$in})");
    $stmt->execute($catIds);
    // Any remaining data rows pointing at these categories: reset to category 1 (SOP).
    $stmt = $pdo->prepare("UPDATE {$data} SET category = 1 WHERE category IN ({$in})");
    $stmt->execute($catIds);
    $stmt = $pdo->prepare("DELETE FROM {$category} WHERE id IN ({$in})");
    $stmt->execute($catIds);
    echo 'Removed ' . count($catIds) . " E2E categor(ies).\n";
}

echo "E2E data cleanup complete.\n";