# Incoming Revision Staging & Rejected File Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stage checked-in revisions in an incoming directory until approved, and replace the "Re-Submit" button on the rejects page with a "Check-Out" link.

**Architecture:** Check-in writes new files to an `incomingDir` instead of `dataDir`. The data directory always holds the last approved version. Approval moves the incoming file to data and creates a revision of the old data file. Rejection leaves the incoming file available for download by the owner. The rejects page provides per-row checkout links instead of a batch resubmit button.

**Tech Stack:** PHP 7.4+, MySQL/MariaDB, Smarty templates, Tabulator

## Global Constraints

- `incomingDir` config defaults to `<dataDir>/incoming/`
- No schema changes needed — uses existing `publishable` and `status` columns
- All file paths go through `getFilePath()` — never construct paths directly
- Existing revisions are never modified or deleted
- Initial upload flow (`add.php`) is unchanged

---

### Task 1: Add `incomingDir` config and `getFilePath` incoming type

**Files:**
- Modify: `application/installer/SchemaBuilder.php`
- Modify: `application/controllers/helpers/functions.php`

**Interfaces:**
- Consumes: `$GLOBALS['CONFIG']['dataDir']` (existing)
- Produces: `$GLOBALS['CONFIG']['incomingDir']` (new config key), `getFilePath($id, $name, 'incoming')` returns path string, `getFilePath($id, $name, 'incoming_temp')` returns without creating subdirectories

- [ ] **Step 1: Add `incomingDir` config default to SchemaBuilder**

Add an INSERT row after the `snapshotDir` entry:

```php
"INSERT INTO `{$prefix}settings` VALUES(NULL, 'incomingDir', '{$incomingDir}', 'Location for incoming file revisions that have not yet been approved. Default is inside dataDir.', 'maxsize=255')",
```

The `$incomingDir` value should default to `rtrim($dataDir, '/') . '/incoming/'`. Note that `$dataDir` and `$snapshotDir` are already computed from `$options['datadir']` at the top of `getInsertStatements()`. Compute `$incomingDir` the same way. Also add the same `$incomingDir` computation to `application/installer/cli.php`.

- [ ] **Step 2: Add `incoming` type to `getFilePath()`**

In `application/controllers/helpers/functions.php`, add a new case before the `default` case:

```php
case 'incoming':
    $base = $GLOBALS['CONFIG']['incomingDir'];
    $path = $base . $fileId . '/' . $realname;
    return $path;
```

- [ ] **Step 3: Run SchemaBuilder to regenerate database.sql**

Run: `make dump-sql`

This regenerates `database.sql` from SchemaBuilder to include the new config row.

- [ ] **Step 4: Bump DB version**

Check `application/version.php` for `ODM_DB_VERSION`. If needed, bump it since SchemaBuilder changed (the config INSERT is part of the schema, so a version bump will trigger the installer on upgrade). Read the current value first.

- [ ] **Step 5: Commit**

```bash
git add application/installer/SchemaBuilder.php application/controllers/helpers/functions.php application/installer/cli.php database.sql application/version.php
git commit -m "feat: add incomingDir config and getFilePath incoming type"
```

---

### Task 2: Route check-in uploads to incoming directory

**Files:**
- Modify: `application/controllers/check-in.php`

**Interfaces:**
- Consumes: `getFilePath($id, $name, 'incoming')`
- Produces: `incoming/<id>/<filename>` created on disk

- [ ] **Step 1: Change check-in to write to `incoming` instead of `data`**

In `application/controllers/check-in.php`:

1. **Remove** the revision-saving block (lines 222-236). That logic moves to the approval handler in Task 3.
2. **Change** the file write destination from `data` to `incoming`. Replace:
   ```php
   $newFilePath = getFilePath($id, $filename, 'data');
   ```
   with:
   ```php
   $newFilePath = getFilePath($id, $filename, 'incoming');
   ```
