# Pending-Review Feedback Message — Design

**Date:** 2026-08-17
**Issue:** https://github.com/opendocman/opendocman/issues/52

## Goal

When a user uploads a new document or checks a document in, present them with a
message about the review process **if** the site is set up to use reviews
(i.e. the `authorization` setting is `'True'`).

## Background

- Reviews are gated by the `authorization` setting. When `'True'`, newly added
  (`add.php:215-219`) and checked-in (`check-in.php:150-154`) documents are
  stored with `publishable = '0'` (pending review).
- Success feedback is delivered via a single `last_message` redirect parameter,
  rendered by `draw_header()` as a dismissible Bootstrap `alert-info` in
  `header.tpl:44-51`.
  - Add flow: `add.php:427,469` → redirect to `details?id=...&last_message=...`
  - Check-in flow: `check-in.php:316-317` → redirect to `out?last_message=...`
- No user-facing notice currently tells the uploader/check-in user the document
  is pending review.

## Approach

**Augment the existing success message** (approved): when `authorization == 'True'`,
append the new `message_document_pending_review` string to the existing success
message in both the add and check-in controllers.

### Changes

1. **`application/controllers/add.php`** — after line 427, when
   `$GLOBALS['CONFIG']['authorization'] == 'True'`, set the message to
   `msg('message_document_added') . ' ' . msg('message_document_pending_review')`.

2. **`application/controllers/check-in.php`** — after line 316, when
   `$GLOBALS['CONFIG']['authorization'] == 'True'`, set the message to
   `msg('message_document_checked_in') . ' ' . msg('message_document_pending_review')`.

3. **Language strings** — add `$lang['message_document_pending_review']` to all
   **17** language files under `application/includes/language/`:
   arabic, bangla, chinese, croatian, czech, danish, dutch, english, french,
   german, italian, portuguese, romanian, spanish, swedish, tamil, turkish.
   Each string must be translated into that language.
   - English value: `'This document is pending review by an administrator before it is published.'`
   - Inserted alongside the neighboring `message_document_*` entries
     (e.g. after `message_document_checked_in`).

## Non-goals

- No schema changes, no migration.
- No change to the review/authorization workflow itself.
- No new message slots or multi-message support.

## Verification

- Manual: with `authorization = 'True'`, add a document → details page shows the
  combined message; check a document in → out page shows the combined message.
- Manual: with `authorization = 'False'`, messages are unchanged.
- All 17 language files contain the new string (grep).
