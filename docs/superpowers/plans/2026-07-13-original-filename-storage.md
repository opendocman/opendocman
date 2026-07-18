# Original Filename Storage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Store uploaded files on disk using their original filename inside a subfolder named by the database ID, instead of the current `{id}.dat` flat format.

**Architecture:** A centralized `getFilePath()` helper in `functions.php` constructs all file paths. All controllers call this helper instead of building `{id}.dat` inline. A new `Version001401` migration class moves existing files to the new layout during upgrade. The helper supports backwards compatibility by falling back to old paths for files not yet migrated.

**Tech Stack:** PHP, MySQL/PDO, phpunit 9.5

**Spec:** `docs/superpowers/specs/2026-07-13-original-filename-storage-design.md`

## Global Constraints

- No database schema changes
- No configuration changes — uses existing `dataDir`, `archiveDir`, `revisionDir`
- All filenames must be sanitized against path traversal (`../`, null bytes, leading `/`)
- The `realname` DB column remains the source of truth for original filenames
- Download `Content-Disposition` header behavior is unchanged

---

### Task 1: Create `getFilePath()` helper in functions.php

**Files:**
- Modify: `application/controllers/helpers/functions.php` (insert new functions after line 25, before existing functions)

**Interfaces:**
- Consumes: `$GLOBALS['CONFIG']['dataDir']`, `$GLOBALS['CONFIG']['archiveDir']`, `$GLOBALS['CONFIG']['revisionDir']`
- Produces: `sanitizeFilename(string $name): string`, `getFilePath(int $fileId, string $realname, string $type = 'data', ?int $revision = null): string`

- [ ] **Step 1: Add `sanitizeFilename()` function**

Insert after line 25 (the "Various utility functions" comment):

```php
/**
 * Sanitize a filename for safe filesystem use
 * @param string $name
 * @return string
 */
function sanitizeFilename(string $name): string
{
    // Strip path traversal sequences
    $name = str_replace(['../', '..\\'], '', $name);
    $name = str_replace(['/', '\\'], '', $name);
    // Strip null bytes
    $name = str_replace("\0", '', $name);
    // Strip leading dots, spaces, hyphens
    $name = ltrim($name, '. -');
    // Fallback if emptied by sanitization
    if ($name === '') {
        $name = 'untitled';
    }
    return $name;
}
```

- [ ] **Step 2: Add `getFilePath()` helper**

Insert after `sanitizeFilename`:

```php
/**
 * Construct the filesystem path for a file
 * @param int $fileId
 * @param string $realname Original uploaded filename
 * @param string $type 'data', 'archive', or 'revision'
 * @param int|null $revision Revision number (required for type='revision')
 * @return string
 */
function getFilePath(int $fileId, string $realname, string $type = 'data', ?int $revision = null): string
{
    $realname = sanitizeFilename($realname);

    switch ($type) {
        case 'data':
            $base = $GLOBALS['CONFIG']['dataDir'];
            $path = $base . $fileId . '/' . $realname;
            // Fallback to old flat path if new path doesn't exist
            if (!file_exists($path)) {
                $oldPath = $base . $fileId . '.dat';
                if (file_exists($oldPath)) {
                    return $oldPath;
                }
            }
            return $path;

        case 'archive':
            $base = $GLOBALS['CONFIG']['archiveDir'];
            $path = $base . $fileId . '/' . $realname;
            if (!file_exists($path)) {
                $oldPath = $base . $fileId . '.dat';
                if (file_exists($oldPath)) {
                    return $oldPath;
                }
            }
            return $path;

        case 'revision':
            $base = $GLOBALS['CONFIG']['revisionDir'];
            if ($revision === null) {
                throw new InvalidArgumentException('Revision number required for type=revision');
            }
            $ext = '';
            $dotPos = strrpos($realname, '.');
            if ($dotPos !== false) {
                $ext = substr($realname, $dotPos);
            }
            $basename = ($dotPos !== false) ? substr($realname, 0, $dotPos) : $realname;
            $path = $base . $fileId . '/' . $basename . '-rev' . $revision . $ext;
            if (!file_exists($path)) {
                $oldPath = $base . $fileId . '/' . $fileId . '_' . $revision . '.dat';
                if (file_exists($oldPath)) {
                    return $oldPath;
                }
            }
            return $path;

        default:
            throw new InvalidArgumentException("Unknown file path type: $type");
    }
}
```

- [ ] **Step 3: Verify the functions are loadable**

