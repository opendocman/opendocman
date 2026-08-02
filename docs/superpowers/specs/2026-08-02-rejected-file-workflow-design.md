# Rejected File Workflow Improvement

**Date:** 2026-08-02
**Issues:**
- https://github.com/opendocman/opendocman/issues/2 (re-submit → check-out)
- https://github.com/opendocman/opendocman/issues/9 (incoming revision staging)

## Problem

**Issue #2:** When a reviewer rejects a file, the owner sees a "Re-Submit For Review" button. This is confusing — the file was just rejected, so why re-submit without changes? The natural workflow is to check-out, make changes, then check-in (which auto-places it back in the approval queue).

**Issue #9:** When a user checks in a new revision, it immediately overwrites the file in the data directory before any reviewer has approved it. If the file is corrupted or contains incorrect information, the damaged version is live until a reviewer rejects it — and even then, the corrupted content survives in the data directory and revision history.

## Scope

Two changes that work together:

1. **Incoming revision staging:** Checked-in revisions go to an `incoming/` directory instead of overwriting the data directory. The data directory always holds the last approved version. Revisions are only moved to data on approval, and rejected incoming files are never preserved in revision history.
2. **Rejects page:** Replace "Re-Submit For Review" with "Check-Out" links so the owner downloads the rejected incoming file, edits it, and checks in the fix.

## Design

### Data Flow

```
[Check-in] → upload to incoming/<id>/<file>
             publishable=0, status=0
             data/ unchanged (last approved version lives here)

[Reviewer views] → if publishable=0 and incoming exists → serve from incoming
                    otherwise → serve from data (normal path)

[Approve] → save data/<id>/<file> as revision
            move incoming/<id>/<file> → data/<id>/<file>
            publishable=1

[Reject]  → publishable=-1
            incoming file stays — owner can download it from rejects page

[Check-out on rejects page] → download from incoming/<id>/<file>
                               status=uid (checked out)

[Check-in after rejection] → user uploads fixed file
                             replace incoming/<id>/<file> with new upload
                             publishable=0 (pending review again)
```

### 1. New Config: `incomingDir`

Add a configuration setting `incomingDir` defaulting to `<dataDir>/incoming/`. This is the staging area for incoming revisions waiting for review.

### 2. `getFilePath` — New `incoming` Type (`functions.php`)

Add a new type case to `getFilePath()`:

```
case 'incoming':
    $base = $GLOBALS['CONFIG']['incomingDir'];
    return $base . $fileId . '/' . $realname;
```

### 3. Check-In Changes (`check-in.php`)

**Before (current behavior):**
- Save current data file as revision
- Write uploaded file to data directory
- Set publishable=0, status=0

**After (new behavior):**
- Save uploaded file to `incoming/<id>/<filename>` instead of `data/<id>/<filename>`
- Do NOT save the old data file as a revision yet (deferred to approval time)
- Update DB: publishable=0, status=0, realname=filename
- Re-extract text content for search indexing from the incoming file
- Log entry 'I' (check-in) as before
- Send notification emails as before

### 4. View/Download Changes (`view_file.php`, `check-out.php`)

**`view_file.php`:**
- If `publishable=0` (pending review) and an incoming file exists:
  - Reviewer or owner: serve from incoming directory
  - Everyone else: serve from data directory (last approved version)
- If `publishable=-1` (rejected) and incoming file exists and user is owner: serve from incoming
- Otherwise: existing behavior (serve from data or revision path)

**`check-out.php`:**
- Allow checkout when `publishable=-1` and `status=0` (rejected, not checked out)
- When `publishable=-1` and incoming file exists: serve from incoming (owner gets the rejected content)
- Otherwise: serve from data directory (normal path)
- Set `status=uid` (checked out) as before

### 5. Approve Changes (`toBePublished.php`)

**On approve (existing `Authorize` handler, line 268):**
- **Before** setting `publishable=1`:
  1. Save current data directory file as a revision (same logic currently in check-in.php)
  2. Move incoming file to data directory (`rename()`)
- Then set `publishable=1` (as before)
- Log 'Y' (Authorized, as before)

### 6. Reject Changes (`toBePublished.php`)

**On reject (existing `Reject` handler, line 178):**
- Set `publishable=-1` (as before)
- Log 'R' (File Rejected, as before)
- Do NOT delete the incoming file — the owner needs to download it to see what was rejected

### 7. Rejects Page Changes (`rejects.php`)

- Replace the two-button layout (resubmit + delete) with per-row "Check-Out" links
- The file listing is rendered via AJAX (`file_list_ajax.php`) + Tabulator table (`out.tpl`), so the "Check-Out" links can be added as a column in the AJAX response
- Remove the resubmit action handler (`$_POST['resubmit']` block, lines 71-90)
- Keep the multi-select "Delete" action interface (checkboxes), but the delete handler (`delete.php`) should also clean up the incoming file directory when deleting a rejected file
- The `out.tpl` template itself may need minimal changes to add the checkout column

### 8. File Listing AJAX (`file_list_ajax.php`)

- When `state=-1` (rejected), add a "Check-Out" link column in each row pointing to `check-out.php?id=<fileid>`
- Line 344 calculates filesize via `getFilePath(... 'data')` — for rejected files, the file may be in incoming directory instead. Try `incoming` type first, fall back to `data`.

### 9. Check-In After Rejection

When checking in a previously-rejected file:

- User already has the file checked out (`status=uid`)
- New upload goes to `incoming/<id>/<filename>`, replacing the old incoming file
- `publishable=0` (pending review again)
- The data directory still has the last approved version (untouched)

### First Uploads (not a check-in)

Initial file uploads from `add.php` still go directly to the data directory — the incoming directory only applies to checked-in revisions. For new files rejected at first review, the file is in data (it's the only copy), and checkout serves from data.

## Files Changed

| File | Change |
|------|--------|
| `application/controllers/rejects.php` | Remove resubmit handler; add per-row checkout links |
| `application/controllers/check-in.php` | Write to incoming directory instead of data; don't save revision |
| `application/controllers/view_file.php` | Serve from incoming if publishable=0/-1 and file exists there |
| `application/controllers/check-out.php` | Serve from incoming if publishable=-1 and file exists there |
| `application/controllers/toBePublished.php` | On approve: save revision + move incoming→data; on reject: keep incoming file |
| `application/controllers/file_list_ajax.php` | Add checkout link column for state=-1; fix filesize lookup for incoming files |
| `application/controllers/helpers/functions.php` | Add `incoming` type to `getFilePath()` |
| `application/installer/SchemaBuilder.php` | Add `incomingDir` config default |

## Edge Cases

| Scenario | Behavior |
|----------|----------|
| New file rejected at first review → checkout → check-in | File was in data (not incoming) initially; check-in creates first incoming file |
| Previously-approved file, new revision rejected → checkout → check-in | Old approved version still in data; rejected incoming available for checkout |
| Multiple rejection cycles | Each rejected incoming overwrites previous in incoming dir; no rejected content in revision history |
| Reviewer views pending file | Sees incoming version (the one they need to review) |
| Non-reviewer views pending file | Sees last approved version from data directory |
| User deletes file from rejects page | Should also delete the incoming file if one exists |
| User checks in a revision for an approved file (normal flow, no rejection) | Goes to incoming directory as well — same staging applies |
| Expired files (check-exp.php) | Unchanged — expiration just sets publishable=-1 |

## Not in Scope

- Existing revisions (from previous approval cycles) are never modified or deleted
- The access log (audit trail) is unchanged
- Initial file upload flow (`add.php`) is unchanged — new files still go to data directory
- The `out.tpl` template layout is preserved as much as possible