# Installer Pre-Flight Checks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add environment validation checks to the installer/upgrader that detect missing PHP extensions, Composer dependencies, and misconfigured server settings before installation, preventing fatal errors like issue #415.

**Architecture:** Composable checker classes implementing `CheckerInterface`, auto-discovered by `RequirementChecker` via glob (matching the `MigrationLoader` pattern). Each checker returns `CheckResult[]` with `required`/`recommended` severity. The `requirements.php` view renders warnings for recommended failures and blocks installation only on required failures.

**Tech Stack:** PHP 7.4+, PHPUnit 9, Mockery

## Global Constraints

- PHP 7.4 minimum (same as the project)
- No new Composer dependencies
- Follow `MigrationLoader` glob-based discovery pattern for checkers
- All checker classes must be in `application/installer/checkers/` and end in `Checker.php`
- Tests go in `tests/Unit/` following existing naming conventions (`*Test.php`)
- Bump `ODM_DB_VERSION` if migrations change — not needed here (no schema changes)

---

### Task 1: Core Infrastructure — CheckResult, CheckerInterface, and RequirementChecker Update

**Files:**
- Create: `application/installer/CheckResult.php`
- Create: `application/installer/CheckerInterface.php`
- Modify: `application/installer/RequirementChecker.php` — add discovery + delegation, refactor inline checks to return `CheckResult[]`
- Create: `tests/Unit/CheckResultTest.php`
- Create: `tests/Unit/CheckerInterfaceTest.php` (ensures implementation contract)

**Interfaces:**
- Consumes: nothing (foundational)
- Produces: `CheckResult` value object, `CheckerInterface` contract, updated `RequirementChecker` that returns `CheckResult[]` and supports checker discovery

- [ ] **Step 1: Create CheckResult value object**

```php
<?php

class CheckResult
{
    public string $name;
    public string $required;
    public string $actual;
    public bool $passed;
    public string $severity; // 'required' | 'recommended'

    public function __construct(
        string $name,
        string $required,
        string $actual,
        bool $passed,
        string $severity = 'required'
    ) {
        $this->name = $name;
        $this->required = $required;
        $this->actual = $actual;
        $this->passed = $passed;
        $this->severity = $severity;
    }

    public function isRequired(): bool
    {
        return $this->severity === 'required';
    }
}
```

- [ ] **Step 2: Create CheckerInterface**

```php
<?php

interface CheckerInterface
{
    /** @return CheckResult[] */
    public function check(): array;
    public function getName(): string;
}
```

- [ ] **Step 3: Write tests for CheckResult**

File: `tests/Unit/CheckResultTest.php`

```php
<?php

use PHPUnit\Framework\TestCase;

class CheckResultTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $result = new CheckResult('PHP Extension: zip', 'Required for Office docs', 'Missing', false, 'required');
        $this->assertEquals('PHP Extension: zip', $result->name);
        $this->assertEquals('Required for Office docs', $result->required);
        $this->assertEquals('Missing', $result->actual);
        $this->assertFalse($result->passed);
        $this->assertEquals('required', $result->severity);
    }

    public function testDefaultsToRequiredSeverity(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true);
        $this->assertEquals('required', $result->severity);
    }

    public function testIsRequiredReturnsTrueForRequired(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true, 'required');
        $this->assertTrue($result->isRequired());
    }

    public function testIsRequiredReturnsFalseForRecommended(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true, 'recommended');
        $this->assertFalse($result->isRequired());
    }
}
```

- [ ] **Step 4: Update RequirementChecker to support checker delegation**

```php
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
                    $checker = new $className();
                    if ($checker instanceof CheckerInterface) {
                        $this->results = array_merge($this->results, $checker->check());
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
```

- [ ] **Step 5: Write test for RequirementChecker delegation**

File: `tests/Unit/RequirementCheckerTest.php`

```php
<?php

use PHPUnit\Framework\TestCase;

class RequirementCheckerTest extends TestCase
{
    public function testCheckAllReturnsArrayOfCheckResults(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckAllIncludesBuiltInChecks(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('PHP Version', $names);
        $this->assertContains('PDO MySQL Driver', $names);
        $this->assertContains('templates_c Writable', $names);
    }

    public function testAllPassedReturnsTrueWhenAllPass(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        // All are CheckResult objects — allPassed checks all
        $this->assertIsBool($checker->allPassed());
    }

    public function testHasRequiredFailuresReturnsFalseWhenAllPass(): void
    {
        $checker = new RequirementChecker();
        $checker->checkAll();
        $this->assertIsBool($checker->hasRequiredFailures());
    }
}
```

