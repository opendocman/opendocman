# Snapshot System — Design Spec

## Overview

Add a snapshot/restore subsystem to OpenDocMan that creates and restores
point-in-time archives of the database + document files.  This replaces the
broken `demo.php` cron script and powers automated demo-site refresh for
`demo.opendocman.com` (latest release) and `beta.opendocman.com` (master).

Existing demo-mode guards (block destructive actions, suppress email, show
credentials on login) and AdSense ads are **untouched** — the snapshot system
is an orthogonal feature.

---

## Architecture

```
SnapshotManager (application/models/SnapshotManager.class.php)
├── create(name, description?) → Snapshot
├── restore(name) → void
├── list() → Snapshot[]
├── delete(name) → void
└── getSnapshotDir() → string
```

Each snapshot is a directory named after the snapshot:

```
<snapshotDir>/<name>/
├── db.sql.gz          # gzipped mysqldump of all odm_ tables
├── files.tar.gz       # tarball of document_repository/
└── metadata.json      # { name, created_at, app_version, description }
```

---

## Snapshot Storage

New `snapshotDir` setting in `odm_settings`:

```sql
INSERT INTO `odm_settings` VALUES(NULL, 'snapshotDir', '/var/www/snapshots/',
    'Location to store database and file snapshots. Should be outside web root.',
    'maxsize=255');
```

- Default: `/var/www/snapshots/` (sibling of the default `dataDir`).
- Added to `SchemaBuilder.php` alongside `dataDir`.
- Added to the installer CLI (`--snapshotdir=` flag).
- Validated in `settings.php` controller (exists + writable, same as `dataDir`).

---

## SnapshotManager Methods

### `create(string $name, ?string $description): Snapshot`

1. Create directory `<snapshotDir>/<name>/`.
2. **DB export**: Prefer `mysqldump` via `shell_exec` (fast). Fall back to
   pure-PDO: `SHOW CREATE TABLE` for each table, then `SELECT *` + gzip.
3. **Files export**: Use PHP's built-in `PharData` to create `files.tar.gz`
   from the contents of `dataDir`.
4. Write `metadata.json`.
5. Update `latest` symlink to point to this snapshot.
6. Return `Snapshot` value object.

### `restore(string $name): void`

1. Validate snapshot exists at `<snapshotDir>/<name>/`.
2. **Drop all `odm_` tables** (`SHOW TABLES LIKE 'odm_%'` → `DROP TABLE`).
3. **Import DB**: Un-gzip `db.sql.gz` and execute via PDO.
4. **Wipe `dataDir`**: `rm -rf` all files/dirs inside `dataDir` (keep the
   directory itself).
5. **Extract files**: Un-tar `files.tar.gz` into `dataDir`.
6. Run the migration system to ensure the schema is at the current version.

### `list(): Snapshot[]`

- Scan `<snapshotDir>/` for subdirectories containing `metadata.json`.
- Return `Snapshot` value objects sorted by `created_at` descending.

### `delete(string $name): void`

- Remove the snapshot directory and its contents.
- If the deleted snapshot was `latest`, update the symlink to the next most
  recent snapshot (or remove it).

---

## CLI Commands

Added to `application/installer/cli.php`:

| Command | Description |
|---------|-------------|
| `snapshot:create --name=<name> [--description=...]` | Create a snapshot |
| `snapshot:restore [--name=<name>]` | Restore a snapshot (defaults to `latest` symlink) |
| `snapshot:list` | List all snapshots |
| `snapshot:delete --name=<name>` | Delete a snapshot |
| `demo:refresh` | Restore `demo-baseline` snapshot + set demo mode on |

No `demo.php` file — the old script is deprecated and removed.

---

## `demo:refresh` Command

Targeted at a snapshot named `demo-baseline` by convention — not `latest`.
This avoids ambiguity when multiple snapshots exist.

Flow:

```
demo:refresh:
  1. Verify snapshot 'demo-baseline' exists → error if not
  2. Run SnapshotManager::restore('demo-baseline')
  3. Set demo=True in odm_settings
  4. Output success
```

---

## Full Cron Pipeline

### Beta site (master branch)

```
0 * * * * cd /home/opendocm/beta.opendocman.com && git pull origin master 2>&1 && php odm.php migrate 2>&1 && php odm.php demo:refresh 2>&1
```

### Demo site (latest release)

```
0 * * * * cd /home/opendocm/demo.opendocman.com && LATEST=$(curl -s https://api.github.com/repos/opendocman/opendocman/releases/latest | grep '"tag_name"' | cut -d'"' -f4) && git fetch --tags origin 2>&1 && git checkout $LATEST 2>&1 && php odm.php migrate 2>&1 && php odm.php demo:refresh 2>&1
```

Both run every hour. Each step's `2>&1` pipes stderr to stdout so Cpanel's
cron mail captures errors.

---

## Snapshot Value Object

Simple DTO — no behavior, just data:

```php
class Snapshot
{
    public string $name;
    public DateTimeImmutable $createdAt;
    public string $appVersion;
    public ?string $description;
    public int $dbSize;      // bytes
    public int $filesSize;   // bytes
}
```

---

## What Stays Unchanged

- `demo.php` — removed.
- Existing demo mode (`$GLOBALS['CONFIG']['demo'] === 'True'`) — untouched.
- All demo-mode guards (block user delete/modify, block category delete,
  suppress email, show login credentials) — untouched.
- AdSense ads — untouched.
- All other settings, controllers, views — unchanged.
- Settings page (`settings.php` + `settings.tpl`) — gains `snapshotDir` row
  automatically via the dynamic settings renderer.

---

## Files Changed

| File | Change |
|------|--------|
| `application/models/SnapshotManager.class.php` | **New** — snapshot CRUD |
| `application/installer/cli.php` | Add snapshot + demo:refresh commands |
| `application/installer/SchemaBuilder.php` | Add `snapshotDir` default data |
| `application/installer/migrations/Version001600.php` | **New** — add `snapshotDir` setting |
| `application/version.php` | Bump `ODM_DB_VERSION` |
| `application/controllers/settings.php` | Add `snapshotDir` validation logic |
| `database.sql` | Regenerated via `make dump-sql` |
| `demo.php` | Removed |