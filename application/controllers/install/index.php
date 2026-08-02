<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../version.php';
require_once __DIR__ . '/../../installer/ConfigManager.php';
require_once __DIR__ . '/../../installer/DatabaseManager.php';
require_once __DIR__ . '/../../installer/MigrationRunner.php';
require_once __DIR__ . '/../../installer/SchemaBuilder.php';

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