3. **Update** the text extraction path to read from the incoming file instead of data:
   ```php
   $newFilePath = getFilePath($id, $filename, 'incoming');
   ```
   (line 279 sets `$file_mime` from `$newFilePath` and the text extractor reads from `$newFilePath` — both already use the variable, so the incoming path flows through correctly.)
4. **Ensure** the incoming subdirectory is created. The existing `mkdir` block (lines 272-275) creates the data directory. Replace/add:
   ```php
   $newFileDir = dirname($newFilePath);
   if (!is_dir($newFileDir)) {
       mkdir($newFileDir, 0775, true);
   }
   ```
   (This code already exists — just make sure it runs before `copy()` on the incoming path.)

5. **Keep** everything else: status/publishable DB update, log entry, email notifications.

- [ ] **Step 2: Manual verification**

Create a check-in scenario:
1. Add a file → approve it → check it out
2. Check in a new version
3. Verify the new file appears in `incoming/<id>/` not `data/<id>/`
4. Verify `publishable=0`, `status=0`, `realname` updated in DB
5. Verify the old approved file is still in `data/<id>/`
6. Verify text content was indexed (check `content_index` table)

- [ ] **Step 3: Commit**

```bash
git add application/controllers/check-in.php
git commit -m "feat: route check-in uploads to incoming directory instead of data"
```

---

### Task 3: Move incoming → data on approval, save revision

**Files:**
- Modify: `application/controllers/toBePublished.php`

**Interfaces:**
- Consumes: `getFilePath($id, $name, 'data')`, `getFilePath($id, $name, 'incoming')`, `getFilePath($id, $name, 'revision', $rev)`
- Produces: data file saved as revision, incoming file moved to data

- [ ] **Step 1: Add revision-save and file-move logic to the Authorize handler**

In `application/controllers/toBePublished.php`, in the `Authorize` handler (line 210), **before** the `$file_obj->Publishable(1)` call at line 268, add:

```php
// Save current data file as revision and move incoming file to data
$currentRealname = $file_obj->getName();
$incomingPath = getFilePath($fileid, $currentRealname, 'incoming');
if (file_exists($incomingPath)) {
    // Count existing revisions
    $query = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $fileid]);
    $revisionCount = (int) $stmt->fetchColumn();

    // Save current data file as revision
    $dataPath = getFilePath($fileid, $currentRealname, 'data');
    if (file_exists($dataPath)) {
        $revisionDir = dirname(getFilePath($fileid, $currentRealname, 'revision', $revisionCount));
        if (!is_dir($revisionDir)) {
            mkdir($revisionDir, 0775, true);
        }
        $revisionPath = getFilePath($fileid, $currentRealname, 'revision', $revisionCount);
        copy($dataPath, $revisionPath);

        // Update log: mark old 'current' with revision number, insert new 'current'
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = :rev WHERE id = :id AND revision = 'current'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':rev' => $revisionCount, ':id' => $fileid]);

        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'current')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id' => $fileid,
            ':username' => /* need username from session */,
            ':note' => 'Approved revision ' . $revisionCount,
        ]);
    }

    // Move incoming file to data directory
    $dataDir = dirname($dataPath);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }
    rename($incomingPath, $dataPath);

    // Re-index text content from the new data file
    $file_mime = File::mime($dataPath, $currentRealname);
    if (TextExtractorFactory::isExtractable($file_mime)) {
        $extractor = TextExtractorFactory::create($file_mime);
        if ($extractor !== null) {
            $contentText = $extractor->extract($dataPath);
            $indexQuery = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}content_index (file_id, content_text, indexed_at) VALUES (:file_id, :content_text, NOW()) ON DUPLICATE KEY UPDATE content_text = :content_text2, indexed_at = NOW()";
            $indexStmt = $pdo->prepare($indexQuery);
            $indexStmt->execute([
                ':file_id' => $fileid,
                ':content_text' => $contentText,
                ':content_text2' => $contentText,
            ]);
        }
    }
}
```

