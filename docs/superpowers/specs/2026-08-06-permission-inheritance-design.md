# Permission Inheritance

**Date:** 2026-08-06
**Issue:** https://github.com/opendocman/opendocman/issues/15

## Problem

Permissions are entirely per-document (`user_perms` and `dept_perms` tables), with no category-level defaults or inheritance. When a document is created, the admin must explicitly set permissions for every department and user — even if the document's category has a consistent permission pattern. There is no fallback mechanism: if no document-level permission row exists, the user gets no access.

## Scope

1. **Category permission templates** — categories can have default permission templates stored in a new `category_perms` table
2. **Template population** — when creating/editing a document, selecting a category pre-fills the permissions form from the template (user can override before saving)
3. **Live inheritance** — at permission-check time, if no document-level permission exists, fall back to the category's template
4. **Permission editor redesign** — replace the current accordion of radio buttons with a dual-mode component (tabbed additive edit + full matrix overview)
5. **Label change** — "None" renamed to "Unset" across all language files

## Design

### 1. Database: New `category_perms` Table

```sql
CREATE TABLE `odm_category_perms` (
    cat_id int(11) unsigned NOT NULL,
    dept_id int(11) unsigned default NULL,
    user_id int(11) unsigned default NULL,
    rights tinyint(4) NOT NULL default '0',
    KEY cat_perms_idx (cat_id, dept_id, user_id),
    KEY cat_id (cat_id),
    KEY dept_id (dept_id),
    KEY user_id (user_id)
) ENGINE = MYISAM;
```

- `cat_id` references `odm_category.id` (CASCADE on delete)
- `dept_id` and `user_id` are mutually exclusive (one set, the other NULL) — same pattern as existing per-file `user_perms` and `dept_perms`
- `rights` uses the same values: -1=Forbidden, 0=Unset, 1=View, 2=Read, 3=Write, 4=Admin
- "Unset" (0) rows are never stored — only rows with non-zero or Forbidden values are inserted
- No rows for a category = no template = no inheritance, no pre-fill

**Migration:** `Version001700.php`, `ODM_DB_VERSION` bumped to `1.7.0`

### 2. Permission Editor Component (Shared)

A single reusable component used in two contexts:
- **Category template editor** (on add/edit category admin pages)
- **Document-level permissions** (on add/edit file pages)

The component has two view modes toggled by the user:

#### Edit Mode: Tabbed Additive

```
[ View ] [ Read ] [ Write ] [ Admin ] [ Forbidden ]   [Overview]

View tab:
  [type to search departments/users...]
  ☑ Marketing Dept
  ☑ Engineering Dept
  ☑ John Smith
  [ + Add from dropdown ]
```