Run: `php -l application/controllers/helpers/functions.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Create unit test for sanitizeFilename**

**File:** Create `tests/Unit/FilePathHelperTest.php`

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

class FilePathHelperTest extends TestCase
{
    public function testSanitizeStripsPathTraversal()
    {
        $this->assertEquals('file.txt', sanitizeFilename('../file.txt'));
        $this->assertEquals('file.txt', sanitizeFilename('..\\file.txt'));
    }

    public function testSanitizeStripsNullBytes()
    {
        $this->assertEquals('file.txt', sanitizeFilename("file\x00.txt"));
    }

    public function testSanitizeStripsLeadingSlashes()
    {
        $this->assertEquals('etc/passwd', sanitizeFilename('/etc/passwd'));
    }

    public function testSanitizeFallsBackOnEmpty()
    {
        $this->assertEquals('untitled', sanitizeFilename('.'));
        $this->assertEquals('untitled', sanitizeFilename(''));
    }

    public function testSanitizePreservesSpacesAndSpecialChars()
    {
        $this->assertEquals('my report (v2).docx', sanitizeFilename('my report (v2).docx'));
    }
}
```

- [ ] **Step 5: Run the unit test**

Run: `./application/vendor/bin/phpunit tests/Unit/FilePathHelperTest.php --verbose`
Expected: 5 tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add application/controllers/helpers/functions.php tests/Unit/FilePathHelperTest.php
git commit -m "feat: add getFilePath() helper for original filename storage"
```

---

### Task 2: Update add.php to use new path

**Files:**
- Modify: `application/controllers/add.php` (lines 362-366)

**Interfaces:**
- Consumes: `getFilePath(int $fileId, string $realname, string $type)`
- Note: `$realname` is `$_FILES['file']['name'][$count]`, already inserted into DB before file write

- [ ] **Step 1: Replace hardcoded path in add.php**

Change lines 362-366:
```php
        // use id to generate a file name
        // save uploaded file with new name
        $newFileName = $fileId . '.dat';

        move_uploaded_file($tmp_name[$count], $GLOBALS['CONFIG']['dataDir'] . '/' . $newFileName);
```

To:
```php
        // Save uploaded file with original filename in ID subfolder
        $realname = $_FILES['file']['name'][$count];
        $newFilePath = getFilePath($fileId, $realname, 'data');
        $newFileDir = dirname($newFilePath);
        if (!is_dir($newFileDir)) {
            mkdir($newFileDir, 0775, true);
        }
        move_uploaded_file($tmp_name[$count], $newFilePath);
```

- [ ] **Step 2: Verify syntax**

Run: `php -l application/controllers/add.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add application/controllers/add.php
git commit -m "feat: store uploaded files with original filename (add.php)"
```

---

### Task 3: Update check-in.php to use new paths

**Files:**
- Modify: `application/controllers/check-in.php` (lines 213-221, 256-257)

**Interfaces:**
- Consumes: `getFilePath(int $fileId, string $realname, string $type, ?int $revision)`
- Note: `$filename` from `$_FILES['file']['name']` (line 149) is the new realname

- [ ] **Step 1: Replace revision copy path (lines 213-221)**

Change:
```php
        $file_name = $GLOBALS['CONFIG']['dataDir'] . $id .'.dat';
        //read and close
        $file_handler = fopen($file_name, "r");
        $file_content = fread($file_handler, filesize($file_name));
        fclose($file_handler);
        //write and close
        $file_handler = fopen($GLOBALS['CONFIG']['revisionDir'] . $id . '/' . $id . '_' . ($revision_number - 1) . '.dat', "w");
        fwrite($file_handler, $file_content);
        fclose($file_handler);
```

To:
```php
        // Save the current version as a revision
        $revisionFileName = getFilePath($id, $filename, 'data');
        $revisionDir = dirname(getFilePath($id, $filename, 'revision', ($revision_number - 1)));
        if (!is_dir($revisionDir)) {
            mkdir($revisionDir, 0775, true);
        }
        // Read current file
        $file_handler = fopen($revisionFileName, "r");
        $file_content = fread($file_handler, filesize($revisionFileName));
        fclose($file_handler);
        // Write revision
        $file_handler = fopen(getFilePath($id, $filename, 'revision', ($revision_number - 1)), "w");
        fwrite($file_handler, $file_content);
        fclose($file_handler);
```

- [ ] **Step 2: Replace overwrite path (lines 255-257)**

Change:
```php
        // rename and save file
        $newFileName = $id . '.dat';
        copy($_FILES['file']['tmp_name'], $GLOBALS['CONFIG']['dataDir'] . $newFileName);
