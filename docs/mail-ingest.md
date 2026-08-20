# Email Ingest (mail-in documents)

> **PHP requirement:** The email ingest feature (via `webklex/php-imap` 6.x,
> which pulls `illuminate/*` and `symfony/http-foundation` 8.x) requires
> **PHP >= 8.4**. This is reflected in `composer.json`.

Configure in Admin → Settings (the `mail_*` keys). Set `mail_enabled` to `True`,
then run:

    php application/installer/cli.php mail:poll

Every user who wants to email documents needs a token (user profile → "Mail
ingest token"). Send mail to the configured inbox with the token in the
subject:

    [odm-<token>] Invoice Q3.pdf

Each valid attachment becomes a document owned by the token's user. If
`authorization` is `True`, ingested documents go to the reviewer queue.