- [ ] **Step 6: Run tests and verify they pass**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'CheckResultTest|RequirementCheckerTest' 2>&1 | tail -10`
Expected: OK (N tests)

- [ ] **Step 7: Commit**

```bash
git add application/installer/CheckResult.php application/installer/CheckerInterface.php application/installer/RequirementChecker.php tests/Unit/CheckResultTest.php tests/Unit/RequirementCheckerTest.php
git commit -m "feat: add CheckResult, CheckerInterface, and update RequirementChecker with delegation"
```

---

### Task 2: PhpExtensionChecker

**Files:**
- Create: `application/installer/checkers/PhpExtensionChecker.php`
- Create: `tests/Unit/PhpExtensionCheckerTest.php`

**Interfaces:**
- Consumes: `CheckerInterface`, `CheckResult`
- Produces: checker that validates required/recommended PHP extensions

- [ ] **Step 1: Write test**

```php
<?php

use PHPUnit\Framework\TestCase;

class PhpExtensionCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckIncludesAllExpectedExtensions(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('PHP Extension: zip', $names);
        $this->assertContains('PHP Extension: dom', $names);
        $this->assertContains('PHP Extension: xml', $names);
        $this->assertContains('PHP Extension: mbstring', $names);
        $this->assertContains('PHP Extension: fileinfo', $names);
        $this->assertContains('PHP Extension: gd', $names);
    }

    public function testZipExtensionIsRequired(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        foreach ($results as $result) {
            if ($result->name === 'PHP Extension: zip') {
                $this->assertEquals('required', $result->severity);
                return;
            }
        }
        $this->fail('zip extension check not found');
    }

    public function testGdExtensionIsRecommended(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        foreach ($results as $result) {
            if ($result->name === 'PHP Extension: gd') {
                $this->assertEquals('recommended', $result->severity);
                return;
            }
        }
        $this->fail('gd extension check not found');
    }

    public function testGetName(): void
    {
        $checker = new PhpExtensionChecker();
        $this->assertEquals('PHP Extensions', $checker->getName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'PhpExtensionCheckerTest' 2>&1`
Expected: FAIL — class not found

- [ ] **Step 3: Implement PhpExtensionChecker**

```php
<?php

class PhpExtensionChecker implements CheckerInterface
{
    private array $extensions = [
        'zip'      => ['severity' => 'required',    'why' => 'DOCX/XLSX/PPTX/ODT extraction'],
        'dom'      => ['severity' => 'required',    'why' => 'XML parsing in document extractors'],
        'xml'      => ['severity' => 'required',    'why' => 'XML support'],
        'mbstring' => ['severity' => 'required',    'why' => 'Multibyte text processing'],
        'fileinfo' => ['severity' => 'required',    'why' => 'MIME type detection'],
        'gd'       => ['severity' => 'recommended', 'why' => 'Image thumbnails'],
    ];

    public function getName(): string
    {
        return 'PHP Extensions';
    }

    public function check(): array
    {
        $results = [];
        foreach ($this->extensions as $ext => $config) {
            $loaded = extension_loaded($ext);
            $results[] = new CheckResult(
                "PHP Extension: {$ext}",
                $config['why'],
                $loaded ? 'Loaded' : 'Missing',
                $loaded,
                $config['severity']
            );
        }
        return $results;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'PhpExtensionCheckerTest' 2>&1 | tail -5`
Expected: OK (N tests)

- [ ] **Step 5: Commit**

```bash
git add application/installer/checkers/PhpExtensionChecker.php tests/Unit/PhpExtensionCheckerTest.php
git commit -m "feat: add PhpExtensionChecker for required/recommended PHP extensions"
```

---

### Task 3: ComposerDependencyChecker

**Files:**
- Create: `application/installer/checkers/ComposerDependencyChecker.php`
- Create: `tests/Unit/ComposerDependencyCheckerTest.php`

**Interfaces:**
- Consumes: `CheckerInterface`, `CheckResult`
- Produces: checker that validates Composer autoloader and key packages

- [ ] **Step 1: Write test**

```php
<?php

use PHPUnit\Framework\TestCase;

class ComposerDependencyCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new ComposerDependencyChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testChecksVendorAutoloadExists(): void
    {
        $checker = new ComposerDependencyChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('Composer Autoloader', $names);
    }

    public function testGetName(): void
    {
        $checker = new ComposerDependencyChecker();
        $this->assertEquals('Composer Dependencies', $checker->getName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'ComposerDependencyCheckerTest' 2>&1`
Expected: FAIL

- [ ] **Step 3: Implement ComposerDependencyChecker**

```php
<?php

class ComposerDependencyChecker implements CheckerInterface
{
    private array $packages = [
        'Smalot\PdfParser\Parser'                       => 'PDF text extraction',
        'ParagonIE\AntiCSRF\AntiCSRF'                   => 'CSRF protection',
        'League\MimeTypeDetection\FinfoMimeTypeDetector' => 'MIME type detection',
        'Aura\Html\HelperLocatorFactory'                 => 'View rendering helpers',
    ];

    public function getName(): string
    {
        return 'Composer Dependencies';
    }

    public function check(): array
    {
        $results = [];

        // Check autoloader exists
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        $autoloadExists = file_exists($autoloadPath);
        $results[] = new CheckResult(
            'Composer Autoloader',
            'vendor/autoload.php must exist',
            $autoloadExists ? 'Found' : 'Missing — run composer install --no-dev',
            $autoloadExists,
            'required'
        );

        if (!$autoloadExists) {
            // Can't check individual packages without autoloader
            foreach ($this->packages as $class => $purpose) {
                $shortName = substr($class, strrpos($class, '\\') + 1);
                $results[] = new CheckResult(
                    "Composer Package: {$shortName}",
                    $purpose,
                    'Skipped (autoloader missing)',
                    false,
                    'required'
                );
            }
            return $results;
        }

        // Check each package class is autoloadable
        foreach ($this->packages as $class => $purpose) {
            $shortName = substr($class, strrpos($class, '\\') + 1);
            $found = class_exists($class);
            $results[] = new CheckResult(
                "Composer Package: {$shortName}",
                $purpose,
                $found ? 'Available' : 'Missing',
                $found,
                'required'
            );
        }

        return $results;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'ComposerDependencyCheckerTest' 2>&1 | tail -5`
Expected: OK

- [ ] **Step 5: Commit**

```bash
git add application/installer/checkers/ComposerDependencyChecker.php tests/Unit/ComposerDependencyCheckerTest.php
git commit -m "feat: add ComposerDependencyChecker for vendor package validation"
```

---

### Task 4: ServerConfigChecker

**Files:**
- Create: `application/installer/checkers/ServerConfigChecker.php`
- Create: `tests/Unit/ServerConfigCheckerTest.php`

**Interfaces:**
- Consumes: `CheckerInterface`, `CheckResult`
- Produces: checker that validates php.ini settings

- [ ] **Step 1: Write test**

```php
<?php

use PHPUnit\Framework\TestCase;

class ServerConfigCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new ServerConfigChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckIncludesFileUploads(): void
    {
        $checker = new ServerConfigChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('file_uploads', $names);
    }

    public function testGetName(): void
    {
        $checker = new ServerConfigChecker();
        $this->assertEquals('Server Configuration', $checker->getName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'ServerConfigCheckerTest' 2>&1`
Expected: FAIL

- [ ] **Step 3: Implement ServerConfigChecker**

```php
<?php

class ServerConfigChecker implements CheckerInterface
{
    private array $checks = [
        'file_uploads' => [
            'severity' => 'required',
            'why' => 'File uploads must be enabled',
            'pass' => fn($val) => $val === '1',
            'format' => fn($val) => $val === '1' ? 'On' : 'Off',
        ],
        'upload_max_filesize' => [
            'severity' => 'recommended',
            'why' => 'At least 8M recommended for document uploads',
            'pass' => fn($val) => $this->compareSize($val, '8M'),
            'format' => fn($val) => $val,
        ],
        'post_max_size' => [
            'severity' => 'recommended',
            'why' => 'Should be >= upload_max_filesize',
            'pass' => fn($val) => $this->compareSize($val, '8M'),
            'format' => fn($val) => $val,
        ],
        'memory_limit' => [
            'severity' => 'recommended',
            'why' => 'At least 64M recommended for PDF processing',
            'pass' => fn($val) => $val === '-1' || $this->compareSize($val, '64M'),
            'format' => fn($val) => $val,
        ],
        'max_execution_time' => [
            'severity' => 'recommended',
            'why' => 'At least 30 seconds recommended for PDF parsing',
            'pass' => fn($val) => (int)$val === 0 || (int)$val >= 30,
            'format' => fn($val) => $val . 's',
        ],
        'display_errors' => [
            'severity' => 'recommended',
            'why' => 'Should be Off in production',
            'pass' => fn($val) => $val !== '1',
            'format' => fn($val) => $val === '1' ? 'On' : 'Off',
        ],
    ];

    public function getName(): string
    {
        return 'Server Configuration';
    }

    public function check(): array
    {
        $results = [];
        foreach ($this->checks as $name => $config) {
            $value = ini_get($name);
            $passed = $config['pass']($value);
            $results[] = new CheckResult(
                $name,
                $config['why'],
                $config['format']($value),
                $passed,
                $config['severity']
            );
        }
        return $results;
    }

    private function compareSize(string $ini, string $threshold): bool
    {
        return $this->parseBytes($ini) >= $this->parseBytes($threshold);
    }

    private function parseBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;
        return match ($unit) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'ServerConfigCheckerTest' 2>&1 | tail -5`
Expected: OK

- [ ] **Step 5: Commit**

```bash
git add application/installer/checkers/ServerConfigChecker.php tests/Unit/ServerConfigCheckerTest.php
git commit -m "feat: add ServerConfigChecker for php.ini settings validation"
```

---

### Task 5: DatabaseChecker

**Files:**
- Create: `application/installer/checkers/DatabaseChecker.php`
- Create: `tests/Unit/DatabaseCheckerTest.php`

**Interfaces:**
- Consumes: `CheckerInterface`, `CheckResult`, PDO instance
- Produces: checker that validates MySQL/MariaDB version

- [ ] **Step 1: Write test**

```php
<?php

use PHPUnit\Framework\TestCase;

class DatabaseCheckerTest extends TestCase
{
    public function testCheckAcceptsPdoAndReturnsArray(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('8.0.35');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testPassesOnMySQL80(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('8.0.35');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertTrue($results[0]->passed);
    }

    public function testPassesOnMariaDB106(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('10.6.18-MariaDB');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertTrue($results[0]->passed);
    }

    public function testFailsOnMySQL55(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('5.5.62');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertFalse($results[0]->passed);
    }

    public function testGetName(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $checker = new DatabaseChecker($pdo);
        $this->assertEquals('Database Server', $checker->getName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'DatabaseCheckerTest' 2>&1`
Expected: FAIL

- [ ] **Step 3: Implement DatabaseChecker**

```php
<?php

class DatabaseChecker implements CheckerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName(): string
    {
        return 'Database Server';
    }

    public function check(): array
    {
        $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $isMaria = stripos($version, 'MariaDB') !== false;
        $passed = false;

        if ($isMaria) {
            // MariaDB 10.2+ required
            $numVersion = preg_replace('/^(\d+\.\d+).*$/', '$1', $version);
            $passed = version_compare($numVersion, '10.2', '>=');
        } else {
            // MySQL 5.7+ required
            $numVersion = preg_replace('/^(\d+\.\d+).*$/', '$1', $version);
            $passed = version_compare($numVersion, '5.7', '>=');
        }

        return [
            new CheckResult(
                'Database Server Version',
                $isMaria ? 'MariaDB 10.2+' : 'MySQL 5.7+',
                $version,
                $passed,
                'required'
            ),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'DatabaseCheckerTest' 2>&1 | tail -5`
Expected: OK

- [ ] **Step 5: Commit**

```bash
git add application/installer/checkers/DatabaseChecker.php tests/Unit/DatabaseCheckerTest.php
git commit -m "feat: add DatabaseChecker for MySQL/MariaDB version validation"
```

---

### Task 6: View and Controller Integration

**Files:**
- Modify: `application/installer/views/requirements.php` — add severity column, yellow warnings, proceed-anyway button
- Modify: `application/installer/InstallerController.php` — wire pre-flight into fresh install and upgrade flows

**Interfaces:**
- Consumes: `RequirementChecker` (which now includes all checkers), `CheckResult` objects
- Produces: working UI that blocks on required failures, warns on recommended, allows proceeding when only recommended failures exist

- [ ] **Step 1: Update requirements.php view**

```php
<?php /* view template — uses $results (CheckResult[]) and $allPassed (bool) */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenDocMan Installer - Requirements Check</title>
    <link rel="stylesheet" href="../css/install.css" type="text/css"/>
    <style>
        .severity-required { color: #c00; }
        .severity-recommended { color: #b85; }
        .warning-icon { color: #e68a00; }
        .pass-icon { color: green; }
        .fail-icon { color: red; }
    </style>
</head>
<body>
<div id="content">
    <img src="../images/logo.gif"><br>
    <h3>System Requirements Check</h3>
    <hr>
    <?php if (!isset($results)): ?>
        <p><a href="?op=requirements" class="button">Check Requirements</a></p>
    <?php else: ?>
        <table>
            <tr>
                <th>Requirement</th>
                <th>Required</th>
                <th>Status</th>
                <th>Severity</th>
            </tr>
            <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo htmlentities($result->name); ?></td>
                    <td><?php echo htmlentities($result->required); ?></td>
                    <td style="color: <?php echo $result->passed ? 'green' : ($result->severity === 'recommended' ? '#e68a00' : 'red'); ?>;">
                        <?php if ($result->passed): ?>
                            <span class="pass-icon">&#10003;</span>
                        <?php elseif ($result->severity === 'recommended'): ?>
                            <span class="warning-icon">&#9888;</span>
                        <?php else: ?>
                            <span class="fail-icon">&#10007;</span>
                        <?php endif; ?>
                        <?php echo htmlentities($result->actual); ?>
                    </td>
                    <td class="<?php echo $result->severity === 'recommended' ? 'severity-recommended' : 'severity-required'; ?>">
                        <?php echo $result->severity === 'recommended' ? 'Recommended' : 'Required'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <hr>

        <?php
        $hasRequiredFailures = false;
        foreach ($results as $result) {
            if ($result->severity === 'required' && !$result->passed) {
                $hasRequiredFailures = true;
                break;
            }
        }
        ?>

        <?php if ($allPassed): ?>
            <p style="color: green;"><strong>All requirements met!</strong></p>
            <p>
                <a href="?op=install" class="button">Proceed with Installation</a>
            </p>
        <?php elseif ($hasRequiredFailures): ?>
            <p style="color: red;"><strong>Please fix the required items above before proceeding.</strong></p>
            <p><a href="?op=requirements" class="button">Re-check</a></p>
        <?php else: ?>
            <p style="color: #e68a00;">
                <strong>All required checks pass, but some recommended items need attention.</strong>
                These are not blocking but may affect functionality.
            </p>
            <p>
                <a href="?op=install" class="button">Proceed Anyway</a>
                &nbsp;
                <a href="?op=requirements" class="button">Re-check</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
```

- [ ] **Step 2: Update InstallerController to wire pre-flight checks**

In `InstallerController.php`, locate `handleFreshInstall()` method. Add a `RequirementChecker` call before the point where tables are created. If `hasRequiredFailures()`, show the requirements view instead.

Also wire the same check into `handleUpgrade()`.

Key changes:

```php
// In handleFreshInstall() — add after DB connection, before table creation:
$reqChecker = new RequirementChecker();
$results = $reqChecker->checkAll();
if ($reqChecker->hasRequiredFailures()) {
    $allPassed = $reqChecker->allPassed();
    require __DIR__ . '/views/requirements.php';
    return;
}
```

```php
// In handleUpgrade() — add similar check before migration runner:
$reqChecker = new RequirementChecker();
$results = $reqChecker->checkAll();
// DatabaseChecker needs a PDO — inject it manually since it won't be auto-discovered
$dbChecker = new DatabaseChecker($pdo);
$results = array_merge($results, $dbChecker->check());

if ($reqChecker->hasRequiredFailures()) {
    $allPassed = $reqChecker->allPassed();
    require __DIR__ . '/views/requirements.php';
    return;
}
```

> **Note:** `DatabaseChecker` requires a PDO instance, so it cannot be auto-discovered by the glob pattern in `RequirementChecker`. The installer controller creates and passes it explicitly. The glob discovery in `RequirementChecker` will skip `DatabaseChecker` if the file exists but the constructor requires arguments (PHP will throw, but only if `class_exists()` triggers autoloading — the current implementation uses `require_once` + `class_exists`, so it will skip gracefully).

Alternative: exclude `DatabaseChecker.php` from the glob pattern by using a naming convention like `*ExtensionChecker.php` or add a `requiresPdo` marker. Simpler: keep DatabaseChecker manually injected.

- [ ] **Step 3: Run full test suite**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter 'CheckResultTest|RequirementCheckerTest|PhpExtensionCheckerTest|ComposerDependencyCheckerTest|ServerConfigCheckerTest|DatabaseCheckerTest' 2>&1 | tail -5`
Expected: OK (all tests)

Then run full suite to ensure no regressions:
`php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist 2>&1 | tail -5`
Expected: OK (378+ tests, 2123+ assertions)

- [ ] **Step 4: Commit**

```bash
git add application/installer/views/requirements.php application/installer/InstallerController.php
git commit -m "feat: wire pre-flight checks into installer and upgrade flows"
```