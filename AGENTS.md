# Database schema changes

When modifying the database schema in `SchemaBuilder.php`:
1. Run `make dump-sql` to regenerate `database.sql`
2. Bump `ODM_DB_VERSION` in `application/version.php` to trigger the installer

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

Credentials are read from environment variables `ADMIN_USER` (default: `admin`)
and `ADMIN_PASSWORD` (default: `password`). Playwright auto-loads `.env` files,
so the project's existing `.env` is picked up automatically.

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

## Makefile targets

```bash
make test              # Run all tests via scripts/run-tests.sh
make test-quiet        # Minimal output mode
make test-e2e          # Playwright E2E smoke test (requires app on :8080)
make test-coverage     # HTML coverage report
make dump-sql          # Regenerate database.sql from SchemaBuilder
```