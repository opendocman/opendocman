# Original Filename Storage

**Date:** 2026-07-13
**Issue:** https://github.com/opendocman/opendocman/issues/320

## Problem

Uploaded files are stored on disk as `{database_id}.dat`, losing the original filename and extension. The original name exists only in the `realname` database column. This makes server-side file management opaque (no way to identify files without the DB), loses extension-based tooling, and is unintuitive for admins browsing the filesystem.

## Design

### New Path Scheme

All three storage directories use subfolder-by-ID with original filenames:

| Directory | Old Path | New Path |
|---|---|---|
| `dataDir` | `{id}.dat` | `{id}/{realname}` |
| `archiveDir` | `{id}.dat` | `{id}/{realname}` |
| `revisionDir` | `{id}/{id}_{rev}.dat` | `{id}/{basename}-rev{rev}.{ext}` |

Where:
- `realname` is the original uploaded filename (e.g., `report.docx`)
- `basename` is `realname` with the extension stripped (e.g., `report`)
- `ext` is the file extension including the dot (e.g., `.docx`)

### Centralized Path Helper

Introduce a single function `getFilePath(fileId, revision, type)` that all controllers call instead of constructing paths inline. This ensures consistent path logic across the entire codebase.

**Files that currently construct `{id}.dat` inline and will be updated:**
- `add.php` — primary upload
- `check-in.php` — revision creation
- `check-out.php` — download
- `view_file.php` — view/download
- `delete.php` — archive/restore/permanent delete
- `details.php` — file details
- `history.php` — revision history
- `in.php` — checked-out listing
- `helpers/functions.php` — file listing display

### Migration

A new installer upgrade step that runs one-time during the next version upgrade:

1. Iterate all rows in `odm_data`
2. For each file ID:
   - Create subdirectory `{dataDir}/{id}/` if not exists
   - Move `{dataDir}/{id}.dat` → `{dataDir}/{id}/{realname}`
   - If archived, move `{archiveDir}/{id}.dat` → `{archiveDir}/{id}/{realname}`
   - If revisions exist, move each `{revisionDir}/{id}/{id}_{rev}.dat` → `{revisionDir}/{id}/{basename}-rev{rev}.{ext}`
   - Clean up empty `{id}.dat` originals

Migration runs atomically per file (if one file fails, others still migrate). Partial migration is acceptable — remaining old-format paths are still handled by the path helper (it checks both locations).

### Filename Safety

Sanitize the `realname` value before using it as a filesystem path component:
- Strip `../` and `..\` path traversal sequences
- Strip null bytes
- Strip leading `/` and `\`

### Backwards Compatibility

The path helper checks both the new (subfolder) and old (flat `{id}.dat`) locations. This means:
- Immediately after migration, all files are at new paths
- If an admin restores a backup with old paths, files are still found
- The old-format check can be removed in a future major version

### Non-goals

- No configuration changes — uses existing `dataDir`, `archiveDir`, `revisionDir` settings
- No database schema changes
- No API changes — all downloads continue to use `Content-Disposition: attachment; filename="{realname}"` via the existing `realname` column