**Username:** The log entry's `modified_by` should be the reviewer who approved. Add a username query before the log update:

```php
// Get reviewer username for the log entry
$usernameQuery = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid";
$usernameStmt = $pdo->prepare($usernameQuery);
$usernameStmt->execute([':uid' => $_SESSION['uid']]);
$username = $usernameStmt->fetchColumn();
```

Use `$username` in the log INSERT above.

- [ ] **Step 2: Manual verification**

1. Check in a new version of an approved file (goes to incoming)
2. As reviewer, approve the file
3. Verify: old data file was saved as revision in `revisionDir/<id>/`
4. Verify: incoming file moved to `data/<id>/`
5. Verify: `publishable=1`, log entry 'Y' created
6. Verify: view_file shows the approved version

- [ ] **Step 3: Commit**

```bash
git add application/controllers/toBePublished.php
git commit -m "feat: move incoming file to data and save revision on approval"
```

---

### Task 4: Keep incoming file on rejection

**Files:**
- Modify: `application/controllers/toBePublished.php`

- [ ] **Step 1: Remove incoming file deletion on reject**

In the `Reject` handler (line 117), the only change is to **not delete** the incoming file. Currently the reject handler calls `$file_obj->Publishable(-1)` at line 178 and sends emails. No change needed — the incoming file stays where it is. Just verify there's no code somewhere that deletes it.

- [ ] **Step 2: Manual verification**

1. Check in a new version of an approved file (goes to incoming)
2. As reviewer, reject the file
3. Verify: `publishable=-1` in DB
4. Verify: incoming file still exists in `incoming/<id>/`
5. Verify: data directory still has the old approved version

- [ ] **Step 3: Commit**

```bash
git add application/controllers/toBePublished.php
git commit -m "feat: keep incoming file available on rejection"
```

---

### Task 5: Serve files from incoming during review and after rejection

**Files:**
- Modify: `application/controllers/view_file.php`

**Interfaces:**
- Consumes: `$file_obj->isPublishable()` returns -1, 0, or 1; `getFilePath($id, $name, 'incoming')` returns path
- Produces: correct file served based on publishable state and user role

- [ ] **Step 1: Add incoming path resolution to view_file**

In `application/controllers/view_file.php`, in the `view` handler (line 70) and the `Download` handler (line 103), the file path is currently resolved at lines 80-86:

```php
if (isset($revision_id)) {
    $filename = getFilePath($request_id, $realname, 'revision', $revision_id);
} elseif ($file_obj->isArchived()) {
    $filename = getFilePath($request_id, $realname, 'archive');
} else {
    $filename = getFilePath($request_id, $realname, 'data');
}
```

Add a check **before** the `data` fallback but **after** the `revision` and `archive` checks:

```php
} elseif ($file_obj->isPublishable() === 0 || $file_obj->isPublishable() === -1) {
    $incomingPath = getFilePath($request_id, $realname, 'incoming');
    if (file_exists($incomingPath)) {
        $filename = $incomingPath;
    } else {
        $filename = getFilePath($request_id, $realname, 'data');
    }
```

This means:
- If `publishable=0` (pending review) and incoming file exists: serve from incoming (any user with READ_RIGHT sees the pending version)
- If `publishable=-1` (rejected) and incoming file exists: serve from incoming (the rejected version)
- Otherwise: fall through to data (the last approved version)

- [ ] **Step 2: Manual verification**

1. Check in a new version → verify reviewers see the incoming file
2. Reject the file → verify owner can view/download the rejected version from incoming
3. Approve a file → verify everyone sees the data version
4. Check a newly uploaded file (first upload, no incoming): verify it still serves from data

- [ ] **Step 3: Commit**

```bash
git add application/controllers/view_file.php
git commit -m "feat: serve files from incoming directory during review and after rejection"
```

---