- Five tabs, one per permission level (Unset has no tab — it's the implicit default)
- Each tab lists only the departments/users assigned that level
- "+ Add" opens a searchable dropdown of unassigned departments/users
- A department/user can appear on multiple tabs (e.g., View + Read)
- Unset = not on any tab = no row stored in the DB

#### Overview Mode: Full Matrix

```
                 View   Read   Write  Admin  Forbidden
Marketing Dept    ✔      ✔      ✔      ○      ○
Engineering Dept  ✔      ○      ○      ○      ✔
John Smith        ✔      ✔      ✔      ✔      ○
...
```

- Green ✔ = permission set, gray ○ = not set
- Read-only snapshot — click a ✔/○ to jump to that tab in edit mode
- Departments and users shown in separate sections/tables

### 3. Template Population on Add/Edit Document

When a category is selected (or changed) on the add/edit file form:

1. **AJAX call** to `category.php?submit=get_perms_json&cat_id=X`
2. Returns JSON: `{dept_perms: {dept_id: rights, ...}, user_perms: {user_id: rights, ...}}`
3. JavaScript updates the permission editor component to match the template values
4. User can override any selection before saving
5. On form submit, the submitted values are saved to `user_perms`/`dept_perms` (document-level) — the category template is never written to document tables

### 4. Live Inheritance in `getAuthority()`

**Current flow:**
```
admin/root → reviewer → owner-of-locked → user_perms → dept_perms → 0 (none)
```

**New flow:**
```
admin/root → reviewer → owner-of-locked → user_perms → dept_perms → category_user_perms → category_dept_perms → 0 (none)
```

- `category_user_perms`: queries `odm_category_perms` where `cat_id = document.category` and `user_id = current user`
- `category_dept_perms`: queries `odm_category_perms` where `cat_id = document.category` and `dept_id = user's department`
- Only falls back to category if both `user_perms` and `dept_perms` have **no row** for the user/department
- If a row exists in `user_perms` with rights=0 ("Unset"), it's treated as an explicit "no access" — no category fallback for that user
- Category template is never written to document tables — it's a live lookup only

### 5. Category Change Behavior

When an existing document's category is changed on the edit form:
1. AJAX fetches the new category's template
2. Permission editor re-populates with the template values (overwriting any previously-set document-level permissions in the form)
3. User can override before saving
4. On save, the submitted values are written to `user_perms`/`dept_perms`

### 6. Label Change: "None" → "Unset"

All 17 language files updated:

| Language | `addpage_none` | `editpage_none` |
|----------|---------------|----------------|
| English | Unset | Unset |
| Arabic | غير محدد | غير محدد |
| Bangla | নির্ধারিত নয় | নির্ধারিত নয় |
| Chinese | 未设置 | 未设置 |
| Croatian | Nepostavljeno | Nepostavljeno |
| Czech | Nenastaveno | Nenastaveno |
| Danish | Ikke indstillet | Ikke indstillet |
| Dutch | Niet ingesteld | Niet ingesteld |
| French | Non défini | Non défini |
| German | Nicht gesetzt | Nicht gesetzt |
| Italian | Non impostato | Non impostato |
| Portuguese | Não definido | Não definido |
| Romanian | Nesetat | Nesetat |
| Spanish | No establecido | No establecido |
| Swedish | Ej inställd | Ej inställd |
| Tamil | அமைக்கப்படவில்லை | அமைக்கப்படவில்லை |
| Turkish | Ayarlanmamış | Ayarlanmamış |

## Files Changed

| File | Change |
|------|--------|
| `application/installer/SchemaBuilder.php` | Add `odm_category_perms` table definition |
| `application/installer/migrations/Version001700.php` | New migration to create the table |
| `application/version.php` | Bump `ODM_DB_VERSION` to `1.7.0` |
| `application/models/UserPermission.class.php` | Update `getAuthority()` with category fallback |
| `application/controllers/category.php` | Add `get_perms_json` AJAX endpoint; add template editor to add/update category forms |
| `application/controllers/add.php` | Wire category-change AJAX to permission editor |
| `application/controllers/edit.php` | Wire category-change AJAX to permission editor |
| `application/views/common/_filePermissions.tpl` | Redesign as dual-mode component (tabbed edit + matrix overview) |
| `application/views/common/add.tpl` | Include updated permission component |
| `application/views/common/edit.tpl` | Include updated permission component |
| `application/views/admin/category_add.tpl` | Add permission template editor section |
| `application/views/admin/category_update.tpl` | Add permission template editor section |
| `public/js/permissions-editor.js` | New JS file for the dual-mode permission component |
| `application/includes/language/*.php` | "None" → "Unset" in all 17 files |

## Edge Cases

| Scenario | Behavior |
|----------|----------|
| Category has no template | No inheritance, no pre-fill — existing behavior unchanged |
| Document has explicit perms for a user, category has different perms for same user | Document-level wins (it's not "Unset") |
| Category deleted | CASCADE removes `category_perms` rows; existing documents keep their perms |
| Document's category changed to one without a template | Permission form clears to all-Unset; user must set perms before saving |
| Category template has Forbidden for a department, document has no perms for that department | Inheritance kicks in — department gets Forbidden |
| 50+ departments/users | Edit mode scales well (only shows assigned); overview mode paginates by scrolling |

## Testing

### Unit Tests (new)

| Test | Target | What it covers |
|------|--------|----------------|
| `tests/Unit/CategoryPermsTest.php` | `Category_Perms` model | Loading perms by cat_id, getPermission() for specific user/dept, returns null when no row exists |
| `tests/Unit/UserPermissionInheritanceTest.php` | `UserPermission::getAuthority()` | Fallback to category perms when no doc-level perms exist; doc-level wins when present; category Forbidden inherited correctly |
| `tests/Unit/CategoryTemplateControllerTest.php` | `category.php` AJAX endpoints | `get_perms_json` returns correct JSON; template CRUD via add/update |

### Existing Tests to Update

| Test | Change |
|------|--------|
| `tests/Unit/UserPermissionOrchestratorTest.php` | Extend mocked data to verify category fallback path in `getAuthority()` |
| `tests/Unit/UserPermsTest.php` | Ensure `getPermission()` still returns correct values with new table existing |
| `tests/Unit/DeptPermsTest.php` | No change needed (dept perms are unaffected at table level) |

### Integration Tests (new)

| Test | What it covers |
|------|----------------|
| `tests/Integration/CategoryPermissionFlowTest.php` | Full flow: create category with template → add document in that category → verify perms inherited → change category → verify re-inherited |

### E2E Test Update

The existing Playwright smoke test (`tests/smoke-uat.spec.ts`) should be extended to:
1. Log in as admin, navigate to category admin, set a permission template
2. Add a file in that category, verify permissions pre-fill from template
3. Verify the "Unset" label appears (not "None")

## Not in Scope

- Category hierarchy (parent_id) — deferred to future work
- Migration of existing `default_rights` column on `odm_data` — left as-is for backward compat
- Batch permission editor (editing perms for multiple docs at once)
- Permission auditing or reporting