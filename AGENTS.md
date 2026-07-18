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
composer test
```