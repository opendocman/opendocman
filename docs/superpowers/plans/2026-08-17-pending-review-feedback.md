# Pending-Review Feedback Message Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show users a pending-review notice appended to the success message when adding or checking in a document, when the site has reviews enabled (`authorization = 'True'`).

**Architecture:** Two controllers (`add.php`, `check-in.php`) already gate document publishability on `$GLOBALS['CONFIG']['authorization']`. When it's `'True'`, append the new translated string `message_document_pending_review` to the existing success message passed via the `last_message` redirect parameter. No schema or workflow changes.

**Tech Stack:** PHP 7.4+ (app), Smarty templates, 17 flat PHP language files.

## Global Constraints

- All new language strings MUST be added to all 17 language files under `application/includes/language/` (arabic, bangla, chinese, croatian, czech, danish, dutch, english, french, german, italian, portuguese, romanian, spanish, swedish, tamil, turkish). English is the reference; other languages use best-effort translations.
- English value of the new string: `'This document is pending review by an administrator before it is published.'`
- New string key: `message_document_pending_review`.
- Preserve the `$lang[...]` alphabetical ordering (the key sorts after `message_document_checked_in` and before `message_document_checked_out_to_you`).
- No schema changes, no migration, no version bump.

---
### Task 1: Add `message_document_pending_review` to all 17 language files

**Files:**
- Modify: `application/includes/language/english.php`
- Modify: `application/includes/language/arabic.php`
- Modify: `application/includes/language/bangla.php`
- Modify: `application/includes/language/chinese.php`
- Modify: `application/includes/language/croatian.php`
- Modify: `application/includes/language/czech.php`
- Modify: `application/includes/language/danish.php`
- Modify: `application/includes/language/dutch.php`
- Modify: `application/includes/language/french.php`
- Modify: `application/includes/language/german.php`
- Modify: `application/includes/language/italian.php`
- Modify: `application/includes/language/portuguese.php`
- Modify: `application/includes/language/romanian.php`
- Modify: `application/includes/language/spanish.php`
- Modify: `application/includes/language/swedish.php`
- Modify: `application/includes/language/tamil.php`
- Modify: `application/includes/language/turkish.php`

**Interfaces:**
- Produces: `msg('message_document_pending_review')` resolves in every language. Task 2 and Task 3 depend on this.

- [ ] **Step 1: Add the English string**

Insert after the `message_document_checked_in` line in `english.php`:

```php
$lang['message_document_pending_review'] = 'This document is pending review by an administrator before it is published.';
```

- [ ] **Step 2: Add the string to the 16 other language files**

Insert after the `message_document_checked_in` line in each file, using these translations:

| File | Value |
|------|-------|
| arabic.php | `'هذه الوثيقة قيد المراجعة من قبل مسؤول قبل نشرها.'` |
| bangla.php | `'এই নথিটি প্রকাশের আগে একজন প্রশাসকের পর্যালোচনার অপেক্ষায় রয়েছে।'` |
| chinese.php | `'此文档在发布之前需要管理员审核。'` |
| croatian.php | `'Ovaj dokument čeka pregled administratora prije nego što bude objavljen.'` |
| czech.php | `'Tento dokument čeká na posouzení správcem, než bude publikován.'` |
| danish.php | `'Dette dokument afventer gennemgang af en administrator, før det offentliggøres.'` |
| dutch.php | `'Dit document wacht op beoordeling door een beheerder voordat het wordt gepubliceerd.'` |
| french.php | `'Ce document est en attente de révision par un administrateur avant d\'être publié.'` |
| german.php | `'Dieses Dokument wird vor der Veröffentlichung von einem Administrator geprüft.'` |
| italian.php | `'Questo documento è in attesa di revisione da parte di un amministratore prima della pubblicazione.'` |
| portuguese.php | `'Este documento está aguardando revisão por um administrador antes de ser publicado.'` |
| romanian.php | `'Acest document este în așteptarea revizuirii de către un administrator înainte de a fi publicat.'` |
| spanish.php | `'Este documento está pendiente de revisión por un administrador antes de publicarse.'` |
| swedish.php | `'Detta dokument väntar på granskning av en administratör innan det publiceras.'` |
| tamil.php | `'இந்த ஆவணம் வெளியிடப்படுவதற்கு முன் நிர்வாகியின் மதிப்பாய்விற்காக காத்திருக்கிறது.'` |
| turkish.php | `'Bu belge yayınlanmadan önce bir yönetici tarafından incelenmeyi bekliyor.'` |

