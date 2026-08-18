# bcrypt Password Hashing

Date: 2026-08-17
Status: Design approved, ready for implementation

## Problem

OpenDocMan hashes user passwords with `md5()` in every write path and checks
them with `WHERE password = md5(...)` at login. MD5 is unsalted, effectively
instant to brute-force, and has been cryptographically broken. The issue
(https://github.com/opendocman/opendocman/issues/127) has been open since 2013.

The app already requires PHP >= 7.4 (composer.json), so PHP's native
`password_hash()` / `password_verify()` are available — no external library is
needed (the `ircmaxell/password_compat` library suggested in the issue targets
PHP < 5.5 and is obsolete).

## Decision

Use `password_hash($plain, PASSWORD_DEFAULT)` (bcrypt) for all new password
hashes, with **lazy rehash**: existing MD5 hashes are verified at login and
transparently upgraded to bcrypt on success. The legacy MySQL `PASSWORD()`
function fallback (MySQL 4.x-era hashes) is dropped — it errors on MySQL 8+
where the function was removed.

## Design

### Helper: `PasswordHasher` class

New file `application/models/PasswordHasher.class.php`, following the existing
`*.class.php` convention. All password hashing/verification goes through it so
the logic lives in one place.

```php
class PasswordHasher
{
    /** @return string bcrypt hash */
    public static function hash(string $plain): string;

    /** Verify plain against a stored hash (bcrypt, or legacy MD5 32-hex). */
    public static function verify(string $plain, string $stored): bool;

    /** True when $stored is a legacy MD5 hash or an out-of-date bcrypt hash. */
    public static function needsRehash(string $stored): bool;
}
```

Behavior:
- `hash()` returns `password_hash($plain, PASSWORD_DEFAULT)`.
- `verify()` calls `password_verify()`. If `$stored` is a 32-char hex string
  (MD5 format), it instead compares `md5($plain) === $stored` using
  `hash_equals()` for constant-time comparison.
- `needsRehash()` returns true for any 32-char-hex MD5 hash, and otherwise
  delegates to `password_needs_rehash($stored, PASSWORD_DEFAULT)`.

### Login flow (`application/controllers/index.php`)

Replace the `WHERE username = :frmuser AND password = md5(:frmpass)` query with
a lookup by username only (selecting `id, username, password`). If a row is
found, verify in PHP via `PasswordHasher::verify()`. On success, if
`PasswordHasher::needsRehash($stored)` is true, issue an UPDATE that stores
`PasswordHasher::hash($frmpass)` for that user (lazy upgrade).

The second legacy query block using MySQL `password()` is removed entirely.

### User model (`application/models/User.class.php`)

- `changePassword($plain)`: compute `PasswordHasher::hash($plain)` in PHP and
  bind the result as a parameter; remove `md5(...)` from the SQL.
- `validatePassword($plain)`: change the query to select `username` by `id`
  only (drop the `password = md5(...)` predicate), then verify in PHP. On
  success, lazily rehash if needed. Remove the MySQL `password()` fallback
  block.

### Other write paths

All switch from `md5(:pass)` in SQL to `PasswordHasher::hash()` in PHP before
the query:

| File | Location | Change |
|------|----------|--------|
| `application/controllers/user.php` | create user INSERT | hash in PHP |
| `application/controllers/user.php` | change password UPDATE | hash in PHP |
| `application/controllers/signup.php` | account INSERT | hash in PHP |
| `application/controllers/forgot_password.php` | reset UPDATE | hash in PHP |

### Installer and seeds

- `application/installer/SchemaBuilder.php`: seed admin with
  `password_hash($adminPassword, PASSWORD_DEFAULT)`; default password stays
  `admin`. `--admin-password` is now interpreted as a **plaintext** password
  (previously it was an MD5 hash that got double-hashed — a bug we are fixing).
- `application/installer/cli.php`: update the `--admin-password` usage text to
  say "Admin password (plaintext, default: admin)".
- `application/controllers/install/odm.php`: seed admin with
  `PasswordHasher::hash($adminpass)`.
- `scripts/seed_test_user.php`: store `PasswordHasher::hash($password)`.

### Schema migration

bcrypt hashes are 60 chars; the column is currently `varchar(50)`.

- New migration `application/installer/migrations/Version001702.php`:
  `ALTER TABLE {prefix}user MODIFY password varchar(255) NOT NULL default ''`.
- Bump `ODM_DB_VERSION` in `application/version.php` from `1.7.1` to `1.7.2`.
- Update the `password varchar(50)` definition to `varchar(255)` in
  `SchemaBuilder.php` and in the legacy inline table definition in
  `application/controllers/install/odm.php`.
- Run `make dump-sql` to regenerate `database.sql`.

### Testing

- New unit test `tests/Unit/PasswordHasherTest.php` covering:
  - `hash()` produces a `$2y$...` bcrypt string
  - `verify()` matches bcrypt and MD5 formats, rejects wrong passwords
  - `needsRehash()` is true for MD5 and out-of-date bcrypt cost, false for
    current bcrypt
- Update `tests/Unit/UserModelTest.php` (changePassword / validatePassword
  assertions) and `tests/Unit/UserControllerFunctionsTest.php` to no longer
  expect `md5()` in generated SQL.
- E2E and seed scripts already log in with plaintext passwords, so they keep
  working as long as the seeds produce bcrypt hashes.

## Files Changed

| File | Change |
|------|--------|
| `application/models/PasswordHasher.class.php` | New helper class |
| `application/controllers/index.php` | Login: lookup by username, PHP verify, lazy rehash; drop `password()` fallback |
| `application/models/User.class.php` | `changePassword` / `validatePassword` use helper; drop `password()` fallback |
| `application/controllers/user.php` | Create + change password hash in PHP |
| `application/controllers/signup.php` | Account creation hashes in PHP |
| `application/controllers/forgot_password.php` | Reset hashes in PHP |
| `application/installer/SchemaBuilder.php` | Admin seed hashes via `password_hash`; column to varchar(255) |
| `application/installer/cli.php` | `--admin-password` usage text |
| `application/controllers/install/odm.php` | Admin seed + column to varchar(255) |
| `scripts/seed_test_user.php` | Hash via helper |
| `application/installer/migrations/Version001702.php` | New: widen password column |
| `application/version.php` | `ODM_DB_VERSION` -> 1.7.2 |
| `database.sql` | Regenerated via `make dump-sql` |
| `tests/Unit/PasswordHasherTest.php` | New unit tests |
| `tests/Unit/UserModelTest.php` | Update md5 expectations |
| `tests/Unit/UserControllerFunctionsTest.php` | Update md5 expectations |

## Out of Scope

- `pw_reset_code` is also an `md5()` of a random string (password reset link).
  Separate concern; worth its own issue.
- Enforcing password strength policy.
- Adding a cost/algorithm configuration setting.
