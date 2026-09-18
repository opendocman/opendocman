# Restore UDF Management List with Delete — Design

Date: 2026-09-06

## Problem

Admin cannot delete a User Defined Field (UDF) they created. The Admin sidebar
UDF link points at `udf?submit=add`, so the only reachable UDF admin UI is the
Add form. There is no way to reach the list of existing fields or the delete
flow.

The delete backend is fully implemented and functional but orphaned:

- `udf_functions_delete_udf()` performs the DB teardown (delete `odm_udf` row,
  `ALTER TABLE ... DROP COLUMN`, `DROP TABLE` for the helper table, handling the
  type-4 primary/secondary pair).
- `udf.php` routes exist: `submit=deletepick` (pick a field), `submit=delete`
  (confirm page), `deleteudf` (perform delete).
- The legacy entry point, `udf_admin_menu()`, which used to link to the delete
  flow, is dead code with zero call sites (it would also fatal-error on
  `$_REQUEST['state']` when called without `state`).

## Goal

- Give admins a management page listing existing UDF fields with a per-row
  Delete action that reuses the existing confirm + delete flow.
- Remove the dead legacy `udf_admin_menu()` / `udf_admin_header()` code that
  superseded the modern sidebar and caused this UI gap.

## Approach: simple list page + per-row delete, reusing legacy flow

### New route: `udf?submit=manage`

In `application/controllers/udf.php`, add a branch that:

1. SELECTs `id, table_name, display_name, field_type` from
   `{$db_prefix}udf` ORDER BY `id`.
2. Assigns the result to Smarty (`udfs`).
3. Renders the new `udf/manage.tpl` inside the `_admin_content.tpl` shell
   (same pattern as the existing add/delete/edit UDF branches).

### New template: `application/views/common/udf/manage.tpl`

- Card with an "Add New UDF" button linking to `udf?submit=add`.
- A table of existing fields: display name, field type (shown as the numeric
  value, matching how the add/edit templates display it — no new translation
  strings are introduced), table name.
- Each row has a **Delete button inside a tiny POST form**:

```html
<form action="udf" method="POST">
    {$csrf_token_field}
    <input type="hidden" name="submit" value="delete">
    <input type="hidden" name="item" value="{$item.table_name|escape:'html'}">
    <button type="submit" class="btn btn-danger btn-sm">{$g_lang_label_delete}</button>
</form>
```

POST is required: the existing confirm route (`udf?submit=delete&item=...`)
validates the CSRF token from `$_POST` via
`$GLOBALS['csrf']->validateToken($_POST)`; a plain GET link would fail with a
`FormPostException` (missing index/token pair).

The POST lands on the existing confirm page (`udf/delete_form.tpl`), which
shows the field name and a Yes/No confirmation, then the existing `deleteudf`
backend performs the teardown.

### Sidebar retarget

In `application/views/common/_admin_sidebar.tpl:17`, change:

```html
<a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=add">
```

to:

```html
<a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=manage">
```

### Remove dead code

In `application/controllers/helpers/udf_functions.php`, delete:

- `udf_admin_header()` (lines ~483-486)
- `udf_admin_menu()` (lines ~488-504)

Both confirmed to have zero call sites across the codebase.

## Files

- Modify: `application/controllers/udf.php`
- Create: `application/views/common/udf/manage.tpl`
- Modify: `application/views/common/_admin_sidebar.tpl`
- Modify: `application/controllers/helpers/udf_functions.php` (remove dead funcs)

## Error handling / edge cases

- Empty UDF list: manage page renders an empty table with a "no fields"
  message; the Add button is still available.
- Field type is displayed as the numeric value (1/2/3/4), consistent with the
  existing add/edit UDF templates.
- The `type`-aware teardown in `udf_functions_delete_udf()` already handles
  type-4 (primary/secondary) — unchanged.

## Out of scope

- No changes to the delete backend, confirm flow, add form, or edit forms.
- No DB schema changes.
- The legacy `udf_admin_header()`/`udf_admin_menu()` removal is the only
  cleanup; no other refactoring of `udf_functions.php`.

## Testing

- PHPUnit: add a unit test for the manage-route list query and the per-row
  delete form rendering if feasible with the existing test infra; at minimum
  verify existing `UdfFunctionsTest` still passes after the dead-code removal.
- Manual browser check: log in as admin → Admin sidebar → UDFs → list shows the
  existing `asda` field → Delete → confirm → field gone from DB and page.