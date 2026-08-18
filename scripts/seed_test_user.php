<?php
/**
 * Seed a non-admin test user for the E2E test suite.
 *
 * Idempotent: creates the user only if they don't already exist. Run from the
 * host (where the MariaDB port is exposed) before running Playwright E2E tests.
 *
 * Run: php scripts/seed_test_user.php
 *
 * Override via env:
 *   NON_ADMIN_USER       (default: e2euser)
 *   NON_ADMIN_PASSWORD   (default: e2euserpass)
 *   APP_DB_HOST          (default: 127.0.0.1)
 *   APP_DB_PORT          (default: 3306, or DB_EXTERNAL_PORT)
 *   APP_DB_NAME          (default: opendocman)
 *   APP_DB_USER          (default: opendocman)
 *   APP_DB_PASS          (required in production-like setups; falls back to .env)
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

$username = getenv('NON_ADMIN_USER') ?: 'e2euser';
$password = getenv('NON_ADMIN_PASSWORD') ?: 'e2euserpass';
$firstName = 'E2E';
$lastName = 'User';
$email = strtolower($username) . '@example.com';

$dbHost = getenv('APP_DB_HOST') ?: ($env['APP_DB_HOST'] ?? '127.0.0.1');
$dbPort = getenv('APP_DB_PORT') ?: ($env['DB_EXTERNAL_PORT'] ?? ($env['DB_PORT'] ?? '3306'));
$dbName = getenv('APP_DB_NAME') ?: ($env['APP_DB_NAME'] ?? 'opendocman');
$dbUser = getenv('APP_DB_USER') ?: ($env['APP_DB_USER'] ?? 'opendocman');
$dbPass = getenv('APP_DB_PASS') ?: ($env['APP_DB_PASS'] ?? 'opendocman');
$dbPrefix = ($env['DB_PREFIX'] ?? 'odm_');
$department = (int) ($env['DEFAULT_DEPARTMENT'] ?? '1');

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
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

require_once __DIR__ . '/../application/models/PasswordHasher.class.php';

$userTable = $dbPrefix . 'user';

// Check whether the user already exists
$check = $pdo->prepare("SELECT id FROM `{$userTable}` WHERE username = :u");
$check->execute([':u' => $username]);
if ($check->fetchColumn()) {
    echo "Test user '{$username}' already exists. Nothing to do.\n";
    exit(0);
}

$insert = $pdo->prepare(
    "INSERT INTO `{$userTable}`
        (username, password, department, Email, last_name, first_name, can_add, can_checkin)
     VALUES
        (:u, :p, :d, :e, :ln, :fn, 1, 1)"
);
$insert->execute([
    ':u' => $username,
    ':p' => PasswordHasher::hash($password),
    ':d' => $department,
    ':e' => $email,
    ':ln' => $lastName,
    ':fn' => $firstName,
]);

echo "Seeded non-admin test user '{$username}' (id " . $pdo->lastInsertId() . ").\n";