### Task 6: Check-out serves from incoming for rejected files

**Files:**
- Modify: `application/controllers/check-out.php`

- [ ] **Step 1: Add incoming path resolution for rejected files**

In `application/controllers/check-out.php`, line 91 resolves the file path:

```php
$filename = getFilePath($id, $real_name, 'data');
```

Replace this with logic that checks for an incoming file when the file is rejected:

```php
$file_data_obj = new FileData($id, $pdo); // already loaded at line 46
if ($file_data_obj->isPublishable() === -1) {
    $incomingPath = getFilePath($id, $real_name, 'incoming');
    if (file_exists($incomingPath)) {
        $filename = $incomingPath;
    } else {
        $filename = getFilePath($id, $real_name, 'data');
    }
} else {
    $filename = getFilePath($id, $real_name, 'data');
}
```

Also, the guard at line 48 currently blocks checkout if `status > 0`. A rejected file has `status=0`, so this passes. No change needed to the guard — but verify that rejected files with `status=0` are allowed through. The guard checks `$file_data_obj->getStatus() > 0` — this is correct (0 means not checked out).

- [ ] **Step 2: Manual verification**

1. Reject a file that has an incoming version
2. Visit the details page for the rejected file → verify checkout link appears
3. Click checkout → verify the incoming file is downloaded (not the data version)
4. Verify `status` is set to the user's `uid` in the DB

- [ ] **Step 3: Commit**

```bash
git add application/controllers/check-out.php
git commit -m "feat: serve incoming file on checkout of rejected files"
```

---

### Task 7: Update rejects page — replace resubmit with per-row checkout links

**Files:**
- Modify: `application/controllers/rejects.php`

- [ ] **Step 1: Remove the resubmit button and handler**

In `application/controllers/rejects.php`:

1. In the display section (line 35-70), remove the "Re-Submit For Review" button (line 61):
   ```php
   // Remove this line:
   <button class="btn btn-success" type="submit" name="submit" value="resubmit"><?php echo msg('button_resubmit_for_review'); ?></button>
   ```

2. Remove the resubmit action handler entirely (lines 71-90):
   ```php
   // Remove this entire block:
   } elseif (isset($_POST['submit']) && $_POST['submit'] == 'resubmit') {
       ...
   ```

3. Keep the "Delete" button and its handler (line 91+).

After these changes, the rejects page still lists files via `out.tpl` with `state=-1`, shows the "Delete" button, and checks are handled by the delete handler. The checkout links will be rendered in the table rows via `file_list_ajax.php` (Task 8).

- [ ] **Step 2: Remove unused CSRF token field**

Since the only remaining action on the rejects page is "Delete" (which redirects to `delete.php` with its own CSRF check), the form element itself can be simplified. The delete handler reads IDs from `$_GET` params, not from a form submit. Consider whether the form wrapper is still needed — if only the delete action remains and it redirects to a GET URL, the form + checkboxes may be unnecessary. However, to keep the existing "Delete" workflow unchanged, leave the form and checkbox structure intact; just remove the resubmit button.

- [ ] **Step 3: Commit**

```bash
git add application/controllers/rejects.php
git commit -m "feat: remove resubmit button from rejects page"
```

---

### Task 8: Add checkout link column to file list and fix filesize lookup

**Files:**
- Modify: `application/controllers/file_list_ajax.php`

- [ ] **Step 1: Add checkout link to AJAX response for state=-1**

In `application/controllers/file_list_ajax.php`, in the data array builder (lines 370-384), add a new field when `$state === -1`:

```php
'checkout_link' => ($state === -1 && $lock === false && $userAccessLevel >= 3)
    ? 'check-out?id=' . $fileid . '&state=0&access_right=modify'
    : null,
```

The `$lock` variable indicates whether the file is checked out (status != 0). A rejected file might be checked out (if already checked out) — in that case, `$lock` would be `true` and we should not show the checkout link. The `$userAccessLevel >= 3` ensures only users with WRITE_RIGHT can checkout.

