# Email Ingest (mail-in documents)

> **PHP requirement:** The email ingest feature (via `webklex/php-imap` 6.x,
> which pulls `illuminate/*` and `symfony/http-foundation` 8.x) requires
> **PHP >= 8.4**. This is reflected in `composer.json`.
>
> **POP3 note:** IMAP works without any extension, but POP3 requires PHP's
> native `imap` extension (the library uses `LegacyProtocol` for non-IMAP).

Configure in Admin → Settings (the `mail_*` keys). Set `mail_enabled` to `True`,
then run (via cron or manually):

    php application/installer/cli.php mail:poll

Every user who wants to email documents needs a token (user profile → "Mail
ingest token"). Send mail to the configured **body** with the token — never in
the subject:

    To: documents@example.com
    Subject: Invoice Q3
    Body:
      Please file this.
      [odm-<token>]
      Thanks.

The **subject becomes the document description**; the token (kept only in the
body) is not stored in the document.

## Behavior

- Each valid attachment becomes a document owned by the token's user. If
  `authorization` is `True`, ingested documents go to the reviewer queue.
- Attachments are validated by **content** (magic-byte MIME), never by the
  email's declared Content-Type header.
- Attachments larger than `max_filesize` are rejected, as are messages with
  more than 20 attachments. Each poll processes at most 50 messages.

## Security settings

- `mail_validate_cert` — verify the mail server's TLS certificate (default
  `True`). Leave `True` in production; disabling exposes the connection to
  MITM.
- `mail_audit_retention_days` — how long to keep `email_audit` rows (default
  `90`; `0` = keep forever). Old rows are pruned on each poll.

## Viewing results

Admin → **Email Ingest Log** lists every processed message with outcome
(created / rejected / error) and reason. The audit table only stores a
SHA-256 of the token, never the token itself.

## Troubleshooting

- `Mail poll failed - connection failed` → check `mail_host`/`mail_port`/
  `mail_encryption`, that the host actually serves IMAP, and that the TLS
  certificate matches the host (or enable `mail_validate_cert` carefully).
- An email shows `no valid token` → the token in the body didn't match any
  user's current token (check for typos, or the token was rotated).