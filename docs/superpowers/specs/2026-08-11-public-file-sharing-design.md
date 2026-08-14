# Public File Sharing Feature

## Overview

Add a public-facing page that displays files marked as "shared/public" without requiring authentication. Files only appear after they have been reviewed and approved through the existing authorization workflow.

## Terminology

Use "public" throughout the UI and code. The existing `$lang['anonymous_*']` translation keys are kept for backward compatibility; new `$lang['public_*']` keys are added.

## Feature Flag

- New admin setting: `public_sharing` (bool, default `False`)
- When disabled: public page returns a "feature disabled" message, login link is hidden
- When enabled: full public page functionality

## Database Changes

### New column on `data` table

```sql
ALTER TABLE `{prefix}data` ADD COLUMN `is_public` tinyint(1) DEFAULT 0 AFTER `publishable`;
```

- `is_public = 1` means the file author/editor has opted to share it publicly
- The existing `publishable` column continues to control review/approval status

### New setting

```sql
INSERT INTO `{prefix}settings` VALUES(NULL, 'public_sharing', 'False',
  '(True/False) Enable public file sharing page. When enabled, files marked as public and approved will be visible without authentication.', 'bool');
```

## SchemaBuilder & Migration

- Update `SchemaBuilder.php`:
  - Add `is_public` column to the `data` CREATE TABLE statement
  - Add `public_sharing` to default settings
- Create new migration `Version*.php` for existing installations
- Bump `ODM_DB_VERSION` in `application/version.php`
- Run `make dump-sql` to regenerate `database.sql`

## Language Strings

Add the following to all 17 language files (`application/includes/language/*.php`):

```php
$lang['public_link'] = 'Click here to view public files';
$lang['public_page_title'] = 'Public Files: List All';
$lang['public_page_h1'] = 'Public Files';
$lang['public'] = 'Public';
$lang['label_public'] = 'Public file';
$lang['message_public_view'] = 'You are viewing public files';
$lang['public_download'] = 'Download';
$lang['message_public_disabled'] = 'Public file sharing is disabled';
```

English translations are authoritative; for other languages, use existing `anonymous_*` translations as guidance.

## Controller: `application/controllers/public.php`

Two modes controlled by `$_GET['submit']`:

### List mode (default)
- No session/auth check
- Check `public_sharing` setting; if disabled, show message and exit
- Query: `SELECT ... FROM {prefix}data WHERE is_public = 1 AND publishable = 1`
- Order by `created DESC`
- Assign results to Smarty and render `public.tpl`

### Download mode (`?submit=download&id=N`)
- Verify file exists, `is_public = 1`, and `publishable = 1`
- Serve the file using the existing `getFilePath()` helper
- Log to `access_log` with action `'U'` (public download)
- Set appropriate Content-Type and Content-Disposition headers

## Templates

### New: `application/views/bootstrap5/public.tpl`
- Full Bootstrap 5 HTML page (mirrors login.tpl structure)
- Simple nav header with logo and "Public Files" heading
- Table listing: Name, Description, Category, Date, Download button
- Each row has a download link: `public?submit=download&id={file_id}`
- If no public files exist, show "No public files available" message

### Modified: `application/views/bootstrap5/login.tpl`
- Add public page link below existing signup/forgot-password links
- Only shown when `public_sharing = True`
- Gated by Smarty: `{if $public_sharing eq 'True'}`
- Link text: `{$g_lang_public_link}`, URL: `public`

### Modified: `application/views/common/add.tpl`
- Add checkbox after category select, gated by `public_sharing`:
  ```html
  {if $public_sharing eq 'True'}
  <div class="mb-3 form-check">
    <input type="checkbox" name="is_public" id="is_public" value="1" class="form-check-input">
    <label class="form-check-label" for="is_public">{$g_lang_label_public}</label>
  </div>
  {/if}
  ```
- Controller must pass `$public_sharing` to template

### Modified: `application/views/common/edit.tpl`
- Same checkbox, gated by `public_sharing`, pre-checked if `is_public = 1`
- Controller passes `$is_public` value and `$public_sharing` to template

## FileData Model: `application/models/FileData.class.php`

Add `is_public` to the class properties, `loadData()`, and `updateData()`:

- Property: `public $is_public;`
- In `loadData()`: add `is_public` to SELECT and `$this->is_public = $row['is_public'];`
- In `updateData()`: add `is_public = :is_public` to SET, bind `:is_public` to `$this->is_public`
- New getter: `getIsPublic()` returns `$this->is_public`
- New setter: `setIsPublic($val)` sets `$this->is_public = $val`

## Controller Changes: `add.php`

- In the POST handler, add `is_public` to the INSERT query:
  ```php
  $is_public = isset($_POST['is_public']) ? 1 : 0;
  ```
  Include `is_public` in the column/value list.

## Controller Changes: `edit.php`

- On form display: pass `$filedata->getIsPublic()` to template as `$is_public`
- On form submit: call `$filedata->setIsPublic(isset($_POST['is_public']) ? 1 : 0)` before `$filedata->updateData()`

## Access Log

Add action `'U'` to the `action` enum in the `access_log` table:
```sql
ALTER TABLE `{prefix}access_log` MODIFY COLUMN `action` enum('A','B','C','V','D','M','X','I','O','Y','R','U') NOT NULL;
```

This is included in the same migration.

## Security

- Public controller does NOT reveal internal file paths
- Downloads go through the controller (not direct file access)
- Only files with `is_public = 1 AND publishable = 1` are ever served
- The `public_sharing` feature flag provides a kill switch

## Implementation Order

1. Update `SchemaBuilder.php` (column + setting)
2. Create migration `Version*.php`
3. Bump `ODM_DB_VERSION`, run `make dump-sql`
4. Add `public_*` language strings to all 17 language files
5. Create `application/controllers/public.php`
6. Create `application/views/bootstrap5/public.tpl`
7. Modify `add.php` controller and `add.tpl` for the checkbox
8. Modify `edit.php` controller and `edit.tpl` for the checkbox
9. Modify `login.tpl` for the public link
10. Write tests (E2E + PHPUnit)
11. Run verification: `make test`, `npm run test:e2e`