# bcrypt Password Hashing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace unsalted MD5 password hashing with bcrypt (`password_hash`) across OpenDocMan, migrating existing MD5 hashes lazily on successful login.

**Architecture:** A new `PasswordHasher` helper class centralizes hash/verify/needs-rehash logic. All write paths hash in PHP instead of `md5()` in SQL. Login (`User::authenticate`) looks the user up by username, verifies in PHP, and transparently rewrites legacy MD5 hashes to bcrypt on success. The ancient MySQL `PASSWORD()` fallback is dropped. The `password` column is widened to `varchar(255)` via a new migration.

**Tech Stack:** PHP >= 7.4 native `password_hash()`/`password_verify()`/`password_needs_rehash()`/`hash_equals()`. PHPUnit 9 + Mockery. No new dependencies.

## Global Constraints

- PHP floor: `>=7.4` (composer.json) — native `password_*` functions are always available.
- Algorithm: `PASSWORD_DEFAULT` (bcrypt) everywhere, never `PASSWORD_BCRYPT`-by-hand and never a hardcoded cost.
- Legacy MD5 hashes are exactly 32 lowercase/uppercase hex chars; bcrypt hashes start with `$2y$`. Do not treat a bcrypt hash as MD5.
- MySQL `PASSWORD()` SQL function is never used anywhere (removed in MySQL 8).
- Model classes are autoloaded by `public/index.php` via `@include $class . '.class.php'` with `models/` on the include path — a new `PasswordHasher.class.php` needs no registration in the web app.
- Unit tests load models explicitly in `tests/bootstrap.php`; new model files must be added there.
- Git author must be `gh-org-bot-odm` (already configured).

---

### Task 1: `PasswordHasher` helper class with unit tests

**Files:**
- Create: `application/models/PasswordHasher.class.php`
- Create: `tests/Unit/PasswordHasherTest.php`
- Modify: `tests/bootstrap.php` (add `require_once` for the new class)

**Interfaces:**
- Produces:
  - `PasswordHasher::hash(string $plain): string` — bcrypt hash
  - `PasswordHasher::verify(string $plain, string $stored): bool`
  - `PasswordHasher::needsRehash(string $stored): bool`

- [ ] **Step 1: Add the class to the test bootstrap**

In `tests/bootstrap.php`, after the `User.class.php` require (line 27), add:

