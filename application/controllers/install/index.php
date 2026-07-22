<?php
session_start();
require_once __DIR__ . '/../../version.php';
require_once __DIR__ . '/../../installer/ConfigManager.php';
require_once __DIR__ . '/../../installer/DatabaseManager.php';
require_once __DIR__ . '/../../installer/MigrationRunner.php';
require_once __DIR__ . '/../../installer/SchemaBuilder.php';
require_once __DIR__ . '/../../installer/migrations/MigrationInterface.php';
require_once __DIR__ . '/../../installer/migrations/Version001000.php';
require_once __DIR__ . '/../../installer/migrations/Version0011rc2.php';
require_once __DIR__ . '/../../installer/migrations/Version001100.php';
require_once __DIR__ . '/../../installer/migrations/Version0012p1.php';
require_once __DIR__ . '/../../installer/migrations/Version0012p3.php';
require_once __DIR__ . '/../../installer/migrations/Version001240.php';
require_once __DIR__ . '/../../installer/migrations/Version001252.php';
require_once __DIR__ . '/../../installer/migrations/Version001256.php';
require_once __DIR__ . '/../../installer/migrations/Version001257.php';
require_once __DIR__ . '/../../installer/migrations/Version001261.php';
require_once __DIR__ . '/../../installer/migrations/Version001262.php';
require_once __DIR__ . '/../../installer/migrations/Version001263.php';
require_once __DIR__ . '/../../installer/migrations/Version001280.php';
require_once __DIR__ . '/../../installer/migrations/Version001290.php';
require_once __DIR__ . '/../../installer/migrations/Version001300.php';
require_once __DIR__ . '/../../installer/migrations/Version001400.php';
require_once __DIR__ . '/../../installer/migrations/Version001401.php';
require_once __DIR__ . '/../../installer/migrations/Version001402.php';

$configManager = new ConfigManager();
if ($configManager->configExists()) {
    $configManager->loadConfig();
    $prefix = $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_';
    try {
        $db = new DatabaseManager(APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
        $db->connect();
    } catch (Exception $e) {
        // DB may not exist yet
    }
}

require_once __DIR__ . '/../../installer/InstallerController.php';