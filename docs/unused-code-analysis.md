# Unused Code Analysis Report — OpenDocMan v2.0.2

Generated: 2026-07-09
Scope: Static analysis of the entire application codebase (excluding `vendor/`)

---

## DEFINITELY UNUSED — High Confidence (8 items)

These items exist in the codebase but have **zero references** anywhere (no calls, includes, links, or imports).

| # | Item | Location | Evidence | Confidence |
|---|------|----------|----------|------------|
| 1 | `advanceCombineArrays()` | `application/models/classHeaders.php:39` | Zero callers in any PHP file (vendor excluded) | 100% |
| 2 | `combineArrays()` (global function) | `application/models/classHeaders.php:72` | All references target `databaseData::combineArrays()` class method, not this global function | 100% |
| 3 | `crumb::delCrumb()` | `application/controllers/helpers/crumb.php:62` | Method is defined but never invoked anywhere | 100% |
| 4 | `tweeter/content.tpl` | `application/views/tweeter/content.tpl` | No `display_smarty_template('content.tpl')`, no `{include file=...}`, no other reference | 100% |
| 5 | `default/header.tpl` | `application/views/default/header.tpl` | Only reachable if theme = `"default"` which never happens; fallback is `common/` | 100% |
| 6 | `default/footer.tpl` | `application/views/default/footer.tpl` | Same as above — theme `"tweeter"` is hardcoded as default | 100% |
| 7 | `public/js/jquery-diagnostics.js` | `public/js/jquery-diagnostics.js` | Zero `<script src>` references in any `.tpl`, `.php`, or `.html` file | 100% |
| 8 | `public/css/common/jquery.noticeMsg.css` | `public/css/common/jquery.noticeMsg.css` | Zero `<link href>` or CSS import references anywhere | 100% |

---

## INTERNAL-ONLY FUNCTIONS — Candidates for Inlining (5 items)

These functions are **only called from within the same file** (directly or indirectly), never from external code. They could be inlined or removed.

| Function | File | Only Called By | Confidence |
|----------|------|----------------|------------|
| `email_users_obj()` | `functions.php:157` | `email_users_id()` (same file) | 100% |
| `valid_username()` | `functions.php:590` | `callPluginMethod()` (same file) | 100% |
| `cleanInput()` | `functions.php:599` | `sanitizeme()` (same file) | 100% |
| `xss_clean()` | `functions.php:682` | `cleanInput()` and self-recursion only | 100% |
| `is_valid_udf_name()` | `udf_functions.php:891` | `udf_functions_add_udf()` and `udf_functions_delete_udf()` (same file) | 100% |

---

## POSSIBLY UNUSED — Lower Confidence (5 items)

| Item | Location | Evidence | Confidence |
|------|----------|----------|------------|
| `controllers/view.php` | `application/controllers/view.php` | No links or redirects in any template or controller point to `/view`; `view_file.php` is the linked alternative. Reachable only by manual URL entry. | 95% |
| `public/js/additional-methods.js` (unminified) | `public/js/additional-methods.js` | Only the `.min.js` version is loaded by templates | 99% |
| `public/js/tweeter/bootstrap.js` (unminified) | `public/js/tweeter/bootstrap.js` | Only `bootstrap.min.js` is loaded by `tweeter/footer.tpl` | 99% |
| `public/css/tweeter/bootstrap.min.css` | `public/css/tweeter/bootstrap.min.css` | Only the unminified `bootstrap.css` is loaded by `tweeter/header.tpl` | 99% |
| `public/js/DataTables/jquery.dataTables.js` (unminified) | `public/js/DataTables/jquery.dataTables.js` | Application loads `jquery.dataTables.min.js`; unminified version only referenced in sample extension pages | 90% |

---

## BROKEN REFERENCES (4 items)

These are not unused, but the referenced target is missing or wrong.

| Problem | Location | Detail |
|---------|----------|--------|
| Dead link — underscore vs dash | `admin.php:121` links to `/check_exp` | The file is `check-exp.php` (with a dash). This link produces a 404. |
| Missing template file | `AccessLog.class.php:72` tries `display('accesslog.tpl')` | File `templates/accesslog.tpl` does not exist anywhere in the project. |
| Dead code in upgrade script | `upgrade_10.php:40` uses `$$query` instead of `$query` | This ALTER TABLE statement is silently skipped due to a typo (variable variable). |
| Undefined variable in upgrade | `upgrade_1256.php:62` uses undefined `$table_name` | The UDF table rename query will produce invalid SQL. |

---

## FALSE POSITIVES AVOIDED

These items appear potentially unused but are confirmed as actively in use:

- **All 15 model classes** (`application/models/*.class.php`) — each is directly instantiated, used via static methods, or serves as a parent class.
- **All 33 top-level controllers** — all have at least one UI link except `view.php` (noted as possibly unused).
- **All 17 language files** (`application/includes/language/*.php`) — dynamically loaded from DB config; all selectable via settings page.
- **Smarty built-in plugins** — 32/45 are unused in templates, but this is standard (bundled Smarty plugin library). Only `{escape}` modifier is actively used by the application templates.
- **`checkUserPermission()`** — called from `view_file.php`, `edit.php`, `details.php`, `check-out.php`.

---

## Summary

| Category | Count |
|----------|-------|
| Definitely unused files/functions | 8 |
| Internal-only functions (candidates for inlining) | 5 |
| Possibly unused (no UI entry point) | 1 |
| Minified/unminified duplicates | 4 |
| Broken references | 4 |

---

## Methodology

- **Static analysis** via grep/ripgrep across all non-vendor PHP, TPL, JS, and CSS files.
- **Controller routing** traced through `public/index.php` (simple include-based routing).
- **Template loading** traced through `display_smarty_template()` calls and `{include file=...}` directives.
- **Asset references** traced through `<script src>`, `<link href>`, and PHP path construction.
- **Model usage** traced through `new ClassName(`, `ClassName::methodName(`, `instanceof`, and `extends`.
- **Function usage** traced through `function_name(` calls (excluding definitions).