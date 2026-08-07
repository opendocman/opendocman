<?php

class RequirementChecker
{
    private array $results = [];

    public function checkAll(): array
    {
        $this->results = [];

        // Built-in checks (environment-sensitive, need context)
        $this->checkPhpVersion();
        $this->checkPdoDriver();
        $this->checkTemplatesWritable();
        $this->checkDataDirWritable();

        // Auto-discover delegated checkers
        $checkerFiles = glob(__DIR__ . '/checkers/*Checker.php');
        if ($checkerFiles !== false) {
            foreach ($checkerFiles as $file) {
                require_once $file;
                $className = basename($file, '.php');
                if (class_exists($className)) {
                    try {
                        $checker = new $className();
                        if ($checker instanceof CheckerInterface) {
                            $this->results = array_merge($this->results, $checker->check());
                        }
                    } catch (\Throwable $e) {
                        // Checker requires constructor arguments not available here — skip
                    }
                }
            }
        }

        return $this->results;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function allPassed(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->passed) {
                return false;
            }
        }
        return true;
    }

    public function hasRequiredFailures(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isRequired() && !$result->passed) {
                return true;
            }
        }
        return false;
    }

    // --- Inline checks (kept in-place, return CheckResult now) ---

    private function checkPhpVersion(): void
    {
        $required = '7.4.0';
        $current = PHP_VERSION;
        $this->results[] = new CheckResult(
            'PHP Version',
            ">= {$required}",
            $current,
            version_compare($current, $required, '>='),
            'required'
        );
    }

    private function checkPdoDriver(): void
    {
        $hasPdo = extension_loaded('PDO');
        $hasMysql = $hasPdo ? in_array('mysql', PDO::getAvailableDrivers(), true) : false;
        $this->results[] = new CheckResult(
            'PDO MySQL Driver',
            'PDO + mysql driver',
            $hasPdo && $hasMysql ? 'Available' : 'Missing',
            $hasPdo && $hasMysql,
            'required'
        );
    }

    private function checkTemplatesWritable(): void
    {
        $paths = [
            __DIR__ . '/../../templates_c',
            __DIR__ . '/../templates_c',
        ];

        $writable = false;
        foreach ($paths as $path) {
            if (is_dir($path) && is_writable($path)) {
                $writable = true;
                break;
            }
            if (!is_dir($path)) {
                if (@mkdir($path, 0777, true)) {
                    $writable = true;
                    break;
                }
            }
        }

        $this->results[] = new CheckResult(
            'templates_c Writable',
            'Writable directory',
            $writable ? 'Writable' : 'Not writable',
            $writable,
            'required'
        );
    }

    private function checkDataDirWritable(): void
    {
        $dataDir = $_SESSION['datadir'] ?? '/var/www/document_repository';
        $writable = false;

        if (is_dir($dataDir) && is_writable($dataDir)) {
            $writable = true;
        } elseif (!is_dir($dataDir)) {
            $parent = dirname($dataDir);
            if (is_writable($parent)) {
                $writable = true;
            }
        }

        $this->results[] = new CheckResult(
            'Data Directory',
            'Writable directory',
            $writable ? 'OK' : 'Not writable',
            $writable,
            'required'
        );
    }

    // Orphaned method — kept for backward compat but no longer called
    public function checkExtensions(): array
    {
        $extensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'mbstring'];
        $results = [];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = new CheckResult(
                "PHP Extension: {$ext}",
                '',
                $loaded ? 'Loaded' : 'Missing',
                $loaded,
                'required'
            );
        }
        return $results;
    }
}