- [ ] **Step 3: Verify syntax and presence**

Run:
```bash
for f in application/includes/language/*.php; do php -l "$f" || exit 1; done
grep -rn "message_document_pending_review" application/includes/language/ | wc -l
```
Expected: no lint errors; count = 17.

- [ ] **Step 4: Commit**

```bash
git add application/includes/language/
git commit -m "i18n: add message_document_pending_review in all languages"
```

---
### Task 2: Append review notice on document add

**Files:**
- Modify: `application/controllers/add.php:427`

**Interfaces:**
- Consumes: `msg('message_document_pending_review')` (from Task 1); `$GLOBALS['CONFIG']['authorization']` (already loaded in `odm-init.php`).
- Produces: When `authorization == 'True'`, `$message` becomes `'Document successfully added' . ' ' . 'This document is pending review...'`, still urlencoded into the redirect `last_message`.

- [ ] **Step 1: Modify the message construction**

In `add.php`, replace line 427:

```php
        // back to main page
        $message = urlencode(msg('message_document_added'));
```

with:

```php
        // back to main page
        $message = urlencode(msg('message_document_added'));

        if ($GLOBALS['CONFIG']['authorization'] == 'True') {
            $message = urlencode(msg('message_document_added') . ' ' . msg('message_document_pending_review'));
        }
```

- [ ] **Step 2: Verify syntax**

Run: `php -l application/controllers/add.php`
Expected: no syntax errors.

- [ ] **Step 3: Manual verification**

With `authorization = 'True'` in settings, log in and upload a document. Confirm the details page (`details?id=...`) shows the alert: "Document successfully added This document is pending review by an administrator before it is published."

- [ ] **Step 4: Commit**

```bash
git add application/controllers/add.php
git commit -m "feat: notify user document is pending review after add"
```

---
### Task 3: Append review notice on document check-in

**Files:**
- Modify: `application/controllers/check-in.php:316`

**Interfaces:**
- Consumes: `msg('message_document_pending_review')` (from Task 1); `$GLOBALS['CONFIG']['authorization']`.
- Produces: When `authorization == 'True'`, `$last_message` becomes `'Document successfully checked in' . ' ' . 'This document is pending review...'`.

- [ ] **Step 1: Modify the message construction**

In `check-in.php`, replace lines 315-317:

```php
        // clean up and back to main page
        $last_message = msg('message_document_checked_in');
        header('Location: out?last_message=' . urlencode($last_message));
```

with:

```php
        // clean up and back to main page
        $last_message = msg('message_document_checked_in');

        if ($GLOBALS['CONFIG']['authorization'] == 'True') {
            $last_message = msg('message_document_checked_in') . ' ' . msg('message_document_pending_review');
        }

        header('Location: out?last_message=' . urlencode($last_message));
```

- [ ] **Step 2: Verify syntax**

Run: `php -l application/controllers/check-in.php`
Expected: no syntax errors.

- [ ] **Step 3: Manual verification**

With `authorization = 'True'`, check a document back in. Confirm the out page shows the alert: "Document successfully checked in This document is pending review by an administrator before it is published."

- [ ] **Step 4: Commit**

```bash
git add application/controllers/check-in.php
git commit -m "feat: notify user document is pending review after check-in"
```