```php
require_once APPLICATION_PATH . '/models/PasswordHasher.class.php';
```

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/PasswordHasherTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function testHashProducesBcryptHash(): void
    {
        $hash = PasswordHasher::hash('secret123');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertNotSame('secret123', $hash);
    }

    public function testHashIsSalted(): void
    {
        $this->assertNotSame(
            PasswordHasher::hash('secret123'),
            PasswordHasher::hash('secret123')
        );
    }

    public function testVerifyMatchesBcryptHash(): void
    {
        $hash = PasswordHasher::hash('secret123');
        $this->assertTrue(PasswordHasher::verify('secret123', $hash));
        $this->assertFalse(PasswordHasher::verify('wrong', $hash));
    }

    public function testVerifyMatchesLegacyMd5Hash(): void
    {
        $this->assertTrue(PasswordHasher::verify('secret123', md5('secret123')));
        $this->assertFalse(PasswordHasher::verify('wrong', md5('secret123')));
    }

    public function testNeedsRehashForMd5(): void
    {
        $this->assertTrue(PasswordHasher::needsRehash(md5('secret123')));
    }

    public function testNeedsRehashForCurrentBcrypt(): void
    {
        $this->assertFalse(PasswordHasher::needsRehash(PasswordHasher::hash('secret123')));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter PasswordHasherTest`
Expected: FAIL — `Class "PasswordHasher" not found`

- [ ] **Step 4: Write minimal implementation**

Create `application/models/PasswordHasher.class.php`:

```php
<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

/**
 * Centralized password hashing using bcrypt via PHP's native password_*
 * functions. Also verifies legacy unsalted MD5 hashes so they can be lazily
 * upgraded to bcrypt on login.
 */
class PasswordHasher
{
    /**
     * @param string $plain
     * @return string bcrypt hash
     */
    public static function hash($plain)
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * @param string $plain
     * @param string $stored
     * @return bool
     */
    public static function verify($plain, $stored)
    {
        if (self::isMd5Hash($stored)) {
            return hash_equals($stored, md5($plain));
        }
        return password_verify($plain, $stored);
    }

    /**
     * @param string $stored
     * @return bool true if the stored hash is legacy MD5 or an outdated bcrypt hash
     */
    public static function needsRehash($stored)
    {
        if (self::isMd5Hash($stored)) {
            return true;
        }
        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    /**
     * @param string $stored
     * @return bool
     */
    private static function isMd5Hash($stored)
    {
        return is_string($stored) && strlen($stored) === 32 && ctype_xdigit($stored);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter PasswordHasherTest`
Expected: PASS (6 tests)

- [ ] **Step 6: Commit**

```bash
git add tests/bootstrap.php tests/Unit/PasswordHasherTest.php application/models/PasswordHasher.class.php
git commit -m "feat: add centralized bcrypt password hashing helper"
```

---

### Task 2: User model — `changePassword` and `validatePassword`

**Files:**
- Modify: `application/models/User.class.php:301-366` (the two methods)
- Modify: `tests/Unit/UserModelTest.php` (methods at lines 663-726)

**Interfaces:**
- Consumes: `PasswordHasher::hash()`, `PasswordHasher::verify()`, `PasswordHasher::needsRehash()` (Task 1)
- Produces (unchanged signatures, new behavior):
  - `User::changePassword(string $plain): bool`
  - `User::validatePassword(string $plain): bool` — now reads the stored hash and verifies in PHP; lazily rehashes legacy MD5 via `changePassword()`.

- [ ] **Step 1: Update the failing tests**

In `tests/Unit/UserModelTest.php`, replace `testChangePassword` (lines 660-674), `testValidatePasswordWithValidPassword` (lines 676-689), `testValidatePasswordWithOldStylePassword` (lines 691-710), and `testValidatePasswordWithInvalidPassword` (lines 712-726) with:

```php
    /**
     * Test changePassword method stores a bcrypt hash
     */
    public function testChangePassword(): void
    {
        $newPassword = 'new_password_123';

        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function ($params) use ($newPassword) {
                return isset($params[':password_hash'])
                    && PasswordHasher::verify($newPassword, $params[':password_hash']);
            }))
            ->andReturn(true);

        $result = $this->user->changePassword($newPassword);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with valid bcrypt password
     */
    public function testValidatePasswordWithValidPassword(): void
    {
        $password = 'correct_password';

        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(PasswordHasher::hash($password));

        $result = $this->user->validatePassword($password);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with legacy MD5 hash (lazy rehash)
     */
    public function testValidatePasswordWithLegacyMd5Hash(): void
    {
        $password = 'old_style_password';

        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(md5($password));

        // verify() succeeds, needsRehash() is true -> changePassword() runs an
        // UPDATE (execute returns true by default), then returns true
        $result = $this->user->validatePassword($password);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with invalid password
     */
    public function testValidatePasswordWithInvalidPassword(): void
    {
        $password = 'wrong_password';

        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(PasswordHasher::hash('something_else'));

        $result = $this->user->validatePassword($password);
        $this->assertFalse($result);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserModelTest`
Expected: FAIL — `testValidatePasswordWithValidPassword` returns false (SQL still filters by `password = md5(...)`, mock `fetchColumn` returns false).

- [ ] **Step 3: Implement the new method bodies**

In `application/models/User.class.php`, replace `changePassword` (lines 301-319) with:

```php
        /**
         * @param string $non_encrypted_password
         * @return bool
         */
        public function changePassword($non_encrypted_password)
        {
            $passwordHash = PasswordHasher::hash($non_encrypted_password);
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->tablename
              SET
                password = :password_hash,
                pw_change_required = 0
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':password_hash' => $passwordHash,
                ':id' => $this->id
            ));
            $this->pw_change_required = 0;
            return true;
        }
```

Replace `validatePassword` (lines 321-366) with:

```php
        /**
         * @param string $non_encrypted_password
         * @return bool
         */
        public function validatePassword($non_encrypted_password)
        {
            $query = "
              SELECT
                password
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->tablename
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $storedHash = $stmt->fetchColumn();

            if ($storedHash !== false && PasswordHasher::verify($non_encrypted_password, $storedHash)) {
                if (PasswordHasher::needsRehash($storedHash)) {
                    $this->changePassword($non_encrypted_password);
                }
                return true;
            }
            return false;
        }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserModelTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add application/models/User.class.php tests/Unit/UserModelTest.php
git commit -m "feat: verify and rehash passwords in User model via PasswordHasher"
```

---

### Task 3: `User::authenticate` + login controller

**Files:**
- Modify: `application/models/User.class.php` (add static method near `exists()`)
- Modify: `application/controllers/index.php:92-176` (login POST block)
- Modify: `tests/Unit/UserModelTest.php` (add authenticate tests)

**Interfaces:**
- Consumes: `PasswordHasher::hash()`, `PasswordHasher::verify()`, `PasswordHasher::needsRehash()` (Task 1)
- Produces:
  - `User::authenticate(string $username, string $plainPassword, PDO $connection): int|false` — returns the user id on success, `false` otherwise. Performs lazy rehash of legacy MD5 / outdated bcrypt hashes.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Unit/UserModelTest.php` (inside the class, before the closing brace):

```php
    /**
     * Test authenticate with a valid bcrypt password
     */
    public function testAuthenticateWithValidBcryptPassword(): void
    {
        $this->mockStatement->shouldReceive('fetch')
            ->once()
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'password' => PasswordHasher::hash('correct_password'),
            ]);

        $result = User::authenticate('testuser', 'correct_password', $this->mockConnection);
        $this->assertSame(1, $result);
    }

    /**
     * Test authenticate with a legacy MD5 hash (lazy rehash happens)
     */
    public function testAuthenticateWithLegacyMd5Password(): void
    {
        $this->mockStatement->shouldReceive('fetch')
            ->once()
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'password' => md5('correct_password'),
            ]);

        // needsRehash is true -> an UPDATE runs; prepare/execute return the
        // mock statement by default, so no extra setup is needed
        $result = User::authenticate('testuser', 'correct_password', $this->mockConnection);
        $this->assertSame(1, $result);
    }

    /**
     * Test authenticate with a wrong password
     */
    public function testAuthenticateWithWrongPassword(): void
    {
        $this->mockStatement->shouldReceive('fetch')
            ->once()
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'password' => PasswordHasher::hash('correct_password'),
            ]);

        $result = User::authenticate('testuser', 'wrong_password', $this->mockConnection);
        $this->assertFalse($result);
    }

    /**
     * Test authenticate with an unknown user
     */
    public function testAuthenticateUnknownUser(): void
    {
        $this->mockStatement->shouldReceive('fetch')
            ->once()
            ->andReturn(false);

        $result = User::authenticate('nobody', 'password', $this->mockConnection);
        $this->assertFalse($result);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserModelTest`
Expected: FAIL — `Call to undefined method User::authenticate()`

- [ ] **Step 3: Implement `User::authenticate`**

In `application/models/User.class.php`, immediately after `exists()` (which ends at line 670), add:

```php
        /**
         * authenticate - Verify a username/password against the stored hash,
         * lazily upgrading legacy MD5 hashes to bcrypt on success.
         * @param string $username
         * @param string $plainPassword
         * @param PDO $connection
         * @return int|false the user id, or false on failure
         */
        public static function authenticate($username, $plainPassword, PDO $connection)
        {
            $query = "SELECT id, username, password FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = :username";
            $stmt = $connection->prepare($query);
            $stmt->execute(array(':username' => $username));
            $row = $stmt->fetch();

            if (!$row || !PasswordHasher::verify($plainPassword, $row['password'])) {
                return false;
            }

            if (PasswordHasher::needsRehash($row['password'])) {
                $update = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET password = :hash WHERE id = :id";
                $stmt2 = $connection->prepare($update);
                $stmt2->execute(array(
                    ':hash' => PasswordHasher::hash($plainPassword),
                    ':id' => $row['id']
                ));
            }

            return (int) $row['id'];
        }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserModelTest`
Expected: PASS

- [ ] **Step 5: Rewrite the login controller**

In `application/controllers/index.php`, replace the block from line 92 (`$frmuser = ...`) through line 137 (end of the MySQL `password()` fallback query), so the login becomes:

```php
    $frmuser = $_POST['frmuser'];
    $frmpass = $_POST['frmpass'];

    // Authenticate against bcrypt hashes; legacy MD5 hashes are lazily
    // upgraded to bcrypt on successful login
    $id = User::authenticate($frmuser, $frmpass, $pdo);

    // if row exists - login/pass is correct
    if ($id !== false) {
        // register the user's ID
        $_SESSION['uid'] = $id;
```

The old `$id = $result[0]['id'];` line and the comment `// check login and md5()` (line 95) must be removed. Everything from line 144 onwards (session, pw change check, plugins, redirects) stays as-is.

- [ ] **Step 6: Lint the touched files**

Run: `php application/vendor/bin/phplint application/controllers/index.php application/models/User.class.php`
Expected: no syntax errors.

- [ ] **Step 7: Commit**

```bash
git add application/models/User.class.php application/controllers/index.php tests/Unit/UserModelTest.php
git commit -m "feat: authenticate via bcrypt with lazy rehash on login"
```

---

### Task 4: Remaining write paths

**Files:**
- Modify: `application/controllers/user.php:95-108` (create) and `:273-304` (admin edit password)
- Modify: `application/controllers/signup.php:56-86`
- Modify: `application/controllers/forgot_password.php:55-75`
- Modify: `tests/Unit/UserControllerFunctionsTest.php:591-593` (test helper mirrors controller SQL)

**Interfaces:**
- Consumes: `PasswordHasher::hash()` (Task 1)

- [ ] **Step 1: Update the test helper**

In `tests/Unit/UserControllerFunctionsTest.php`, change the `updateUser` helper (lines 591-593) so the controller's new behavior is mirrored:

```php
        if (!empty($userData['password'])) {
            $query .= " password = :password, ";
        }
```

(The test helper never exercises the PHP-side hashing — it only mirrors the SQL the controller builds. No assertion changes are needed.)

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserControllerFunctionsTest`
Expected: PASS

- [ ] **Step 2: Update `user.php` create-user SQL**

In `application/controllers/user.php`, change the INSERT (lines 95-108) from `md5(:password)` to `:password`:

```php
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user
                    (username, password, department, phone, Email,last_name, first_name, can_add, can_checkin, pw_change_required)
                    VALUES(
                        :username,
                        :password,
                        :department,
                        :phonenumber,
                        :email,
                        :lastname,
                        :firstname,
                        :can_add,
                        :can_checkin,
                        1
                )";
```

Then in the `execute` array (lines 111-119), replace `':password' => $_POST['password'],` with:

```php
            ':password' => PasswordHasher::hash($_POST['password']),
```

- [ ] **Step 3: Update `user.php` admin-edit SQL**

Change line 274 from `password = md5(:password)` to `password = :password`:

```php
    if (!empty($_POST['password'])) {
        $query .= " password = :password, ";
    }
```

Replace the `bindParam(':password', $_POST['password']);` at line 303 with a bound, pre-hashed variable:

```php
    if (!empty($_POST['password'])) {
        $passwordHash = PasswordHasher::hash($_POST['password']);
        $stmt->bindParam(':password', $passwordHash);
    }
```

- [ ] **Step 4: Update `signup.php`**

In `application/controllers/signup.php`, change line 69 from `md5(:password),` to `:password,`, and change line 80 from `':password' => $_POST['password'],` to:

```php
                ':password' => PasswordHasher::hash($_POST['password']),
```

- [ ] **Step 5: Update `forgot_password.php`**

In `application/controllers/forgot_password.php`, change line 59 from `password = md5(:new_pass),` to `password = :new_pass,`, and change line 71 from `':new_pass' => $newPass,` to:

```php
        ':new_pass' => PasswordHasher::hash($newPass),
```

- [ ] **Step 6: Lint the touched files**

Run: `php application/vendor/bin/phplint application/controllers/user.php application/controllers/signup.php application/controllers/forgot_password.php`
Expected: no syntax errors.

- [ ] **Step 7: Commit**

```bash
git add application/controllers/user.php application/controllers/signup.php application/controllers/forgot_password.php tests/Unit/UserControllerFunctionsTest.php
git commit -m "feat: hash passwords with bcrypt on create, edit, signup and reset"
```

---

### Task 5: Installer and seed scripts

**Files:**
- Modify: `application/installer/SchemaBuilder.php:169,187`
- Modify: `application/installer/cli.php:405`
- Modify: `application/controllers/install/odm.php:248`
- Modify: `scripts/seed_test_user.php:87`

**Interfaces:**
- Consumes: PHP native `password_hash()` (SchemaBuilder/odm.php are standalone, no autoloader) and `PasswordHasher::hash()` (seed script requires the class directly).

- [ ] **Step 1: Update SchemaBuilder**

In `application/installer/SchemaBuilder.php`:

- Line 169, change the default so `--admin-password` is treated as plaintext:

```php
        $adminPassword = $options['admin_password'] ?? 'admin';
```

- Line 187, embed a PHP-computed bcrypt hash into the SQL dump (the hash is concatenated, never interpolated through `md5()`):

```php
            "INSERT INTO `{$prefix}user` VALUES (NULL,'admin','" . password_hash($adminPassword, PASSWORD_DEFAULT) . "','1','5555551212','admin@example.com','User','Admin','',0,1,1)",
```

- [ ] **Step 2: Update the CLI usage text**

In `application/installer/cli.php` line 405, change:

```php
        echo "    --admin-password=MD5 Admin password hash (default: md5('admin'))\n";
```

to:

```php
        echo "    --admin-password=Admin password in plaintext (default: admin)\n";
```

- [ ] **Step 3: Update the legacy web installer**

In `application/controllers/install/odm.php` line 248, change:

```php
$query = "INSERT INTO {$dbprefix}user VALUES (NULL,'admin',md5('{$adminpass}'),'1','5555551212','admin@example.com','User','Admin','',1,1)";
```

to:

```php
$query = "INSERT INTO {$dbprefix}user VALUES (NULL,'admin','" . password_hash($adminpass, PASSWORD_DEFAULT) . "','1','5555551212','admin@example.com','User','Admin','',1,1)";
```

- [ ] **Step 4: Update the E2E seed script**

In `scripts/seed_test_user.php`, add the class require after the DB connection block (after line 63):

```php
require_once __DIR__ . '/../application/models/PasswordHasher.class.php';
```

And change line 87 from `':p' => md5($password),` to:

```php
    ':p' => PasswordHasher::hash($password),
```

- [ ] **Step 5: Verify install SQL and seed still work**

Run: `php application/installer/cli.php dump-sql --prefix=odm_ | grep "INSERT INTO \`odm_user\`" | head -1`
Expected: an INSERT with a `$2y$...` hash for the admin row.

Run: `php -l scripts/seed_test_user.php`
Expected: no syntax errors.

- [ ] **Step 6: Commit**

```bash
git add application/installer/SchemaBuilder.php application/installer/cli.php application/controllers/install/odm.php scripts/seed_test_user.php
git commit -m "feat: seed admin and test users with bcrypt hashes"
```

---

### Task 6: Schema migration and version bump

**Files:**
- Create: `application/installer/migrations/Version001702.php`
- Modify: `application/version.php:21`
- Modify: `application/installer/SchemaBuilder.php:104`
- Modify: `application/controllers/install/odm.php:236` (table definition)
- Modify: `database.sql` (regenerated)

**Interfaces:**
- Consumes: none (migration is self-contained)
- Produces: `Version001702` auto-discovered by `MigrationLoader` via `glob()`; `ODM_DB_VERSION` bumped so the installer runs it.

- [ ] **Step 1: Write the migration**

Create `application/installer/migrations/Version001702.php`:

```php
<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001702 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.2';
    }

    public function getDescription(): string
    {
        return 'Widen user password column to varchar(255) for bcrypt hashes';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` MODIFY COLUMN `password` varchar(255) NOT NULL default ''");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}user` MODIFY COLUMN `password` varchar(50) NOT NULL default ''");
    }
}
```

- [ ] **Step 2: Bump the DB version**

In `application/version.php` line 21, change:

```php
const ODM_DB_VERSION = '1.7.1';
```

to:

```php
const ODM_DB_VERSION = '1.7.2';
```

- [ ] **Step 3: Update the schema definitions**

In `application/installer/SchemaBuilder.php` line 104, change:

```php
                password varchar(50) NOT NULL default '',
```

to:

```php
                password varchar(255) NOT NULL default '',
```

In `application/controllers/install/odm.php` line 236, change:

```php
  password varchar(50) NOT NULL default '',
```

to:

```php
  password varchar(255) NOT NULL default '',
```

- [ ] **Step 4: Regenerate database.sql**

Run: `make dump-sql`
Expected: `database.sql` regenerated; the `odm_user` table definition now has `password varchar(255)` and the admin seed row has a `$2y$` hash.

- [ ] **Step 5: Verify migration status output**

Run: `php application/installer/cli.php status`
Expected: shows `1.7.2` as a pending migration (version listed, not applied).

- [ ] **Step 6: Run the full unit suite**

Run: `make test`
Expected: all unit tests pass.

- [ ] **Step 7: Commit**

```bash
git add application/installer/migrations/Version001702.php application/version.php application/installer/SchemaBuilder.php application/controllers/install/odm.php database.sql
git commit -m "feat: widen password column for bcrypt and bump DB version to 1.7.2"
```

---

### Task 7: E2E verification

**Files:**
- None (uses existing `tests/smoke-uat.spec.ts`)

**Interfaces:**
- Consumes: all previous tasks. Requires the app running on `:8080` and a seeded DB. If the dev DB admin is still `admin`/`admin`, the lazy rehash on login upgrades it — the E2E login must still succeed.

- [ ] **Step 1: Seed the non-admin user**

Run: `php scripts/seed_test_user.php`
Expected: prints "Seeded non-admin test user 'e2euser'" (or "already exists").

- [ ] **Step 2: Run the E2E smoke test**

Run (if the app is already running on `:8080`):

```bash
npm run test:e2e
```

Expected: the suite passes — login (bcrypt verify + lazy rehash), settings change, persistence, and cleanup all succeed.

If the app is not running, note that E2E requires `http://localhost:8080` and defer this step; the unit suite in Task 6 is the authoritative automated check.

- [ ] **Step 3: Confirm no md5 password hashing remains**

Run: `grep -rn "md5(" application --include="*.php" | grep -v vendor | grep -iv "smart\|reset_code\|randstring\|uniqid\|tag_guard"`

Expected: no matches in auth code. The remaining matches (if any) are the `pw_reset_code` generation in `forgot_password.php` (out of scope) and Smarty's internal tag guards.

- [ ] **Step 4: Commit (if any artifacts changed)**

```bash
git status
```

Only commit if E2E or seed produced tracked-file changes (unexpected). Otherwise nothing to commit.
