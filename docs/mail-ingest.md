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