- [ ] **Step 2: Fix filesize lookup for incoming files**

Line 344 currently does:
```php
$filesize = display_filesize(getFilePath($fileid, $realname, 'data'));
```

For rejected files, the file might be in the incoming directory. When `$state === -1`, fall back to incoming path:
```php
$filePath = getFilePath($fileid, $realname, 'data');
if (!file_exists($filePath) && $state === -1) {
    $filePath = getFilePath($fileid, $realname, 'incoming');
}
$filesize = display_filesize($filePath);
```

- [ ] **Step 3: Update the Tabulator table to show the checkout column**

In `public/js/bootstrap5/tabulator-config.js`, add a new column (lines 66-86) that's visible when `state === -1`. Insert after the "Size" column:

```javascript
{ title: '', field: 'checkout_link', width: 100, headerSort: false,
  visible: function() { return parseInt(document.getElementById('file-table')?.dataset.state || 1) === -1; },
  formatter: function(cell) {
    var link = cell.getValue();
    return link ? '<a href="' + link + '" class="btn btn-sm btn-primary">Check-Out</a>' : '';
  }
},
```

- [ ] **Step 4: Remove the rejects form handler from tabulator-config.js**

Since the rejects page no longer has a resubmit button, the form + checkbox pattern is only used for delete. Lines 124-146 handle the `author_note_form` submit. If the form structure in `rejects.php` is simplified (no form wrapper), remove these JS lines. If the form is kept for the delete action, leave them.

- [ ] **Step 4: Manual verification**

1. Reject a file → visit the rejects page
2. Verify each row shows a "Check-Out" link
3. Verify clicking the link initiates checkout of the incoming file
4. Verify filesize shows correctly for rejected files

- [ ] **Step 5: Commit**

```bash
git add application/controllers/file_list_ajax.php [path/to/tabulator/js]
git commit -m "feat: add checkout link column and fix filesize for rejected files"
```

---

### Task 9: Clean up incoming directory on file delete

**Files:**
- Modify: `application/controllers/delete.php`

- [ ] **Step 1: Remove incoming file on temp delete**

In `application/controllers/delete.php`, in the `tmpdel` handler (line 42), after the file is archived and `AccessLog` entry added (line 81), add:

```php
// Clean up incoming file if one exists
$incomingPath = getFilePath($id, $realname, 'incoming');
if (file_exists($incomingPath)) {
    unlink($incomingPath);
    // Remove empty incoming subdirectory
    $incomingDir = dirname($incomingPath);
    if (is_dir($incomingDir) && count(scandir($incomingDir)) <= 2) {
        rmdir($incomingDir);
    }
}
```

The `$realname` and `$id` variables are already available in scope (lines 63, 70).

- [ ] **Step 2: Manual verification**

1. Reject a file (creates incoming file)
2. Delete the file from the rejects page
3. Verify the incoming file is removed
4. Verify the data file was archived (existing behavior unchanged)

- [ ] **Step 3: Commit**

```bash
git add application/controllers/delete.php
git commit -m "feat: clean up incoming directory on file delete"
```

---

### Task 10: Run tests and final verification

**Files:**
- Run: `tests/smoke-uat.spec.ts` (Playwright E2E)
- Run: PHPUnit tests
- Run: `make test`

- [ ] **Step 1: Run PHPUnit tests**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist
```
Expected: All existing tests pass. If any break due to our changes, fix them.

- [ ] **Step 2: Run E2E smoke test**

```bash
npm run test:e2e
```
Expected: Smoke test passes (login, change setting, verify persistence, cleanup).

- [ ] **Step 3: Full `make test`**

```bash
make test
```
Expected: All tests pass.

- [ ] **Step 4: Commit if any test fixes were needed**

```bash
git add -A
git commit -m "chore: fix tests for incoming directory changes"
```