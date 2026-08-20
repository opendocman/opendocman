# Email Document Ingest — Design (Issue #17)

**Date:** 2026-08-20
**Status:** Approved
**Issue:** https://github.com/opendocman/opendocman/issues/17 — "Add new file through e-mail"

## Goal

Allow users to submit documents to OpenDocMan by emailing them to a monitored
POP3/IMAP mailbox. A CLI polling command fetches messages, validates the sender
by a per-user secret token, and creates one document per valid attachment,
honoring the existing authorization/review workflow.

## Decisions

- **Ingest trigger:** CLI/cron polling command (`mail:poll` in `installer/cli.php`).
- **Mailbox access:** a Composer IMAP/POP3 library. IMAP is pure-PHP; **POP3 requires PHP's native `imap` extension** (the library uses LegacyProtocol for non-IMAP). Both protocols supported.
- **Sender authentication:** per-user secret **ingest token** delivered as a
  **subject-line prefix** — e.g. subject `[odm-ab3x7] Q3 invoices`. Every user
  emails the SAME shared mailbox (no per-user aliases or catch-all required).
  The poller extracts the token from the subject and resolves it to a user; no
  matching token = reject. The `From` address is never trusted for attribution,
  so spoofing is still blocked.
- **Token rolling:** admins (and users with rights) can regenerate/rotate a
  token at any time. Rotating hashes a new token and immediately voids the old
  one (previously-captured token values no longer validate).
- **Owner:** the user who owns the matched token.
- **Category/department:** one global default category and department for all
  mail-in documents (admin-configured).
- **Attachments:** each valid attachment becomes its own document. Failures are
  recorded in an audit trail.
- **Authorization:** when `authorization == 'True'`, ingested documents are
  created with `publishable = '0'` and flow into the reviewer queue, exactly
  like web-added documents.

## 1. Architecture & Components

### `EmailInbox` model — `application/models/EmailInbox.class.php`
Thin wrapper over the Composer IMAP/POP3 client. Responsibilities:
- Connect using configured host/port/protocol/encryption/folder.
- Fetch unread messages (expose message id, from, recipient/subject, and
  attachments).
- Post-process actions: mark read and/or delete.

Knows only the mail transport. No business logic.

### `EmailIngest` model — `application/models/EmailIngest.class.php`
Pure business core. For each message:
1. Resolve sender token → user.
2. Filter attachments against `CONFIG['allowedFileTypes']`.
3. Create one document per valid attachment via `Document::create()`.
4. Write an `email_audit` row per attachment outcome.

All dependencies injected (PDO, config array, `Document::create` callback) so
it is unit-testable without a live mailbox or DB.

### `Document::create()` — extracted from `add.php`
The raw document-creation SQL currently inline in `add.php` (data row + log row
+ dept_perms + user_perms + move file + content_index) is extracted into a
reusable `Document::create()` method. Both the web add flow and email ingest
call it, so there is a single source of truth for document creation.

### `mail:poll` subcommand — `application/installer/cli.php`
New command in the existing `CliCommand::run()` switch:
- Loads config + connects PDO (existing bootstrap pattern).
- Instantiates `EmailInbox` + `EmailIngest`.
- Iterates unread messages; per-message try/catch so one failure never aborts
  the whole mailbox.

## 2. Data Model (migration `Version001705` → `ODM_DB_VERSION` 1.7.5)

### `{prefix}user` — add column `mail_token`
Per-user secret ingest token, stored hashed via the existing `PasswordHasher`.

**Token surface (show + rotate):**
- **User profile** — a logged-in user sees their own token (with the instruction
  to place it in the email subject) and can rotate it.
- **Admin user table** — an admin sees/rotates any user's token (e.g. for a
  user who lost it or was compromised).

A **rotate** action generates a new token and immediately voids the previous
one.

### New table `{prefix}email_audit`
One row per processed message-attachment:
- `id` (PK)
- `message_id` — mail message identifier
- `from` — sender address
- `token_hash` — hashed token (or null on rejection)
- `outcome` — `created` | `rejected` | `error`
- `document_id` — created doc id (null if none)
- `reason` — human/audit reason
- `created` — timestamp

### New settings (rows in `{prefix}settings`)
- `mail_enabled` — master switch
- `mail_host`, `mail_port`
- `mail_protocol` — `imap` | `pop3`
- `mail_encryption` — `none` | `ssl` | `tls`
- `mail_user`, `mail_pass`
- `mail_folder` (default `INBOX`)
- `mail_default_category`
- `mail_default_department`

All auto-appear in the admin settings form (existing `settings.tpl` iterates
the settings table); add special-case selects for protocol, encryption,
category, and department.

### Build steps
- `Version001705` migration + SchemaBuilder default statements seed.
- Bump `ODM_DB_VERSION` to `1.7.5` in `application/version.php`.
- Regenerate `database.sql` via `make dump-sql`.

## 3. Data Flow & Security

1. Poller connects and fetches unread messages.
2. Per message:
   a. **Token auth:** extract token from the **subject-line prefix** (e.g.
      `[odm-abc123]`), look up user by token. No match → audit
      `rejected (no valid token)`, skip. Rotating a user's token voids the old
      value, so stale tokens are rejected.
   b. **File-type check:** each attachment MIME checked against
      `CONFIG['allowedFileTypes']`. Invalid → rejected.
   c. **Create one doc per valid attachment:**
      - `publishable = '0'` when `authorization == 'True'` (flows into the
        reviewer queue / `toBePublished`), else `'1'`.
      - owner = token's user; category/department = mail defaults.
   d. **Write audit row** per attachment outcome.
   e. **Mark processed**; delete if configured.
3. One bad message never aborts the mailbox — per-message try/catch.

## 4. Error Handling

- Missing/invalid token, disallowed file type, or document-create failure is
  recorded in `email_audit` with a reason; the message is still marked read so
  it is not reprocessed every poll.
- Optional sender notification on rejection, gated by a config toggle
  (outbound notification via the existing `Email` model).
- Poller failures (cannot connect) are logged and reported in CLI output, not
  fatal to the process.

## 5. Testing

- **Unit:** `EmailIngestTest` — token validation (subject prefix), file-type
  filter, one-doc-per-attachment, audit rows, publishable status under
  authorization on/off.
- **Unit:** `TokenRotationTest` — rotating a token voids the old value and
  generates a valid new one.
- **Unit:** `Migration001705Test` — verifies settings + schema changes.
- **Unit:** `Document::create` extraction regression tests (web flow unchanged).
- **Integration:** email-ingest workflow test with Mockery PDO (mirrors
  `tests/Integration/IncomingRevisionWorkflowTest.php`).
- Manual test procedure documented (no live mail server in CI).

## Out of Scope

- Outbound email improvements (already exists via `mail()` / `Email` model).
- SPF/DKIM verification of the sending domain (token covers spoofing).
- Per-user category/department overrides; subject-based routing markers.
- A long-running daemon process (CLI/cron chosen instead).