# Installer Pre-Flight Checks

Date: 2026-08-07
Status: Design approved, ready for implementation

## Problem

The OpenDocMan installer and upgrader do not validate that the server environment
has the required PHP extensions, Composer dependencies, or server configuration
settings before attempting installation. Users encounter fatal errors (like the
PDF parsing crash in issue #415) only after the app is supposedly installed,
leading to confusion about whether it's a bug or a misconfiguration.

## Design

### Value Object: `CheckResult`

```php
class CheckResult {
    string $name;        // e.g. "PHP Extension: zip"
    string $required;    // e.g. "Required for DOCX/XLSX/PPTX/ODT extraction"
    string $actual;      // e.g. "Loaded" or "Missing"
    bool $passed;
    string $severity;    // "required" | "recommended"
}
```

### Interface: `CheckerInterface`

```php
interface CheckerInterface {
    /** @return CheckResult[] */
    public function check(): array;
    public function getName(): string;
}
```

### Checker Classes

All live in `application/installer/checkers/` and are auto-discovered by
`RequirementChecker` (similar to how `MigrationLoader` discovers migrations).

#### `PhpExtensionChecker`

Checks that required/recommended PHP extensions are loaded via
`extension_loaded()`.

| Extension | Severity  | Why                                    |
|-----------|-----------|----------------------------------------|
| pdo       | required  | Database access                        |
| pdo_mysql | required  | MySQL driver                           |
| zip       | required  | DOCX/XLSX/PPTX/ODT extraction          |
| dom       | required  | XML parsing in all extractors          |
| xml       | required  | XML support                            |
| mbstring  | required  | Multibyte text processing              |
| fileinfo  | required  | MIME type detection                    |
| gd        | recommended | Image thumbnails                     |

#### `ComposerDependencyChecker`

Verifies Composer autoloader exists, then probes whether key classes are
autoloadable (by checking if the class exists without actually instantiating).

- `vendor/autoload.php` exists (required)
- `Smalot\PdfParser\Parser` class exists (required)
- `ParagonIE\AntiCSRF\AntiCSRF` class exists (required)
- `League\MimeTypeDetection\FinfoMimeTypeDetector` class exists (required)
- `Aura\Html\HelperLocatorFactory` class exists (required)

If the vendor directory is missing entirely, the failure message tells the user
to run `composer install --no-dev`.

#### `ServerConfigChecker`

Reads `ini_get()` for PHP configuration values.

| Setting             | Threshold        | Severity    |
|---------------------|------------------|-------------|
| file_uploads        | On               | required    |
| upload_max_filesize | >= 8M            | recommended |
| post_max_size       | >= 8M            | recommended |
| memory_limit        | >= 64M           | recommended |
| max_execution_time  | >= 30            | recommended |
| display_errors      | Off              | recommended |

#### `DatabaseChecker`

Accepts a PDO instance in the constructor. Queries `SELECT VERSION()`.

- MySQL 5.7+ or MariaDB 10.2+ (required)

### Updated `RequirementChecker`

`checkAll()` now creates each checker class, runs `check()`, and flattens all
`CheckResult[]` arrays. The existing inline checks (PHP version, PDO driver,
writable dirs) are either migrated into the new checkers or kept in-place
alongside the new delegate calls.

New method `hasRequiredFailures()` returns true if any required-severity check
failed. `allPassed()` is updated to exclude recommended failures.

### Updated View: `requirements.php`

A `Severity` column is added to the results table. Results with
`severity: recommended && !passed` render as yellow warnings. The "Proceed"
button is available when no required failures exist (recommended-only failures
are non-blocking). The "Proceed" button is never shown when required failures
exist.

### Integration Points

- **Fresh install** (`handleFreshInstall`): calls `RequirementChecker::checkAll()`
  before creating tables. If required failures exist, shows the requirements
  view; user must fix them to proceed.
- **Upgrade** (`handleUpgrade`): also calls `RequirementChecker::checkAll()`
  before running migrations. Same behavior — required failures block the
  upgrade.
- **Intro screen** (`showIntro`): adds a "Check Requirements" link visible
  regardless of install state, so users can inspect their environment anytime.

### Discovery

Checkers in `application/installer/checkers/` implementing `CheckerInterface`
are auto-discovered by `RequirementChecker` via `glob()` matching
`*Checker.php` — no manual registration needed, matching the `MigrationLoader`
pattern.

## Files Changed

| File | Change |
|------|--------|
| `application/installer/CheckerInterface.php` | New |
| `application/installer/CheckResult.php` | New |
| `application/installer/checkers/PhpExtensionChecker.php` | New |
| `application/installer/checkers/ComposerDependencyChecker.php` | New |
| `application/installer/checkers/ServerConfigChecker.php` | New |
| `application/installer/checkers/DatabaseChecker.php` | New |
| `application/installer/RequirementChecker.php` | Refactored to use checkers + auto-discovery |
| `application/installer/views/requirements.php` | Add severity column, yellow warnings, proceed anyway button |
| `application/installer/InstallerController.php` | Wire up pre-flight check in fresh install and upgrade flows |