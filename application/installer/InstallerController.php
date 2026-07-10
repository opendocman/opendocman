<?php

session_start();

require_once __DIR__ . '/../version.php';
require_once __DIR__ . '/ConfigManager.php';
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/RequirementChecker.php';
require_once __DIR__ . '/MigrationRunner.php';
require_once __DIR__ . '/SchemaBuilder.php';
require_once __DIR__ . '/migrations/MigrationInterface.php';
require_once __DIR__ . '/migrations/Version001000.php';
require_once __DIR__ . '/migrations/Version0011rc2.php';
require_once __DIR__ . '/migrations/Version001100.php';
require_once __DIR__ . '/migrations/Version0012p1.php';
require_once __DIR__ . '/migrations/Version0012p3.php';
require_once __DIR__ . '/migrations/Version001240.php';
require_once __DIR__ . '/migrations/Version001252.php';
require_once __DIR__ . '/migrations/Version001256.php';
require_once __DIR__ . '/migrations/Version001257.php';
require_once __DIR__ . '/migrations/Version001261.php';
require_once __DIR__ . '/migrations/Version001262.php';
require_once __DIR__ . '/migrations/Version001263.php';
require_once __DIR__ . '/migrations/Version001280.php';
require_once __DIR__ . '/migrations/Version001290.php';
require_once __DIR__ . '/migrations/Version001300.php';
require_once __DIR__ . '/migrations/Version001400.php';

class InstallerController
{
    private ConfigManager $configManager;
    private ?DatabaseManager $dbManager = null;

    public function __construct()
    {
        $this->configManager = new ConfigManager();
    }

    public function handleRequest(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');

        if (strpos($path, 'installer/setup-config') === 0 || strpos($path, 'install/setup-config') === 0) {
            $this->handleConfigWizard();
            return;
        }

        if (!$this->configManager->configExists()) {
            header('Location: /installer/setup-config');
            exit;
        }

        $this->configManager->loadConfig();
        $prefix = $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_';

        try {
            $this->dbManager = new DatabaseManager(
                APP_DB_HOST,
                APP_DB_NAME,
                APP_DB_USER,
                APP_DB_PASS
            );
            $this->dbManager->connect();
        } catch (Exception $e) {
            $this->renderError('Database connection failed: ' . $e->getMessage());
            return;
        }

        $op = $_GET['op'] ?? '';

        switch ($op) {
            case 'install':
                $this->handleFreshInstall($prefix);
                break;
            case 'requirements':
                $this->showRequirements();
                break;
            case 'backup-reminder':
                $this->showBackupReminder();
                break;
            case 'upgrade':
                $this->handleUpgrade($prefix);
                break;
            default:
                $this->showIntro($prefix);
                break;
        }
    }

    private function handleConfigWizard(): void
    {
        $step = $_GET['step'] ?? '0';

        if ($this->configManager->configExists() && $step === '0') {
            $targetPath = $this->configManager->getTargetPath();
            require __DIR__ . '/views/config-exists.php';
            return;
        }

        switch ($step) {
            case '0':
                $this->showConfigWelcome();
                break;
            case '1':
                $this->showConfigForm();
                break;
            case '2':
                $this->processConfigForm();
                break;
            default:
                $this->showConfigWelcome();
                break;
        }
    }

    private function showConfigWelcome(): void
    {
        require __DIR__ . '/views/welcome.php';
    }

    private function showConfigForm(): void
    {
        $configManager = $this->configManager;
        $configManager->loadConfig();

        if (defined('APP_DB_NAME')) {
            $existing = [
                'db_name' => APP_DB_NAME,
                'db_user' => APP_DB_USER,
                'db_pass' => APP_DB_PASS,
                'db_host' => APP_DB_HOST,
                'db_prefix' => $GLOBALS['CONFIG']['db_prefix'] ?? 'odm_',
            ];
        } else {
            $existing = [];
        }

        $defaults = [
            'db_name' => $existing['db_name'] ?? $configManager->getEnvVar('APP_DB_NAME', 'opendocman'),
            'db_user' => $existing['db_user'] ?? $configManager->getEnvVar('APP_DB_USER', 'opendocman'),
            'db_pass' => $existing['db_pass'] ?? $configManager->getEnvVar('APP_DB_PASS', ''),
            'db_host' => $existing['db_host'] ?? $configManager->getEnvVar('APP_DB_HOST', 'localhost'),
            'db_prefix' => $existing['db_prefix'] ?? $configManager->getEnvVar('DB_PREFIX', 'odm_'),
            'admin_password' => $configManager->getEnvVar('ADMIN_PASSWORD', 'admin'),
            'data_dir' => $configManager->getEnvVar('ODM_DATADIR', '/var/www/document_repository/'),
        ];
        $isDocker = $configManager->getEnvVar('IS_DOCKER') === 'true';
        require __DIR__ . '/views/config-form.php';
    }

    private function processConfigForm(): void
    {
        $errors = [];

        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';
        $dbHost = $_POST['db_host'] ?? '';
        $dbPrefix = $_POST['db_prefix'] ?? 'odm_';
        $adminPassword = $_POST['admin_password'] ?? 'admin';
        $dataDir = $_POST['data_dir'] ?? '/var/www/document_repository/';

        if (empty($dbName)) {
            $errors[] = 'Database name is required';
        }
        if (empty($dbUser)) {
            $errors[] = 'Database user is required';
        }
        if (strlen($adminPassword) < 5) {
            $errors[] = 'Admin password must be at least 5 characters';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $errors[] = 'Database prefix must be alphanumeric with underscores only';
        }

        if (!empty($errors)) {
            $errorMessage = implode('<br>', $errors);
            require __DIR__ . '/views/config-form.php';
            return;
        }

        try {
            $this->configManager->writeConfig([
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'db_host' => $dbHost,
                'db_prefix' => $dbPrefix,
            ]);

            $_SESSION['adminpass'] = $adminPassword;
            $_SESSION['datadir'] = $dataDir;
            $_SESSION['db_prefix'] = $dbPrefix;
            $_SESSION['baseurl'] = $this->detectBaseUrl();

            $configManager = $this->configManager;
            $targetPath = $configManager->getTargetPath();
            require __DIR__ . '/views/config-complete.php';
        } catch (Exception $e) {
            $errorMessage = 'Failed to write config: ' . $e->getMessage();
            require __DIR__ . '/views/config-form.php';
        }
    }

