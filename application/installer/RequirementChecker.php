<?php

class RequirementChecker
{
    private array $results = [];

    public function checkAll(): array
    {
        $this->results = [];
        $this->checkPhpVersion();
        $this->checkPdoDriver();
        $this->checkTemplatesWritable();
        $this->checkDataDirWritable();
        return $this->results;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function allPassed(): bool
    {
        foreach ($this->results as $result) {
            if (!$result['passed']) {
                return false;
            }
        }
        return true;
    }

    private function checkPhpVersion(): void
    {
        $required = '7.4.0';
        $current = PHP_VERSION;
        $this->results[] = [
            'name' => 'PHP Version',
            'required' => ">= {$required}",
            'actual' => $current,
            'passed' => version_compare($current, $required, '>='),
        ];
    }

    private function checkPdoDriver(): void
    {
        $hasPdo = extension_loaded('PDO');
        $hasMysql = in_array('mysql', PDO::getAvailableDrivers(), true);
        $this->results[] = [
            'name' => 'PDO MySQL Driver',
            'required' => 'PDO + mysql driver',
            'actual' => $hasPdo && $hasMysql ? 'Available' : 'Missing',
            'passed' => $hasPdo && $hasMysql,
        ];
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

        $this->results[] = [
            'name' => 'templates_c Writable',
            'required' => 'Writable directory',
            'actual' => $writable ? 'Writable' : 'Not writable',
            'passed' => $writable,
        ];
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

        $this->results[] = [
            'name' => 'Data Directory',
            'required' => 'Writable directory',
            'actual' => $writable ? 'OK' : 'Not writable',
            'passed' => $writable,
        ];
    }

    public function checkExtensions(): array
    {
        $extensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'mbstring'];
        $results = [];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = [
                'name' => "PHP Extension: {$ext}",
                'actual' => $loaded ? 'Loaded' : 'Missing',
                'passed' => $loaded,
            ];
        }
        return $results;
    }
}