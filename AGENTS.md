# Database schema changes

When modifying the database schema in `SchemaBuilder.php`:
1. Run `make dump-sql` to regenerate `database.sql`
2. Bump `ODM_DB_VERSION` in `application/version.php` to trigger the installer

## Adding a new migration

Create a new `Version*.php` file in `application/installer/migrations/`
implementing `MigrationInterface`. That's it — `MigrationLoader::getAll()`
auto-discovers it via `glob()` so no manual registration in entry points
is needed. Bump `ODM_DB_VERSION` in `application/version.php`.

# Translation strings

New `$lang[...]` entries must be added to **all 17 language files** under
`application/includes/language/`, not just `english.php`.

# Testing

## E2E smoke test (Playwright)

```bash
npm run test:e2e
```

This runs `tests/smoke-uat.spec.ts` against `http://localhost:8080` — logs in,
changes a setting, verifies persistence, and cleans up.

The `retryGoto` helper is used for all page navigations to handle the
PHP built-in server's intermittent empty-response race condition.
Use `retryGoto(page, url)` instead of `page.goto(url)` in new E2E tests.

Credentials are read from environment variables `ADMIN_USER` (default: `admin`)
and `ADMIN_PASSWORD` (default: `password`). `tests/global-setup.ts` loads the
project `.env` into the test process before workers start (Playwright does not
auto-load `.env` itself), and it runs the non-admin user seed. Explicit shell
env vars override `.env` values.

Before each run, `global-setup.ts` also runs `scripts/cleanup_e2e_data.php`,
which deletes leftover rows from previous E2E runs (categories/departments
named `E2E %`, files named `odm-*`/`test_doc*`). Several tests intentionally
do not delete their own artifacts, so without this cleanup the accumulated
rows eventually push a freshly-created row past a listing's page size and
break assertions. Run it manually if a prior run was interrupted:

## Ensuring correct credentials

The seeded DB admin password may differ from `.env` `ADMIN_PASSWORD` (e.g. the
dev DB uses `admin`/`admin`, not `password`). If every E2E login fails with
"There was an error logging you in", align `.env`'s `ADMIN_PASSWORD` with the
actual DB admin password (or override on the CLI):

```bash
ADMIN_USER=admin ADMIN_PASSWORD=admin npm run test:e2e
```

## Non-admin E2E coverage

The Permission-inheritance E2E suite (`tests/smoke-uat.spec.ts`) tests that a
non-admin owner keeps admin rights after adding a document, and that switching
the owner on the add/edit form moves the admin grant. These require a seeded
non-admin user. Seed it (idempotent) before running:

```bash
php scripts/seed_test_user.php
```

The user defaults to `e2euser` / `e2euserpass` (display name "User, E2E").
Override via env `NON_ADMIN_USER`, `NON_ADMIN_PASSWORD`; the seed script reads
DB creds from `.env` / env vars. The E2E tests read `NON_ADMIN_USER`,
`NON_ADMIN_PASSWORD`, and `NON_ADMIN_DISPLAY` (default "User, E2E").

## PHPUnit tests

```bash
# Via Makefile (recommended — runs all tests with proper setup)
make test
make test-unit        # unit tests only
make test-integration # integration tests only
make test-user        # user-related tests

# Direct phpunit (vendor is in application/vendor)
php application/vendor/bin/phpunit -c phpunit.xml.dist
php application/vendor/bin/phpunit -c phpunit.xml.dist --filter User 2>&1 | tail -20
```

# Git & GitHub

Before committing, verify `git config user.name` and `user.email` are set to
the bot account (`gh-org-bot-odm`). Before pushing or creating PRs, ensure
the bot is the active `gh` account with:

```bash
gh auth switch --user gh-org-bot-odm
```

This applies to all git operations unless told otherwise.

Start feature work from a fresh `master` branch unless otherwise stated.

## Makefile targets

```bash
make test              # Run all tests via scripts/run-tests.sh
make test-quiet        # Minimal output mode
make test-e2e          # Playwright E2E smoke test (requires app on :8080)
make test-coverage     # HTML coverage report
make dump-sql          # Regenerate database.sql from SchemaBuilder
```