    private function detectBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    private function showIntro(string $prefix): void
    {
        $isUpgrade = $this->dbManager->odmsysTableExists($prefix);
        if ($isUpgrade) {
            $currentVersion = $this->dbManager->getDbVersion($prefix) ?? 'Unknown';
            $requiredVersion = $GLOBALS['CONFIG']['required_db_version'];
            $needsUpgrade = version_compare($currentVersion, $requiredVersion, '<');

            if (!$needsUpgrade) {
                echo '<div style="text-align:center; padding:40px;"><h2>Database is up to date</h2>';
                echo '<p>Current version: ' . htmlentities($currentVersion) . '</p>';
                echo '<p><a href="../index">Go to Application</a></p></div>';
                return;
            }

            $currentVersion = $currentVersion;
            $requiredVersion = $requiredVersion;
            require __DIR__ . '/views/upgrade.php';
        } else {
            $requiredVersion = $GLOBALS['CONFIG']['required_db_version'];
            require __DIR__ . '/views/db-install.php';
        }
    }

    private function showRequirements(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        $allPassed = $checker->allPassed();
        require __DIR__ . '/views/requirements.php';
    }

    private function handleFreshInstall(string $prefix): void
    {
        $checker = new RequirementChecker();
        if (!$checker->allPassed()) {
            $results = $checker->checkAll();
            $errorMessage = 'Please fix the requirements first';
            require __DIR__ . '/views/requirements.php';
            return;
        }

        $forceFresh = isset($_GET['force_fresh']) && $_GET['force_fresh'] === '1';

        try {
            $adminPassword = $_SESSION['adminpass'] ?? 'admin';
            $dataDir = $_SESSION['datadir'] ?? '/var/www/document_repository/';

            if ($forceFresh) {
                $this->dbManager->dropAllTables($prefix);
            }

            $this->dbManager->createDatabase();

            $schemaBuilder = new SchemaBuilder();
            $pdo = $this->dbManager->connect();
            $pdo->exec("USE `" . APP_DB_NAME . "`");

            foreach ($schemaBuilder->getCreateTableStatements($prefix) as $stmt) {
                $pdo->exec($stmt);
            }

            foreach ($schemaBuilder->getDefaultDataStatements($prefix, [
                'admin_password' => $adminPassword,
                'datadir' => $dataDir,
            ]) as $stmt) {
                $pdo->exec($stmt);
            }

            $migrationRunner = new MigrationRunner($pdo, $prefix);
            $migrationRunner->ensureTrackingTable();

            $currentVersion = $schemaBuilder->getVersion();
            $migrationTable = $prefix . 'migrations';
            $pdo->exec("INSERT INTO `{$migrationTable}` (`version`, `name`, `executed_at`, `batch`) VALUES ('{$currentVersion}', 'Fresh Install Schema', NOW(), 1)");

            $isDocker = $this->configManager->getEnvVar('IS_DOCKER') === 'true';
            $httpPort = $this->configManager->getEnvVar('HTTP_PORT', '8080');
            $hostname = $this->configManager->getEnvVar('ODM_HOSTNAME', 'localhost');
            $adminPassword = $adminPassword;
            unset($_SESSION['datadir']);
            require __DIR__ . '/views/complete.php';
        } catch (Exception $e) {
            $this->renderError('Installation failed: ' . $e->getMessage());
        }
    }

    private function showBackupReminder(): void
    {
        $currentVersion = $this->dbManager->getDbVersion($GLOBALS['CONFIG']['db_prefix']) ?? 'Unknown';
        $requiredVersion = $GLOBALS['CONFIG']['required_db_version'];
        require __DIR__ . '/views/backup-reminder.php';
    }

    private function handleUpgrade(string $prefix): void
    {
        try {
            $pdo = $this->dbManager->connect();
            $runner = new MigrationRunner($pdo, $prefix);

            $runner->registerMigrations([
                new Version001000(),
                new Version0011rc2(),
                new Version001100(),
                new Version0012p1(),
                new Version0012p3(),
                new Version001240(),
                new Version001252(),
                new Version001256(),
                new Version001257(),
                new Version001261(),
                new Version001262(),
                new Version001263(),
                new Version001280(),
                new Version001290(),
                new Version001300(),
                new Version001400(),
            ]);

            $results = $runner->run();
            $hasError = false;
            foreach ($results as $r) {
                if ($r['status'] === 'error') {
                    $hasError = true;
                    break;
                }
            }

            $checker = new RequirementChecker();
            require __DIR__ . '/views/upgrade-results.php';
        } catch (Exception $e) {
            $this->renderError('Upgrade failed: ' . $e->getMessage());
        }
    }

    private function renderError(string $message): void
    {
        echo '<div style="text-align:center; padding:40px;">';
        echo '<h2 style="color:#cc0000;">Error</h2>';
        echo '<p>' . htmlentities($message) . '</p>';
        echo '<p><a href="javascript:history.back()">Go Back</a></p>';
        echo '</div>';
    }
}

$controller = new InstallerController();
$controller->handleRequest();