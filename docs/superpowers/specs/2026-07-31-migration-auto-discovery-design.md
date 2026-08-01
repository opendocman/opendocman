# Migration Auto-Discovery

## Problem

Adding a new database migration requires editing 3 files at up to 7 locations:

- `InstallerController.php`: `require_once` + `new VersionNNNN()` in `handleFreshInstall` and `handleUpgrade`
- `cli.php`: `require_once` + `new VersionNNNN()` in `migrate` and `status`
- `controllers/install/index.php`: `require_once`

These lists are duplicated verbatim, and `controllers/install/index.php` is already out of sync (missing the last 4 migrations).

## Solution

Replace all hardcoded `require_once` + `new VersionNNNN()` lists with a single `MigrationLoader` class that auto-discovers migration files via `glob()`.

### New file: `application/installer/MigrationLoader.php`

```php
class MigrationLoader
{
    public static function getAll(): array
    {
        require_once __DIR__ . '/migrations/MigrationInterface.php';

        $files = glob(__DIR__ . '/migrations/Version*.php');
        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            require_once $file;
            $className = basename($file, '.php');
            $migration = new $className();
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException("{$className} must implement MigrationInterface");
            }
            $migrations[] = $migration;
        }

        usort($migrations, fn($a, $b) => version_compare($a->getVersion(), $b->getVersion()));
        return $migrations;
    }
}
```

### Modified files

**`application/installer/InstallerController.php`:**
- Remove 22 `require_once __DIR__ . '/migrations/Version*.php';` lines
- Add `require_once __DIR__ . '/MigrationLoader.php';`
- Replace both `registerMigrations([...])` blocks with `$runner->registerMigrations(MigrationLoader::getAll());`

**`application/installer/cli.php`:**
- Remove 22 `require_once __DIR__ . '/migrations/Version*.php';` lines
- Add `require_once __DIR__ . '/MigrationLoader.php';`
- Replace both `registerMigrations([...])` blocks with `$runner->registerMigrations(MigrationLoader::getAll());`

**`application/controllers/install/index.php`:**
- Remove 22 `require_once __DIR__ . '/../../installer/migrations/Version*.php';` lines
- No new require needed — `MigrationLoader` is loaded transitively via `InstallerController.php`

### Migration interface require

`MigrationInterface.php` is now loaded by `MigrationLoader` (before any Version class that depends on it). The explicit `require_once` in the 3 entry files can be removed since `require_once` on the interface within `MigrationLoader` covers all uses.

### Workflow for adding a new migration

1. Create `Version001700.php` in `application/installer/migrations/`
2. Bump `ODM_DB_VERSION` in `application/version.php`

That's it — no changes to entry point files.

### Updated guidance (AGENTS.md)

The "Database schema changes" section will be updated to document the new workflow.

## Testing

- `php -l` on all modified files to verify syntax
- Run existing PHPUnit tests to ensure nothing is broken
- Run Playwright E2E smoke test to verify installer still works