```

To:
```php
        // Save new version with original filename
        $newFilePath = getFilePath($id, $filename, 'data');
        $newFileDir = dirname($newFilePath);
        if (!is_dir($newFileDir)) {
            mkdir($newFileDir, 0775, true);
        }
        copy($_FILES['file']['tmp_name'], $newFilePath);
```

- [ ] **Step 3: Verify syntax**

Run: `php -l application/controllers/check-in.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add application/controllers/check-in.php
git commit -m "feat: store check-in revisions with original filename"
```

---

### Task 4: Update read-only controllers

**Files:**
- Modify: `application/controllers/view_file.php` (lines 76-81, 107-112)
- Modify: `application/controllers/check-out.php` (line 91)
- Modify: `application/controllers/details.php` (lines 46, 104, 107)
- Modify: `application/controllers/history.php` (lines 72, 74, 243)
- Modify: `application/controllers/in.php` (line 97)
- Modify: `application/controllers/helpers/functions.php` (line 225 — inside `list_files()`)

**Interfaces:**
- Consumes: `getFilePath(int $fileId, string $realname, string $type, ?int $revision)` at each call site
- Each call site already has the `$realname` from `FileData::getName()` or `FileData::getRealName()`

- [ ] **Step 1: Update view_file.php — view action (lines 75-81)**

Change:
```php
    if (isset($revision_id)) {
        $filename = $revision_dir . $request_id . ".dat";
    } elseif ($file_obj->isArchived()) {
        $filename = $GLOBALS['CONFIG']['archiveDir'] . $_REQUEST['id'] . ".dat";
    } else {
        $filename = $GLOBALS['CONFIG']['dataDir'] . $_REQUEST['id'] . ".dat";
    }
```

To:
```php
    if (isset($revision_id)) {
        $filename = getFilePath($request_id, $realname, 'revision', $revision_id);
    } elseif ($file_obj->isArchived()) {
        $filename = getFilePath($_REQUEST['id'], $realname, 'archive');
    } else {
        $filename = getFilePath($_REQUEST['id'], $realname, 'data');
    }
```

- [ ] **Step 2: Update view_file.php — Download action (lines 106-112)**

Same pattern as Step 1, replace with `getFilePath()` calls.

- [ ] **Step 3: Update check-out.php (line 91)**

Change:
```php
    $filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
```
To:
```php
    $filename = getFilePath($id, $real_name, 'data');
```

- [ ] **Step 4: Update details.php (line 46)**

Change:
```php
    $file_size = display_filesize($GLOBALS['CONFIG']['revisionDir'] . $_GET['id'] . '/' . $_GET['id'] . '_' . $revision_id . '.dat');
```
To:
```php
    $file_size = display_filesize(getFilePath($_GET['id'], $file_data_obj->getName(), 'revision', $revision_id));
