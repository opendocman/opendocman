# Public File Sharing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a public-facing page that displays files marked as public and approved, without requiring authentication.

**Architecture:** New controller `public.php` + new template `public.tpl` + checkbox on add/edit forms gated by a `public_sharing` feature flag (default: False). Uses the existing `publishable` column for approval gating and a new `is_public` column on the `data` table.

**Tech Stack:** PHP, Smarty templates, Bootstrap 5, MySQL

## Global Constraints

- New language strings must be added to all 17 language files, not just english.php
- `ODM_DB_VERSION` must be bumped after schema changes
- `make dump-sql` must be run after SchemaBuilder changes
- Feature flag `public_sharing` defaults to `'False'`
- The `public` routing path is auto-resolved by `public/index.php` — no route config needed
- Access log action `'U'` for public downloads

---
### Task 1: Schema & Migration

**Files:**
- Modify: `application/installer/SchemaBuilder.php`
- Modify: `application/version.php`
- Create: `application/installer/migrations/Version001701.php`

**Interfaces:**
- Consumes: existing MigrationInterface, existing SchemaBuilder pattern
- Produces: `is_public` column on `data` table, `public_sharing` setting, `'U'` access_log action enum value

- [ ] **Step 1: Add `is_public` column to SchemaBuilder**

In `SchemaBuilder.php`, add `is_public` to the `data` table CREATE TABLE statement after `publishable`:

```php
publishable tinyint(4) default NULL,
is_public tinyint(1) DEFAULT 0,
```

- [ ] **Step 2: Add `public_sharing` setting to SchemaBuilder**

In `getDefaultDataStatements()`, add after the `file_expired_action` setting:

```php
"INSERT INTO `{$prefix}settings` VALUES(NULL, 'public_sharing', 'False',
 '(True/False) Enable public file sharing page. When enabled, files marked as public and approved will be visible without authentication.', 'bool')",
```

- [ ] **Step 3: Create migration Version001701**

