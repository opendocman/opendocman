# Email Document Ingest Implementation Plan (Issue #17)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let users submit documents by emailing a shared POP3/IMAP mailbox with a per-user secret token in the subject; a CLI poller ingests each valid attachment as an ODM document, honoring the review workflow.

**Architecture:** A `mail:poll` CLI command (in the existing `installer/cli.php`) connects to a mailbox via a Composer IMAP/POP3 client, wraps it in a thin `EmailInbox` adapter producing `EmailMessage` DTOs, and passes each to a pure-business `EmailIngest` model. `EmailIngest` resolves the subject token to a user, validates attachment MIME types, and creates one document per valid attachment via a newly-extracted `Document::create()` (shared with the web add flow). Every outcome is written to a new `email_audit` table. Tokens are managed (shown/rotated) in the user profile and admin user table.

**Tech Stack:** PHP 8+, webklex/php-imap (IMAP is pure-PHP; POP3 requires PHP's native `imap` extension — both supported), Smarty templates, Mockery for tests, PHPUnit, PDO.

## Global Constraints

- Migration number **Version001705** → **`ODM_DB_VERSION` `1.7.5`** (currently `1.7.4`).
- After any `SchemaBuilder.php` change run `make dump-sql` to regenerate `database.sql`.
- New `$lang[...]` strings go into **all 17** language files under `application/includes/language/` (not just `english.php`).
- New settings auto-appear in `settings.tpl` because it iterates the whole settings table; add special-case `<select>` only for `mail_protocol`, `mail_encryption`, `mail_default_category`, `mail_default_department`.
- Ingest token is stored **plaintext** in `user.mail_token` (treated like an API key, NOT a password hash) so it can be re-displayed in the user profile; `email_audit.token_hash` stores a SHA-256 of the token for non-reve audit.
- Sender `From` address is never trusted for attribution. Authentication = subject token only.
- When `CONFIG['authorization'] == 'True'`, ingested docs get `publishable = '0'` and flow into the reviewer queue (`toBePublished`); else `'1'`.
- Unit tests use Mockery-mocked `PDO`/`PDOStatement` (see `tests/TestCase.php`). No live DB or mail server in tests.

---

### Task 1: DB migration — `mail_token`, `email_audit`, `mail_*` settings

**Files:**
- Create: `application/installer/migrations/Version001705.php`
- Modify: `application/installer/SchemaBuilder.php`
- Modify: `application/version.php`
- Test: `tests/Unit/Migration001705Test.php`

**Interfaces:**
- Consumes: `MigrationInterface` (already present, auto-discovered by `MigrationLoader::getAll()`).
- Produces: new `user.mail_token` column, new `email_audit` table, 11 `mail_*` settings rows; `ODM_DB_VERSION` `1.7.5`.

- [ ] **Step 1: Write the failing migration test**

```php
<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/installer/migrations/MigrationInterface.php';
require_once APPLICATION_PATH . '/installer/migrations/Version001705.php';

class Migration001705Test extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testVersionIs175(): void
    {
        $this->assertSame('1.7.5', (new Version001705())->getVersion());
    }

    public function testUpAddsMailTokenColumnToUser(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/ALTER TABLE.*`user`.*ADD COLUMN.*mail_token/'))
            ->once()
            ->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

    public function testUpCreatesEmailAuditTable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/CREATE TABLE.*`email_audit`/'))
            ->once()
            ->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

    public function testUpInsertsMailSettings(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/INSERT INTO.*settings.*mail_enabled/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/INSERT INTO.*settings.*mail_host/'))
            ->once()
            ->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

    public function testDownDropsEmailAuditTable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/DROP TABLE.*`email_audit`/'))
            ->once()
            ->andReturn(1);
        $migration = new Version001705();
        $migration->down($pdo, 'odm_');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/Migration001705Test.php 2>&1 | tail -20`
Expected: FAIL with "Class 'Version001705' not found".

- [ ] **Step 3: Create the migration**

```php
<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001705 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.5';
    }

    public function getDescription(): string
    {
        return 'Add email ingest: user.mail_token, email_audit table, mail settings';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "ALTER TABLE `{$prefix}user` ADD COLUMN mail_token varchar(255) NULL DEFAULT NULL"
        );

        $pdo->exec(
            "CREATE TABLE `{$prefix}email_audit` (
                id int(11) unsigned NOT NULL auto_increment,
                message_id varchar(255) default NULL,
                from_address varchar(255) default NULL,
                token_hash varchar(64) default NULL,
                outcome varchar(20) NOT NULL,
                document_id int(11) unsigned default NULL,
                reason text default NULL,
                created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE = MYISAM"
        );

        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_enabled', 'False', 'Enable the email document ingest feature', 'bool')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_host', '', 'Mail server host for the ingest mailbox', 'alpha|req')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_port', '993', 'Mail server port', 'num')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_protocol', 'imap', 'Mailbox protocol: imap or pop3', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_encryption', 'ssl', 'Mailbox encryption: none, ssl, or tls', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_user', '', 'Mailbox username', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_pass', '', 'Mailbox password', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_folder', 'INBOX', 'Mailbox folder to poll', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_folder', 'INBOX', 'Mailbox folder to poll', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_delete', 'False', 'Delete processed messages from the mailbox after ingestion', 'bool')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_default_category', '', 'Default category id for ingested documents', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_default_department', '', 'Default department id for ingested documents', '')");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}email_audit`");
        $pdo->exec("ALTER TABLE `{$prefix}user` DROP COLUMN mail_token");
        foreach (['mail_enabled','mail_host','mail_port','mail_protocol','mail_encryption','mail_user','mail_pass','mail_folder','mail_delete','mail_default_category','mail_default_department'] as $name) {
            $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = '{$name}'");
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/Migration001705Test.php 2>&1 | tail -20`
Expected: PASS (all assertions).

- [ ] **Step 5: Update `SchemaBuilder.php`**

In `SchemaBuilder.php`:
- Add `mail_token varchar(255) NULL default NULL,` to the `{prefix}user` CREATE TABLE (around line 113, after `can_checkin`).
- Add a `{prefix}email_audit` CREATE TABLE (matching the migration's table) after the `content_index` table block.
- Add the 11 `mail_*` settings (including `mail_delete`) to `getDefaultDataStatements()` after the existing settings (around line 204).

- [ ] **Step 6: Bump the DB version and regenerate SQL**

In `application/version.php` change line 21 to `const ODM_DB_VERSION = '1.7.5';`.
Run: `make dump-sql`
Expected: `database.sql` regenerated; confirm it contains `email_audit` and `mail_token`.

- [ ] **Step 7: Commit**

```bash
git add application/installer/migrations/Version001705.php application/installer/SchemaBuilder.php application/version.php database.sql tests/Unit/Migration001705Test.php
git commit -m "feat: DB migration for email ingest (mail_token, email_audit, mail settings)"
```

---

### Task 2: MailToken helper + User model methods

**Files:**
- Create: `application/models/MailToken.class.php`
- Modify: `application/models/User.class.php`
- Test: `tests/Unit/MailTokenTest.php`

**Interfaces:**
- Consumes: nothing external (uses `random_bytes`).
- Produces:
  - `MailToken::generate(): string` — returns `'odm-' . bin2hex(random_bytes(10))` (22-char token).
  - `User::getMailToken(): string` — plaintext token (or `''` if none).
  - `User::rotateMailToken(): string` — generates a new token, persists it, returns the plaintext.
  - `User::setMailToken(string $token): void` — persists a given token.
  - `User::hasMailToken(): bool`

- [ ] **Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/MailToken.class.php';
require_once APPLICATION_PATH . '/models/User.class.php';

class MailTokenTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGenerateReturnsOdmPrefixedToken(): void
    {
        $token = MailToken::generate();
        $this->assertSame(0, strpos($token, 'odm-'));
        $this->assertGreaterThan(8, strlen($token));
    }

    public function testGenerateIsUnique(): void
    {
        $this->assertNotSame(MailToken::generate(), MailToken::generate());
    }
}
```

- [ ] **Step 2: Run to verify failure**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/MailTokenTest.php 2>&1 | tail -20`
Expected: FAIL, class not found.

- [ ] **Step 3: Create `MailToken.class.php`**

```php
<?php

class MailToken
{
    public static function generate(): string
    {
        return 'odm-' . bin2hex(random_bytes(16));
    }
}
```

- [ ] **Step 4: Run to verify pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/MailTokenTest.php 2>&1 | tail -20`
Expected: PASS.

- [ ] **Step 5: Add User model methods**

In `application/models/User.class.php`, add these public methods (mirror the `changePassword` UPDATE pattern at line 301-320):

```php
public function getMailToken(): string
{
    $query = "SELECT mail_token FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->execute([':id' => $this->id]);
    $result = $stmt->fetchColumn();
    return is_string($result) ? $result : '';
}

public function setMailToken(string $token): void
{
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET mail_token = :token WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->execute([':token' => $token, ':id' => $this->id]);
}

public function rotateMailToken(): string
{
    $token = MailToken::generate();
    $this->setMailToken($token);
    return $token;
}

public function hasMailToken(): bool
{
    return $this->getMailToken() !== '';
}
```

- [ ] **Step 6: Run the full User test suite to confirm no regression**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/UserTest.php tests/Unit/UserModelTest.php 2>&1 | tail -20`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add application/models/MailToken.class.php application/models/User.class.php tests/Unit/MailTokenTest.php
git commit -m "feat: MailToken helper and User token methods"
```

---

### Task 3: Extract `Document::create()` from the web add flow

**Files:**
- Create: `application/models/Document.class.php`
- Modify: `application/controllers/add.php:299-424`
- Test: `tests/Unit/DocumentTest.php`

**Interfaces:**
- Consumes: nothing from prior tasks.
- Produces:
  - `Document::create(PDO $pdo, array $params): int` — creates a data row + log row + dept perms + user perms + moves the file + indexes text. Returns the new doc id.
  - `$params` keys: `category`(int), `owner_id`(int), `realname`(string), `description`(string), `department`(int), `comment`(string), `publishable`(string `'0'|'1'`), `is_public`(bool), `dept_perms`(array dept_id=>rights), `user_perms`(array user_id=>rights), `source_path`(string), `source_is_upload`(bool, true when from `$_FILES`), `username`(string), `mime`(string).
  - **Behavior note:** the function only performs the DB writes + file move + content index. Email-notification and `callPluginMethod('onAfterAdd')` stay in `add.php` (web-only).

- [ ] **Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class DocumentTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCreatePersistsDataRowAndReturnsId(): void
    {
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['dataDir'] = sys_get_temp_dir();

        $stmt = \Mockery::mock('PDOStatement');
        $stmt->shouldReceive('execute')->andReturn(true);

        $pdo = \Mockery::mock('PDO');
        $pdo->shouldReceive('prepare')->andReturn($stmt);
        $pdo->shouldReceive('lastInsertId')->andReturn(42);

        $id = Document::create($pdo, [
            'category' => 3,
            'owner_id' => 7,
            'realname' => 'report.pdf',
            'description' => 'desc',
            'department' => 2,
            'comment' => 'note',
            'publishable' => '0',
            'is_public' => 0,
            'dept_perms' => [2 => 3],
            'user_perms' => [7 => 4],
            'source_path' => __FILE__,
            'source_is_upload' => false,
            'mime' => 'application/pdf',
        ]);

        $this->assertSame(42, $id);
    }
}
```

Note: the file-move + content-index steps read the source file. In the test, the source is a real temp file (`__FILE__`), so `copy()` succeeds. `TextExtractorFactory::isExtractable('application/pdf')` may be false, so no index write occurs — that's fine. If `isExtractable` returns true and calls a PDF parser on the test file, set the test's mime to `'text/plain'` and give it a matching source path to avoid parser flakiness; adjust the expectation accordingly.

- [ ] **Step 2: Run to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/DocumentTest.php 2>&1 | tail -20`
Expected: FAIL — `Class 'Document' not found`.

- [ ] **Step 3: Create `Document.class.php`**

Move the SQL from `add.php:300-422` into a static `create()`:

```php
<?php

class Document
{
    public static function create(PDO $pdo, array $params): int
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $publishable = (string) $params['publishable'];
        $isPublic = $params['is_public'] ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO {$prefix}data (status, category, owner, realname, created, description, department, comment, default_rights, publishable, is_public) VALUES (0, :category, :owner_id, :realname, NOW(), :description, :current_user_dept, :comment, 0, {$publishable}, {$isPublic})");
        $stmt->bindParam(':category', $params['category']);
        $stmt->bindParam(':owner_id', $params['owner_id']);
        $stmt->bindParam(':realname', $params['realname']);
        $stmt->bindParam(':description', $params['description']);
        $stmt->bindParam(':current_user_dept', $params['department']);
        $stmt->bindParam(':comment', $params['comment']);
        $stmt->execute();
        $fileId = (int) $pdo->lastInsertId();

        $historyStmt = $pdo->prepare("INSERT INTO {$prefix}log (id, modified_on, modified_by, note, revision) VALUES ('{$fileId}', NOW(), :username, 'Initial import', 'current')");
        $historyStmt->bindParam(':username', $params['username']);
        $historyStmt->execute();

        foreach ($params['dept_perms'] as $deptId => $deptPerm) {
            $s = $pdo->prepare("INSERT INTO {$prefix}dept_perms (fid, rights, dept_id) VALUES ({$fileId}, :perm, :did)");
            $s->bindParam(':perm', $deptPerm);
            $s->bindParam(':did', $deptId);
            $s->execute();
        }
        foreach ($params['user_perms'] as $userId => $permission) {
            $s = $pdo->prepare("INSERT INTO {$prefix}user_perms (fid, uid, rights) VALUES ({$fileId}, :uid, :rights)");
            $s->bindParam(':uid', $userId);
            $s->bindParam(':rights', $permission);
            $s->execute();
        }

        $newFilePath = getFilePath($fileId, $params['realname'], 'data');
        $newFileDir = dirname($newFilePath);
        if (!is_dir($newFileDir)) {
            mkdir($newFileDir, 0775, true);
        }
        if ($params['source_is_upload']) {
            move_uploaded_file($params['source_path'], $newFilePath);
        } else {
            copy($params['source_path'], $newFilePath);
        }

        $mime = $params['mime'];
        if (TextExtractorFactory::isExtractable($mime)) {
            $extractor = TextExtractorFactory::create($mime);
            if ($extractor !== null) {
                $contentText = $extractor->extract($newFilePath);
                $indexStmt = $pdo->prepare("INSERT INTO {$prefix}content_index (file_id, content_text, indexed_at) VALUES (:file_id, :content_text, NOW())");
                $indexStmt->execute([':file_id' => $fileId, ':content_text' => $contentText]);
            }
        }

        return $fileId;
    }
}
```

Note: `username` must be supplied in `$params`; in the web flow this is `$user_obj->getUserName()`, in ingest it is the token user's username.

- [ ] **Step 4: Run to verify pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/DocumentTest.php 2>&1 | tail -20`
Expected: PASS. Adjust the MIME/temp-file handling if the index step misbehaves (see step 1 note).

- [ ] **Step 5: Refactor `add.php` to call `Document::create()`**

Replace the block `add.php:299-424` (the `INSERT`, history, dept/user perms, file move, content index, and `AccessLog::addLogEntry($fileId, 'A', $pdo)`) with:

```php
// Gather perms arrays exactly as today
$userPermission = $_REQUEST['user_permission'] ?? [];
$deptPermission = $_POST['department_permission'] ?? [];

$fileId = Document::create($pdo, [
    'category' => (int) $_REQUEST['category'],
    'owner_id' => (int) $owner_id,
    'realname' => $_FILES['file']['name'][$count],
    'description' => $_REQUEST['description'],
    'department' => (int) $current_user_dept,
    'comment' => $_REQUEST['comment'],
    'publishable' => $publishable,
    'is_public' => $is_public,
    'dept_perms' => $deptPermission,
    'user_perms' => $userPermission,
    'source_path' => $tmp_name[$count],
    'source_is_upload' => true,
    'username' => $user_obj->getUserName(),
    'mime' => $file_mime,
]);

AccessLog::addLogEntry($fileId, 'A', $pdo);
```

Keep everything after it (the `message`/notification/`callPluginMethod('onAfterAdd')` block) as-is. Ensure `$fileId` and `$username` are still in scope after the refactor.

- [ ] **Step 6: Run the existing add-flow integration tests**

Run: `bash -c "php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Integration 2>&1 | tail -20"`
Expected: PASS (no regressions from the refactor).

- [ ] **Step 7: Commit**

```bash
git add application/models/Document.class.php application/controllers/add.php tests/Unit/DocumentTest.php
git commit -m "refactor: extract reusable Document::create() from web add flow"
```

---

### Task 4: Composer dependency + `EmailMessage` DTO + `EmailInbox` adapter

**Files:**
- Modify: `composer.json`
- Create: `application/models/EmailMessage.class.php`
- Create: `application/models/EmailInbox.class.php`
- Test: `tests/Unit/EmailInboxTest.php` (light — mocks the library)

**Interfaces:**
- Consumes: webklex/php-imap.
- Produces:
  - `EmailMessage` DTO with fields `id`(string), `subject`(string), `from`(string), and `attachments`(array of `['name'=>string,'path'=>string,'mime'=>string]`).
  - `EmailInbox::__construct(array $config)`.
  - `EmailInbox::fetchMessages(): EmailMessage[]` — only unread.
  - `EmailInbox::markRead(string $id): void`.
  - `EmailInbox::delete(string $id): void`.

- [ ] **Step 1: Add the Composer dependency**

In `composer.json` `require` block add:
```json
"webklex/php-imap": "^6.0"
```
Run `composer require webklex/php-imap` (or `composer update`) to install. (IMAP is pure-PHP with no `php-imap` extension needed; **POP3 requires PHP's native `imap` extension** — the library uses LegacyProtocol for non-IMAP protocols. Both are supported; document the POP3 requirement.)

- [ ] **Step 2: Write `EmailMessage.class.php`**

```php
<?php

class EmailMessage
{
    public string $id;
    public string $subject;
    public string $from;
    public array $attachments = [];

    public function __construct(string $id, string $subject, string $from)
    {
        $this->id = $id;
        $this->subject = $subject;
        $this->from = $from;
    }
}
```

- [ ] **Step 3: Write `EmailInbox.class.php`**

```php
<?php

require_once __DIR__ . '/EmailMessage.class.php';

class EmailInbox
{
    private $client;
    private string $folder;

    public function __construct(array $config)
    {
        $this->folder = $config['folder'] ?? 'INBOX';
        // webklex/php-imap Client::make
        $this->client = \Webklex\IMAP\Client::make([
            'host' => $config['host'],
            'port' => (int) $config['port'],
            'protocol' => $config['protocol'],   // 'imap' or 'pop3'
            'encryption' => $config['encryption'], // 'none', 'ssl', 'tls'
            'user' => $config['user'],
            'password' => $config['pass'],
        ]);
    }

    public function fetchMessages(): array
    {
        $folderObj = $this->client->getFolder($this->folder);
        $messages = $folderObj->unseen()->get();
        $result = [];
        foreach ($messages as $msg) {
            $em = new EmailMessage($msg->getUid(), (string) $msg->getSubject(), (string) $msg->getFrom()[0]->mail ?? '');
            foreach ($msg->getAttachments() as $att) {
                $name = $att->getName();
                if ($name === null) {
                    continue;
                }
                $path = tempnam(sys_get_temp_dir(), 'odm_att_');
                file_put_contents($path, $att->getContent());
                $em->attachments[] = ['name' => $name, 'path' => $path, 'mime' => $att->getType()];
            }
            $result[] = $em;
        }
        return $result;
    }

    public function markRead(string $id): void
    {
        $folderObj = $this->client->getFolder($this->folder);
        $folderObj->getMessage($id)->setFlag('seen');
    }

    public function delete(string $id): void
    {
        $folderObj = $this->client->getFolder($this->folder);
        $folderObj->getMessage($id)->delete();
    }
}
```

Note: the webklex/php-imap v6 method names are approximate — verify against the installed package's docs (`vendor/webklex/php-imap/README.md`) and adjust `getAttachments()`, `getUid()`, `setFlag('seen')`, and `Client::make()` signatures to match. The DTO/ingest layers do not depend on these specifics, so any API drift is contained here.

- [ ] **Step 4: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Models/EmailMessage.class.php';
require_once __DIR__ . '/../Models/EmailInbox.class.php';

class EmailInboxTest extends TestCase
{
    public function testMessageDtoExposesFields(): void
    {
        $m = new EmailMessage('123', 'subject', 'sender@example.com');
        $m->attachments[] = ['name' => 'a.pdf', 'path' => '/tmp/a', 'mime' => 'application/pdf'];
        $this->assertSame('123', $m->id);
        $this->assertSame('a.pdf', $m->attachments[0]['name']);
    }
}
```

- [ ] **Step 5: Run to verify pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/EmailInboxTest.php 2>&1 | tail -20`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add composer.json application/models/EmailMessage.class.php application/models/EmailInbox.class.php tests/Unit/EmailInboxTest.php
git commit -m "feat: IMAP/POP3 inbox adapter and email message DTO"
```

---

### Task 5: `EmailIngest` core model

**Files:**
- Create: `application/models/EmailIngest.class.php`
- Test: `tests/Unit/EmailIngestTest.php`

**Interfaces:**
- Consumes: `EmailMessage` (Task 4), `Document::create` (Task 3), `MailToken` (Task 2), Mockery PDO.
- Produces:
  - `EmailIngest::__construct(PDO $pdo, array $config, callable $documentCreator)` — `$documentCreator` defaults to a wrapper around `Document::create` (`array $params, string $mime` → int id).
  - `EmailIngest::process(EmailMessage $message): array` — returns `['created'=>count, 'rejected'=>count, 'errors'=>count]`.
  - `EmailIngest::resolveUserByToken(string $token): ?int` — returns user id or null.

- [ ] **Step 1: Write the failing test**

```php
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Models/EmailMessage.class.php';
require_once __DIR__ . '/../Models/EmailIngest.class.php';

class EmailIngestTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private array $createdCalls = [];

    /**
     * Build a mock PDO whose prepare() routes by SQL text:
     * - queries containing "FROM ... user" return the given user row (or null)
     * - any other query (email_audit insert) just executes
     */
    private function mockPdo(?array $userRow): PDO
    {
        $pdo = \Mockery::mock('PDO');
        $pdo->shouldReceive('prepare')->andReturnUsing(function (string $sql) use ($userRow) {
            $stmt = \Mockery::mock('PDOStatement');
            if (strpos($sql, 'FROM') !== false && strpos($sql, 'user') !== false) {
                $stmt->shouldReceive('fetch')->andReturn($userRow);
            }
            $stmt->shouldReceive('execute')->andReturn(true);
            return $stmt;
        });
        return $pdo;
    }

    private function creator(array $params, string $mime): int
    {
        $this->createdCalls[] = $params;
        return 100 + count($this->createdCalls);
    }

    private function makeIngest(array $config, ?array $userRow): EmailIngest
    {
        $this->createdCalls = [];
        return new EmailIngest($this->mockPdo($userRow), $config, [$this, 'creator']);
    }

    public function testResolveUserBySubjectReturnsNullWhenNoMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], null);
        $this->assertNull($ingest->resolveUserBySubject('Q3 [odm-abc123] report'));
    }

    public function testResolveUserBySubjectReturnsUserIdOnMatch(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_'], ['id' => 7]);
        $this->assertSame(7, $ingest->resolveUserBySubject('Q3 [odm-abc123] report'));
    }

    public function testProcessRejectsMissingToken(): void
    {
        $ingest = $this->makeIngest(['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf']], null);
        $msg = new EmailMessage('m1', 'no token here', 'a@b.com');
        $result = $ingest->process($msg);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testProcessCreatesOneDocPerValidAttachment(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m2', 'Report [odm-token]', 'a@b.com');
        $msg->attachments = [
            ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'],
            ['name' => 'b.pdf', 'path' => '/tmp/b.pdf', 'mime' => 'application/pdf'],
        ];
        $result = $ingest->process($msg);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['rejected']);
        $this->assertSame(7, $this->createdCalls[0]['owner_id']);
    }

    public function testProcessRejectsBadMimePerAttachment(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m3', 'Report [odm-token]', 'a@b.com');
        $msg->attachments = [
            ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'],
            ['name' => 'x.exe', 'path' => '/tmp/x.exe', 'mime' => 'application/x-msdownload'],
        ];
        $result = $ingest->process($msg);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['rejected']);
    }

    public function testPublishableIsZeroWhenAuthorizationOn(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'True', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m4', 'Report [odm-token]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
        $ingest->process($msg);
        $this->assertSame('0', $this->createdCalls[0]['publishable']);
    }

    public function testPublishableIsOneWhenAuthorizationOff(): void
    {
        $config = ['db_prefix' => 'odm_', 'authorization' => 'False', 'allowedFileTypes' => ['application/pdf'], 'mail_default_category' => 3, 'mail_default_department' => 2];
        $ingest = $this->makeIngest($config, ['id' => 7]);
        $msg = new EmailMessage('m5', 'Subject [odm-token]', 'a@b.com');
        $msg->attachments = [ ['name' => 'a.pdf', 'path' => '/tmp/a.pdf', 'mime' => 'application/pdf'] ];
        $ingest->process($msg);
        $this->assertSame('1', $this->createdCalls[0]['publishable']);
    }
}
```

- [ ] **Step 2: Run to verify fail**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/EmailIngestTest.php 2>&1 | tail -20`
Expected: FAIL — `EmailIngest` not defined.

- [ ] **Step 3: Write `EmailIngest.class.php`**

```php
<?php

require_once __DIR__ . '/EmailMessage.class.php';

class EmailIngest
{
    private PDO $pdo;
    private array $config;
    private $documentCreator;

    public function __construct(PDO $pdo, array $config, $documentCreator = null)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->documentCreator = $documentCreator ?? function (array $params, string $mime): int {
            return Document::create($this->pdo, $params);
        };
    }

    /**
     * Extract the `odm-xxxx` token from a subject line like "[odm-ab3x7] Q3 invoices".
     */
    private function extractToken(string $subject): ?string
    {
        if (preg_match('/\[(odm-[a-f0-9]+)\]/i', $subject, $m) === 1) {
            return $m[1];
        }
        return null;
    }

    public function resolveUserBySubject(string $subject): ?int
    {
        $token = $this->extractToken($subject);
        if ($token === null) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->config['db_prefix']}user WHERE mail_token = :token");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }

    public function process(EmailMessage $message): array
    {
        $stats = ['created' => 0, 'rejected' => 0, 'errors' => 0];
        $userId = $this->resolveUserBySubject($message->subject);
        $token = $this->extractToken($message->subject) ?? '';

        if ($userId === null) {
            $this->writeAudit($message, null, 'rejected', 'no valid token', null);
            $stats['rejected']++;
            return $stats;
        }

        foreach ($message->attachments as $att) {
            $mime = $att['mime'];
            if (!in_array($mime, $this->config['allowedFileTypes'], true)) {
                $this->writeAudit($message, $userId, 'rejected', 'disallowed file type: ' . $mime, null);
                $stats['rejected']++;
                continue;
            }

            $publishable = ($this->config['authorization'] === 'True') ? '0' : '1';
            $params = [
                'category' => (int) $this->config['mail_default_category'],
                'owner_id' => $userId,
                'realname' => $att['name'],
                'description' => $message->subject,
                'department' => (int) $this->config['mail_default_department'],
                'comment' => 'Imported via email from ' . $message->from,
                'publishable' => $publishable,
                'is_public' => 0,
                'dept_perms' => [],
                'user_perms' => [$userId => 4],
                'username' => '',
                'source_path' => $att['path'],
                'source_is_upload' => false,
                'mime' => $mime,
            ];
            try {
                $docId = call_user_func($this->documentCreator, $params, $mime);
                $this->writeAudit($message, $userId, 'created', '', $docId);
                $stats['created']++;
            } catch (Exception $e) {
                $this->writeAudit($message, $userId, 'error', $e->getMessage(), null);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private function writeAudit(EmailMessage $message, ?int $userId, string $outcome, string $reason, ?int $docId): void
    {
        $tokenHash = null;
        $t = $this->extractToken($message->subject);
        if ($t !== null) {
            $tokenHash = hash('sha256', $t);
        }
        $stmt = $this->pdo->prepare("INSERT INTO {$this->config['db_prefix']}email_audit (message_id, from_address, token_hash, outcome, document_id, reason) VALUES (:mid, :from, :hash, :outcome, :did, :reason)");
        $stmt->execute([
            ':mid' => $message->id,
            ':from' => $message->from,
            ':hash' => $tokenHash,
            ':outcome' => $outcome,
            ':did' => $docId,
            ':reason' => $reason,
        ]);
    }
}
```

Note: keep the code exactly as-is for the contract. In production `username` for the history row is set from the resolved user; the email ingest currently passes `''` because `Document::create` uses `$params['username']` for the `modified_by` log entry. If you want the real username, look it up here and pass it.

- [ ] **Step 4: Run to verify pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Unit/EmailIngestTest.php 2>&1 | tail -20`
Expected: PASS. Make the mock SQL-routing helper in the test robust (return the user row for the user-lookup query, null otherwise).

- [ ] **Step 5: Commit**

```bash
git add application/models/EmailIngest.class.php tests/Unit/EmailIngestTest.php
git commit -m "feat: EmailIngest core (token auth, mime filter, one-doc-per-attachment, audit)"
```

---

### Task 6: Wire `mail:poll` CLI command

**Files:**
- Modify: `application/installer/cli.php`

**Interfaces:**
- Consumes: `ConfigManager`, `DatabaseManager` (already in cli.php), `EmailInbox` (Task 4), `EmailIngest` (Task 5).
- Produces: a `mail:poll` command that polls once and exits.

- [ ] **Step 1: Add a `mail:poll` case to the switch**

In `CliCommand::run()` switch (line 27) add:
```php
case 'mail:poll':
    $this->mailPoll();
    break;
```

- [ ] **Step 2: Add the `mailPoll()` method**

```php
private function mailPoll(): void
{
    require_once __DIR__ . '/../version.php';
    require_once __DIR__ . '/../models/EmailInbox.class.php';
    require_once __DIR__ . '/../models/EmailIngest.class.php';

    $configManager = new ConfigManager();
    if (!$configManager->configExists()) {
        fwrite(STDERR, "Error: No config file found. Run setup-config first.\n");
        exit(1);
    }
    $configManager->loadConfig();

    $dbManager = new DatabaseManager(APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
    $pdo = $dbManager->connect();

    $c = $GLOBALS['CONFIG'];
    if (($c['mail_enabled'] ?? 'False') !== 'True') {
        fwrite(STDERR, "Mail ingest is disabled (mail_enabled is not True).\n");
        return;
    }

    $inbox = new EmailInbox([
        'host' => $c['mail_host'] ?? '',
        'port' => $c['mail_port'] ?? 993,
        'protocol' => $c['mail_protocol'] ?? 'imap',
        'encryption' => $c['mail_encryption'] ?? 'ssl',
        'user' => $c['mail_user'] ?? '',
        'pass' => $c['mail_pass'] ?? '',
        'folder' => $c['mail_folder'] ?? 'INBOX',
    ]);

    $ingest = new EmailIngest($pdo, $c);

    $messages = $inbox->fetchMessages();
    $totals = ['created' => 0, 'rejected' => 0, 'errors' => 0];
    foreach ($messages as $message) {
        $stats = $ingest->process($message);
        foreach (array_keys($totals) as $k) {
            $totals[$k] += $stats[$k];
        }
        $inbox->markRead($message->id);
        if (($c['mail_delete'] ?? 'False') === 'True') {
            $inbox->delete($message->id);
        }
    }

    echo "mail:poll complete — created {$totals['created']}, rejected {$totals['rejected']}, errors {$totals['errors']}\n";
}
```

Add a `mail_delete` setting to the migration/SchemaBuilder (Task 1) as an optional switch for "delete processed messages"; default `False`.

- [ ] **Step 3: Verify CLI loads without fatal errors**

Run: `php application/installer/cli.php mail:poll 2>&1 | tail -10`
Expected: either runs (connects to a real mailbox) or fails cleanly with a connection error — not a PHP parse/fatal from the new command.

- [ ] **Step 4: Commit**

```bash
git add application/installer/cli.php
git commit -m "feat: add mail:poll CLI command"
```

---

### Task 7: Token management UI (profile + admin user table) + i18n

**Files:**
- Modify: `application/controllers/profile.php`
- Modify: `application/controllers/user.php` (modify-user form + save path)
- Modify: `application/controllers/admin_users.php`
- Modify: `application/controllers/admin_crud_ajax.php`
- Modify: `application/views/common/admin_users.tpl`
- Modify: `application/includes/language/*.php` (all 17)

**Interfaces:**
- Consumes: `User::getMailToken()/setMailToken()/rotateMailToken()` (Task 2).
- Produces: i18n keys `email_token`, `email_token_rotate`, `email_token_instruction`, `email_token_rotated`.

- [ ] **Step 1: Add i18n strings to all 17 language files**

Add these keys to every file under `application/includes/language/`:
```php
$lang['email_token'] = 'Mail ingest token';
$lang['email_token_rotate'] = 'Rotate token';
$lang['email_token_instruction'] = 'To submit a document by email, send it to the configured inbox with your token in the subject, e.g. "[YOUR-TOKEN] Subject".';
$lang['email_token_rotated'] = 'Your mail ingest token was rotated. The old token no longer works.';
```
(Use the existing English text; for non-English files follow each file's existing wording style — if the engineer is unsure, copy the English strings; the strings must exist in all files per AGENTS.md.)

- [ ] **Step 2: Show/rotate in the user profile**

`profile.php` renders its own inline HTML via `ob_start()`. Add the token display + rotate button to that block (after the existing "Update profile" button, around line 37):

```php
$token = (new User($_SESSION['uid'], $GLOBALS['pdo']))->getMailToken();
if ($token !== '') {
    echo '<div class="alert alert-info mt-3">';
    echo '<strong>' . htmlspecialchars(msg('email_token')) . ':</strong> <code>' . htmlspecialchars($token) . '</code><br>';
    echo htmlspecialchars(msg('email_token_instruction'));
    echo '<br><a class="btn btn-warning btn-sm" href="user?submit=Rotate+Mail+Token&item=' . $_SESSION['uid'] . '">' . msg('email_token_rotate') . '</a>';
    echo '</div>';
}
```

Handle the rotate action in `user.php`, before its main edit-handling block:

```php
if (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Rotate Mail Token') {
    $uid = (int) $_REQUEST['item'];
    if ($uid !== $_SESSION['uid'] && !(new User($_SESSION['uid'], $GLOBALS['pdo']))->isAdmin()) {
        header('Location: error?ec=4');
        exit;
    }
    $u = new User($uid, $GLOBALS['pdo']);
    $u->rotateMailToken();
    header('Location: profile?last_message=' . urlencode(msg('email_token_rotated')));
    exit;
}
```

- [ ] **Step 3: Show/rotate in the admin user table**

In `admin_crud_ajax.php`, add a route (or extend the existing user edit handler) so an admin can rotate a token:
```php
// POST or GET action 'rotate_mail_token'
if ($_REQUEST['action'] == 'rotate_mail_token') {
    $u = new User((int) $_REQUEST['item'], $GLOBALS['pdo']);
    $newToken = $u->rotateMailToken();
    echo json_encode(['token' => $newToken]);
    exit;
}
```
In `admin_users.tpl`, add a "Rotate token" button/action that calls this endpoint and shows the returned token.

- [ ] **Step 4: Manual smoke test**

Run the app (`php -S 0.0.0.0:8080 -t public`) and confirm:
- Profile page shows the token + rotate button.
- Rotating changes the token and the old one no longer works.
- Admin user table shows/rotates a token.

- [ ] **Step 5: Commit**

```bash
git add application/controllers/profile.php application/controllers/user.php application/controllers/admin_users.php application/controllers/admin_crud_ajax.php application/views/common/admin_users.tpl application/includes/language/*.php
git commit -m "feat: show and rotate mail ingest tokens in profile and admin user table"
```

---

### Task 8: Manual test documentation

**Files:**
- Create: `docs/mail-ingest.md`

**Interfaces:**
- Consumes: completed feature.

- [ ] **Step 1: Write the manual test/ops doc**

```markdown
# Email Ingest (mail-in documents)

Configure in Admin → Settings (the `mail_*` keys). Set `mail_enabled` to `True`,
then run:

    php application/installer/cli.php mail:poll

Every user who wants to email documents needs a token (user profile → "Mail
ingest token"). Send mail to the configured inbox with the token in the
subject:

    [odm-<token>] Invoice Q3.pdf

Each valid attachment becomes a document owned by the token's user. If
`authorization` is `True`, ingested documents go to the reviewer queue.
```
```

- [ ] **Step 2: Commit**

```bash
git add docs/mail-ingest.md
git commit -m "docs: mail ingest verification guide"
```