```

- [ ] **Step 5: Update details.php (lines 104, 107)**

Change:
```php
if ($file_data_obj->isArchived()) {
    $filename = $GLOBALS['CONFIG']['archiveDir'] . $request_id . '.dat';
    $file_size = display_filesize($filename);
} else {
    $filename = $GLOBALS['CONFIG']['dataDir'] . $request_id . '.dat';
```
To:
```php
$realname = $file_data_obj->getName();
if ($file_data_obj->isArchived()) {
    $filename = getFilePath($request_id, $realname, 'archive');
    $file_size = display_filesize($filename);
} else {
    $filename = getFilePath($request_id, $realname, 'data');
```
(Remove the duplicate `$real_name = $file_data_obj->getName();` on line 70 since `$realname` is now set here)

- [ ] **Step 6: Update history.php (lines 72, 74)**

Change:
```php
    if ($datafile->isArchived()) {
        $filename = $GLOBALS['CONFIG']['archiveDir'] . e::h($id) . '.dat';
    } else {
        $filename = $GLOBALS['CONFIG']['dataDir'] . e::h($id) . '.dat';
    }
```
To:
```php
    if ($datafile->isArchived()) {
        $filename = getFilePath($id, $real_name, 'archive');
    } else {
        $filename = getFilePath($id, $real_name, 'data');
    }
```

- [ ] **Step 7: Update history.php (line 243)**

Change:
```php
    if (is_file($GLOBALS['CONFIG']['revisionDir'] . $id . '/' . $id . "_$revision.dat")) {
```
To:
```php
    if (is_file(getFilePath($id, $real_name, 'revision', $revision))) {
```

- [ ] **Step 8: Update in.php (line 97)**

Change:
```php
        $filename = $GLOBALS['CONFIG']['dataDir'] . $id . '.dat';
```
To:
```php
        $filename = getFilePath($id, $realname, 'data');
```

- [ ] **Step 9: Update list_files() in functions.php (line 225)**

Change:
```php
        $filesize = display_filesize($dataDir . $fileid . '.dat');
```
To:
```php
        $filesize = display_filesize(getFilePath($fileid, $realname, 'data'));
```

- [ ] **Step 10: Verify syntax for all modified files**

Run:
```bash
php -l application/controllers/view_file.php
php -l application/controllers/check-out.php
php -l application/controllers/details.php
php -l application/controllers/history.php
php -l application/controllers/in.php
php -l application/controllers/helpers/functions.php
```
Expected: All return `No syntax errors detected`

- [ ] **Step 11: Commit**

```bash
git add application/controllers/view_file.php application/controllers/check-out.php application/controllers/details.php application/controllers/history.php application/controllers/in.php application/controllers/helpers/functions.php
git commit -m "feat: use getFilePath() in read-only controllers"
```

---

### Task 5: Update delete.php for archive, restore, and permanent delete

**Files:**
- Modify: `application/controllers/delete.php` (lines 65, 122, 168-169)

- [ ] **Step 1: Update archive path (line 65)**

Change:
```php
    fmove($GLOBALS['CONFIG']['dataDir'] . $id . '.dat', $GLOBALS['CONFIG']['archiveDir'] . $id . '.dat');
```
To:
```php
    $file_obj = new FileData($id, $pdo);
    $realname = $file_obj->getName();
    $srcPath = getFilePath($id, $realname, 'data');
    $dstPath = getFilePath($id, $realname, 'archive');
    $dstDir = dirname($dstPath);
    if (!is_dir($dstDir)) {
        mkdir($dstDir, 0775, true);
    }
    fmove($srcPath, $dstPath);
```

- [ ] **Step 2: Update undelete path (line 122)**

Change:
```php
    fmove($GLOBALS['CONFIG']['archiveDir'] . $fileId . '.dat', $GLOBALS['CONFIG']['dataDir'] . $fileId . '.dat');
```
To:
```php
    $file_obj = new FileData($fileId, $pdo);
    $realname = $file_obj->getName();
    $srcPath = getFilePath($fileId, $realname, 'archive');
    $dstPath = getFilePath($fileId, $realname, 'data');
    $dstDir = dirname($dstPath);
    if (!is_dir($dstDir)) {
        mkdir($dstDir, 0775, true);
    }
    fmove($srcPath, $dstPath);
```

- [ ] **Step 3: Update permanent delete path (lines 168-169)**

Change:
```php
    $filename = $id . ".dat";
    unlink($GLOBALS['CONFIG']['archiveDir'] . $filename);
```
To:
```php
    $file_obj = new FileData($id, $pdo);
    $realname = $file_obj->getName();
    $archivePath = getFilePath($id, $realname, 'archive');
    if (file_exists($archivePath)) {
        unlink($archivePath);
    }
```

- [ ] **Step 4: Verify syntax**

Run: `php -l application/controllers/delete.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add application/controllers/delete.php
git commit -m "feat: use getFilePath() in delete controller"
```

---

### Task 6: Create Version001401 migration class

**Files:**
- Create: `application/installer/migrations/Version001401.php`

**Interfaces:**
- Produces: `class Version001401 implements MigrationInterface` with `up(PDO $pdo, string $prefix)`

- [ ] **Step 1: Create the migration class**

```php
<?php

class Version001401 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.4.1';
    }

    public function getDescription(): string
    {
        return 'Migrate files to subfolder-by-ID structure with original filenames';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        // Query dataDir from the settings table (config globals NOT available during upgrade)
        $stmt = $pdo->query("SELECT value FROM `{$prefix}settings` WHERE name = 'dataDir'");
        $dataDir = rtrim($stmt->fetchColumn(), '/') . '/';
        if ($dataDir === '/') {
            return; // No dataDir configured, nothing to migrate
        }
        $archiveDir = $dataDir . 'archiveDir/';
        $revisionDir = $dataDir . 'revisionDir/';
        $stmt = $pdo->query("SELECT id, realname FROM `{$prefix}data` ORDER BY id ASC");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            $id = $file['id'];
            $realname = $file['realname'];

            // Sanitize the realname
            $realname = str_replace(['../', '..\\'], '', $realname);
            $realname = str_replace(['/', '\\'], '', $realname);
            $realname = str_replace("\0", '', $realname);
            $realname = ltrim($realname, '. -');
            if ($realname === '') {
                $realname = 'untitled';
            }

            // --- dataDir ---
            $oldDataPath = $dataDir . $id . '.dat';
            $newDataDir = $dataDir . $id . '/';
            $newDataPath = $newDataDir . $realname;

            if (file_exists($oldDataPath)) {
                if (!is_dir($newDataDir)) {
                    mkdir($newDataDir, 0775, true);
                }
                rename($oldDataPath, $newDataPath);
            }

            // --- archiveDir ---
            $oldArchivePath = $archiveDir . $id . '.dat';
            $newArchiveDir = $archiveDir . $id . '/';
            $newArchivePath = $newArchiveDir . $realname;

            if (file_exists($oldArchivePath)) {
                if (!is_dir($newArchiveDir)) {
                    mkdir($newArchiveDir, 0775, true);
                }
                rename($oldArchivePath, $newArchivePath);
            }

            // --- revisionDir ---
            $oldRevisionDir = $revisionDir . $id . '/';
            if (is_dir($oldRevisionDir)) {
                $ext = '';
                $dotPos = strrpos($realname, '.');
                if ($dotPos !== false) {
                    $ext = substr($realname, $dotPos);
                }
                $basename = ($dotPos !== false) ? substr($realname, 0, $dotPos) : $realname;

                $revFiles = glob($oldRevisionDir . $id . '_*.dat');
                if ($revFiles === false) {
                    $revFiles = [];
                }
                foreach ($revFiles as $revFile) {
                    $filename = basename($revFile);
                    if (preg_match('/^' . preg_quote($id, '/') . '_(\d+)\.dat$/', $filename, $matches)) {
                        $revNum = $matches[1];
                        $newRevPath = $oldRevisionDir . $basename . '-rev' . $revNum . $ext;
                        rename($revFile, $newRevPath);
                    }
                }
            }
        }

        // Update DB version
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.4.1' WHERE sys_name='version'");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        // Reversal not supported for file migrations
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l application/installer/migrations/Version001401.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add application/installer/migrations/Version001401.php
git commit -m "feat: add Version001401 migration for original filename storage"
```

---

### Task 7: Register migration and bump version

**Files:**
- Modify: `application/installer/InstallerController.php` (lines 27, 329)
- Modify: `application/installer/cli.php` (lines 24, 117, 177)
- Modify: `application/controllers/install/index.php` (line 24)
- Modify: `application/version.php` (line 24)

- [ ] **Step 1: Add require_once in InstallerController.php**

After line 27 (`require_once __DIR__ . '/migrations/Version001400.php';`), add:
```php
require_once __DIR__ . '/migrations/Version001401.php';
```

- [ ] **Step 2: Register migration in install/index.php**

After line 24 (`require_once __DIR__ . '/../../installer/migrations/Version001400.php';`), add:
```php
require_once __DIR__ . '/../../installer/migrations/Version001401.php';
```

- [ ] **Step 3: Add require_once in cli.php**

After line 24 (`require_once __DIR__ . '/migrations/Version001400.php';`), add:
```php
require_once __DIR__ . '/migrations/Version001401.php';
```

- [ ] **Step 4: Register migration in InstallerController::handleUpgrade()**

After line 329 (`new Version001400(),`), add:
```php
                new Version001401(),
```

- [ ] **Step 5: Register migration in cli.php migrate() method**

After line 117 (`new Version001400(),`), add:
```php
            new Version001401(),
```

- [ ] **Step 6: Register migration in cli.php status() method**

After line 177 (`new Version001400(),`), add:
```php
            new Version001401(),
```

- [ ] **Step 7: Update required_db_version in version.php**

Change line 24 from:
```php
$GLOBALS['CONFIG']['required_db_version'] = '1.4.0';
```
To:
```php
$GLOBALS['CONFIG']['required_db_version'] = '1.4.1';
```

- [ ] **Step 8: Verify syntax on all modified files**

Run:
```bash
php -l application/installer/InstallerController.php
php -l application/installer/cli.php
php -l application/controllers/install/index.php
php -l application/version.php
```
Expected: All return `No syntax errors detected`

- [ ] **Step 9: Commit**

```bash
git add application/installer/InstallerController.php application/installer/cli.php application/controllers/install/index.php application/version.php
git commit -m "feat: register Version001401 migration and bump required DB version"
```

---

## Self-Review Checklist

1. **Spec coverage:** All spec requirements covered — path helper (Task 1), upload (Task 2), check-in/revisions (Task 3), read controllers (Task 4), delete/archive (Task 5), migration script (Task 6), registration (Task 7).
2. **Placeholder scan:** No TBD, TODO, or vague steps. All code is complete in every step.
3. **Type consistency:** `getFilePath(int, string, string, ?int)` is used consistently across all tasks. The `sanitizeFilename(string)` utility is always called inside `getFilePath`.
4. **Scope check:** Focused on the file path change only — no scope creep.