```php
<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001701 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.1';
    }

    public function getDescription(): string
    {
        return 'Add is_public column, public_sharing setting, and public download access_log action';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` ADD COLUMN `is_public` tinyint(1) DEFAULT 0 AFTER `publishable`");
        $pdo->exec("INSERT INTO `{$prefix}settings` (name, value, description, validation) VALUES ('public_sharing', 'False', '(True/False) Enable public file sharing page. When enabled, files marked as public and approved will be visible without authentication.', 'bool')");
        $pdo->exec("ALTER TABLE `{$prefix}access_log` MODIFY COLUMN `action` enum('A','B','C','V','D','M','X','I','O','Y','R','U') NOT NULL");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` DROP COLUMN `is_public`");
        $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = 'public_sharing'");
        $pdo->exec("ALTER TABLE `{$prefix}access_log` MODIFY COLUMN `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL");
    }
}
```

- [ ] **Step 4: Bump ODM_DB_VERSION and dump SQL**

In `application/version.php`:

```php
const ODM_DB_VERSION = '1.7.1';
```

Then run: `make dump-sql`

- [ ] **Step 5: Commit**

```bash
git add application/installer/SchemaBuilder.php application/version.php application/installer/migrations/Version001701.php database.sql
git commit -m "feat: add is_public column, public_sharing setting, and migration for public file sharing"
```

---
### Task 2: Language Strings

**Files:**
- Modify: `application/includes/language/english.php`
- Modify: `application/includes/language/arabic.php`
- Modify: `application/includes/language/bangla.php`
- Modify: `application/includes/language/chinese.php`
- Modify: `application/includes/language/croatian.php`
- Modify: `application/includes/language/czech.php`
- Modify: `application/includes/language/danish.php`
- Modify: `application/includes/language/dutch.php`
- Modify: `application/includes/language/french.php`
- Modify: `application/includes/language/german.php`
- Modify: `application/includes/language/italian.php`
- Modify: `application/includes/language/portuguese.php`
- Modify: `application/includes/language/romanian.php`
- Modify: `application/includes/language/spanish.php`
- Modify: `application/includes/language/swedish.php`
- Modify: `application/includes/language/tamil.php`
- Modify: `application/includes/language/turkish.php`

**Interfaces:**
- Consumes: none
- Produces: `$lang['public_link']`, `$lang['public_page_title']`, `$lang['public_page_h1']`, `$lang['public']`, `$lang['label_public']`, `$lang['message_public_view']`, `$lang['public_download']`, `$lang['message_public_disabled']`

- [ ] **Step 1: Add public language keys to english.php**

In `application/includes/language/english.php`, add after the `$lang['anonymous_link']` block:

```php
// Public file sharing
$lang['public_link'] = 'Click here to view public files';
$lang['public_page_title'] = 'Public Files: List All';
$lang['public_page_h1'] = 'Public Files';
$lang['public'] = 'Public';
$lang['label_public'] = 'Public file';
$lang['message_public_view'] = 'You are viewing public files';
$lang['public_download'] = 'Download';
$lang['message_public_disabled'] = 'Public file sharing is disabled';
```

- [ ] **Step 2: Add public language keys to all 16 other language files**

For each file, add the same keys in the same position (after the `$lang['anonymous_link']` block). Use each language's existing `anonymous_*` translations as guidance for the new `public_*` translations. The english values from Step 1 are the fallback.

- [ ] **Step 3: Commit**

```bash
git add application/includes/language/*.php
git commit -m "feat: add public_* language keys to all 17 language files"
```

---
### Task 3: FileData Model — Add `is_public`

**Files:**
- Modify: `application/models/FileData.class.php`
- Modify: `application/models/AccessLog.class.php`

**Interfaces:**
- Produces: `FileData::$is_public`, `FileData::getIsPublic()`, `FileData::setIsPublic($val)`, updated `loadData()` and `updateData()`
- Produces: Updated `AccessLog::addLogEntry()` with optional `$userId` parameter

- [ ] **Step 1: Add `is_public` property**

After line 59 (`public $isLocked;`), add:

```php
public $is_public;
```

- [ ] **Step 2: Update `loadData()` to include `is_public`**

Add `is_public` to the SELECT query on line 110-123:

```php
$query = "
  SELECT
    category,
    owner,
    created,
    description,
    comment,
    status,
    department,
    default_rights,
    is_public
  FROM
    ...
";
```

Add after line 138 (`$this->default_rights = $row['default_rights'];`):

```php
$this->is_public = $row['is_public'] ?? 0;
```

- [ ] **Step 3: Update `updateData()` to include `is_public`**

Add `is_public = :is_public` to the SET clause and bind the value:

```php
$query = "
  UPDATE
    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
  SET
    category = :category,
    owner = :owner,
    description = :description,
    comment = :comment,
    status = :status,
    department = :department,
    default_rights = :default_rights,
    is_public = :is_public
   WHERE
    id = :id
";

$stmt->execute(array(
    ':category' => $this->category,
    ':owner' => $this->owner,
    ':description' => $this->description,
    ':comment' => $this->comment,
    ':status' => $this->status,
    ':department' => $this->department,
    ':default_rights' => $this->default_rights,
    ':is_public' => $this->is_public,
    ':id' => $this->id
));
```

- [ ] **Step 4: Add getter and setter methods**

Add after the existing `getFileSize()` method:

```php
public function getIsPublic()
{
    return $this->is_public;
}

public function setIsPublic($val)
{
    $this->is_public = $val;
}
```

- [ ] **Step 5: Update AccessLog::addLogEntry to accept optional userId**

Replace the existing `addLogEntry()` method:

```php
public static function addLogEntry($fileId, $type, PDO $pdo, $userId = null)
{
    if ($fileId == 0) {
        global $id;
        $fileId = $id;
    }

    $uid = ($userId !== null) ? $userId : ($_SESSION['uid'] ?? 0);

    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}access_log (file_id,user_id,timestamp,action) VALUES ( :file_id, :uid, NOW(), :type)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(
        array(
            ':file_id' => $fileId,
            ':uid' => $uid,
            ':type' => $type
        )
    );
}
```

- [ ] **Step 6: Commit**

```bash
git add application/models/FileData.class.php application/models/AccessLog.class.php
git commit -m "feat: add is_public to FileData model and update AccessLog for optional userId"
```

---
### Task 4: Public Controller

**Files:**
- Create: `application/controllers/public.php`

**Interfaces:**
- Consumes: `getFilePath()`, `AccessLog::addLogEntry()`, `FileData`, `msg()`, `display_smarty_template()`, `$GLOBALS['CONFIG']['public_sharing']`, `$GLOBALS['smarty']`
- Produces: Handles routes `/public` and `/public?submit=download&id=N`

- [ ] **Step 1: Write the controller**

```php
<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

// Check if public sharing is enabled
if (!isset($GLOBALS['CONFIG']['public_sharing']) || $GLOBALS['CONFIG']['public_sharing'] !== 'True') {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . msg('public_page_title') . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/bootstrap5/style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="alert alert-info">' . msg('message_public_disabled') . '</div>
        <a href="index" class="btn btn-primary">' . msg('login') . '</a>
    </div>
</body>
</html>';
    exit;
}

if (isset($_GET['submit']) && $_GET['submit'] === 'download' && isset($_GET['id'])) {
    // Download mode
    $fileId = (int) $_GET['id'];
    $filedata = new FileData($fileId, $pdo);

    if (!$filedata->exists() || !$filedata->getIsPublic() || $filedata->isPublishable() != 1) {
        header('HTTP/1.0 404 Not Found');
        echo 'File not found';
        exit;
    }

    $realname = $filedata->getName();
    $filePath = getFilePath($fileId, $realname, 'data');

    if (!file_exists($filePath)) {
        header('HTTP/1.0 404 Not Found');
        echo 'File not found';
        exit;
    }

The `AccessLog::addLogEntry()` signature needs to accept an optional `$userId` parameter. For public downloads, pass `0` as the user_id:

In `AccessLog.class.php`, update `addLogEntry()`:

```php
public static function addLogEntry($fileId, $type, PDO $pdo, $userId = null)
{
    if ($fileId == 0) {
        global $id;
        $fileId = $id;
    }

    $uid = ($userId !== null) ? $userId : ($_SESSION['uid'] ?? 0);

    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}access_log (file_id,user_id,timestamp,action) VALUES ( :file_id, :uid, NOW(), :type)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(
        array(
            ':file_id' => $fileId,
            ':uid' => $uid,
            ':type' => $type
        )
    );
}
```

This is backward-compatible — existing callers use 3 args and `$_SESSION['uid']` as before.

### Controller

```php
    AccessLog::addLogEntry($fileId, 'U', $pdo, 0);
```

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $realname . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// List mode
$query = "SELECT
    d.id,
    d.realname,
    d.description,
    d.category,
    c.name as category_name,
    d.created
FROM {$GLOBALS['CONFIG']['db_prefix']}data d
LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}category c ON d.category = c.id
WHERE d.is_public = 1 AND d.publishable = 1
ORDER BY d.created DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

Pass `public_sharing` and `site_title` to the template. Add below the query:

```php
$GLOBALS['smarty']->assign('public_files', $files);
$GLOBALS['smarty']->assign('public_sharing', $GLOBALS['CONFIG']['public_sharing'] ?? 'False');
$GLOBALS['smarty']->assign('site_title', $GLOBALS['CONFIG']['title']);
display_smarty_template('public.tpl');
```
```

- [ ] **Step 2: Commit**

```bash
git add application/controllers/public.php
git commit -m "feat: create public controller for file listing and download"
```

---
### Task 5: Public Template

**Files:**
- Create: `application/views/bootstrap5/public.tpl`

**Interfaces:**
- Consumes: `{$public_files}`, `{$g_lang_*}`, `{$g_base_url}`

- [ ] **Step 1: Write the public template**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$g_lang_public_page_title} - {$site_title|escape}</title>
    {include file="head_include.tpl"}
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="public">{$site_title|escape} - {$g_lang_public_page_h1}</a>
        </div>
    </nav>
    <main class="container-fluid py-3">
        <div class="mt-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{$g_lang_public_page_h1}</h3>
                </div>
                <div class="card-body">
                    {if $public_files|@count > 0}
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{$g_lang_label_filename}</th>
                                    <th>{$g_lang_label_description}</th>
                                    <th>{$g_lang_category}</th>
                                    <th>{$g_lang_date}</th>
                                    <th>{$g_lang_public_download}</th>
                                </tr>
                            </thead>
                            <tbody>
                            {foreach from=$public_files item=file}
                                <tr>
                                    <td>{$file.realname|escape}</td>
                                    <td>{$file.description|escape}</td>
                                    <td>{$file.category_name|escape}</td>
                                    <td>{$file.created|date_format:"%Y-%m-%d"}</td>
                                    <td>
                                        <a href="public?submit=download&amp;id={$file.id}" class="btn btn-sm btn-primary">
                                            {$g_lang_public_download}
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                    {else}
                    <p class="text-muted">No public files available.</p>
                    {/if}
                </div>
            </div>
        </div>
    </main>
    {include file="footer.tpl"}
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add application/views/bootstrap5/public.tpl
git commit -m "feat: create public.tpl template for public file listing"
```

---
### Task 6: Modify Add/Edit Forms

**Files:**
- Modify: `application/controllers/add.php`
- Modify: `application/controllers/edit.php`
- Modify: `application/views/common/add.tpl`
- Modify: `application/views/common/edit.tpl`

**Interfaces:**
- Consumes: `$GLOBALS['CONFIG']['public_sharing']`, `FileData::setIsPublic()`
- Produces: `is_public` checkbox on add/edit forms, `is_public` stored/loaded from DB

- [ ] **Step 1: Add `is_public` to the add controller INSERT**

In `add.php`, after the `$publishable` line (around line 218) and before the INSERT query, add:

```php
$is_public = isset($_POST['is_public']) ? 1 : 0;
```

In the INSERT query, add `is_public` to the column list (after `publishable`):

```php
publishable,
is_public
```

And in VALUES:

```php
$publishable,
$is_public
```

Pass `public_sharing` to the template. Add below line 138 (the `$GLOBALS['smarty']->assign('db_prefix', ...)` line):

```php
$GLOBALS['smarty']->assign('public_sharing', $GLOBALS['CONFIG']['public_sharing'] ?? 'False');
```

- [ ] **Step 2: Add `is_public` checkbox to add.tpl**

Add after the `{include file='../../views/common/_filePermissions.tpl'}` section in `add.tpl`:

```html
{if $public_sharing eq 'True'}
<div class="mb-3 form-check">
    <input type="checkbox" name="is_public" id="is_public" value="1" class="form-check-input">
    <label class="form-check-label" for="is_public">{$g_lang_label_public}</label>
</div>
{/if}
```

- [ ] **Step 3: Update edit controller to load/save `is_public`**

In `edit.php`, in the display section (around line 145), after the existing smarty assigns, add:

```php
$GLOBALS['smarty']->assign('is_public', $filedata->getIsPublic());
$GLOBALS['smarty']->assign('public_sharing', $GLOBALS['CONFIG']['public_sharing'] ?? 'False');
```

In the POST handler (around line 230), before `$filedata->updateData()`, add:

```php
$filedata->setIsPublic(isset($_POST['is_public']) ? 1 : 0);
```

- [ ] **Step 4: Add `is_public` checkbox to edit.tpl**

Read the existing `edit.tpl` to find the right insertion point and add:

```html
{if $public_sharing eq 'True'}
<div class="mb-3 form-check">
    <input type="checkbox" name="is_public" id="is_public" value="1" class="form-check-input"{if $is_public eq 1} checked{/if}>
    <label class="form-check-label" for="is_public">{$g_lang_label_public}</label>
</div>
{/if}
```

- [ ] **Step 5: Commit**

```bash
git add application/controllers/add.php application/controllers/edit.php application/views/common/add.tpl application/views/common/edit.tpl
git commit -m "feat: add public file checkbox to add/edit forms, gated by public_sharing setting"
```

---
### Task 7: Add Public Link to Login Page

**Files:**
- Modify: `application/views/bootstrap5/login.tpl`

- [ ] **Step 1: Add public link to the login template**

Add after the signup/forgot-password block (after line 45):

```html
{if $g_public_sharing eq 'True'}
<div class="text-center mt-2">
    <a href="public" class="text-decoration-none small">{$g_lang_public_link}</a>
</div>
{/if}
```

Note: The `$g_public_sharing` variable is already auto-assigned by `odm-init.php` which iterates `$GLOBALS['CONFIG']` and assigns each as `g_{key}` (line 64-66 of odm-init.php). `$g_lang_public_link` is also auto-assigned for all language keys (line 71-73). No controller changes needed for login.tpl.

- [ ] **Step 2: Commit**

```bash
git add application/views/bootstrap5/login.tpl
git commit -m "feat: add public file link to login page, gated by public_sharing setting"
```

---
### Task 8: Tests

**Files:**
- Modify: `tests/smoke-uat.spec.ts` (or create new test file)

- [ ] **Step 1: Add E2E test for public page**

In `tests/public-sharing.spec.ts`, add tests:

```typescript
import { test, expect } from '@playwright/test';
import { retryGoto } from './helpers';

test.describe('Public File Sharing', () => {
  test('public page shows disabled message when feature is off', async ({ page }) => {
    await retryGoto(page, '/public');
    await expect(page.locator('text=Public file sharing is disabled')).toBeVisible();
  });
});
```

- [ ] **Step 2: Run tests**

```bash
npm run test:e2e
```

- [ ] **Step 3: Commit**

```bash
git add tests/
git commit -m "test: add E2E tests for public file sharing page"
```

---
### Task 9: Final Verification

- [ ] **Step 1: Run all tests**

```bash
make test
npm run test:e2e
```

- [ ] **Step 2: Verify the full feature flow manually**

1. Access `/public` — should see disabled message
2. Login as admin, go to Settings, enable `public_sharing`
3. Logout, visit `/public` — should see "No public files available"
4. Login, add a file with the "Public file" checkbox checked
5. As admin, approve the file (set publishable to 1)
6. Visit `/public` as unauthenticated — file should appear
7. Click Download — file should download successfully
8. Check access_log for the `'U'` entry

- [ ] **Step 3: Fix any test failures**

```bash
make test
npm run test:e2e
```

- [ ] **Step 4: Final commit if fixes needed**

```bash
git add -A
git commit -m "fix: address test and verification findings for public file sharing"
```
