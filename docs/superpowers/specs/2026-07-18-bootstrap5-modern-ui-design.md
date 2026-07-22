# Bootstrap 5 Modern UI Theme for OpenDocMan

**Date:** 2026-07-18
**Issue:** [#381](https://github.com/opendocman/opendocman/issues/381)
**Theme name:** `bootstrap5`

## Goal

Create a new "bootstrap5" theme for OpenDocMan using the existing directory-based theme system. All pages (login, dashboard, admin, user management, settings, etc.) get a modern Bootstrap 5 look. jQuery is removed entirely in favor of vanilla JS and Bootstrap 5's native JavaScript components. jQuery DataTables is replaced with Tabulator (MIT license).

## Architecture

### Theme vs Shared Templates — Standard Pattern

The theme provides only the **chrome** (wrapper) and **CSS**. Page content templates live in `views/common/` and are shared across all themes. This is how Wordpress, Drupal, and most mature theming systems work.

**Theme directory (`views/bootstrap5/`):**
- `header.tpl` — BS5 navbar, flash messages, breadcrumbs
- `footer.tpl` — Closing tags, powered-by footer
- `head_include.tpl` — CDN links (BS5, Tabulator), local assets
- `login.tpl` — Centered card login form (overrides `common/`)

Only `login.tpl` is overridden because its HTML structure differs significantly from the current `common/login.tpl`. For all other pages, the theme provides CSS, and the `common/` templates are updated to use modern semantic HTML.

### Asset Structure

```
public/
  css/bootstrap5/
    style.css              # BS5 overrides and theme-specific styles
  js/bootstrap5/
    app.js                 # Vanilla JS app logic (DOM helpers, AJAX, event handlers)
    tabulator-config.js    # Tabulator initialization and column definitions
```

### Library Replacements

| Old Library | New Library | Reason |
|---|---|---|
| jQuery 1.x | Vanilla JS | BS5 doesn't need it; simpler DOM |
| jQuery DataTables | Tabulator | MIT license, vanilla JS, feature-rich |
| jQuery Validate | BS5 native validation | HTML5-based, no JS dependency |
| jQuery MultiSelect | Tom Select | MIT license, vanilla JS, BS5-styled |

The `app.js` file will contain:
- `document.addEventListener('DOMContentLoaded', ...)` for all init
- `fetch()` for AJAX calls
- `querySelector` / `querySelectorAll` for DOM selection
- `classList` for DOM manipulation

The `tabulator-config.js` file will contain shared Tabulator setup options (pagination, AJAX data source URL building, column definitions).

### Shared Template Architecture

A single `_content.tpl` handles all pages that are simple enough to be rendered as a title + body inside a card. The controller assigns `{$title}` and `{$content}` (pre-built HTML) and calls `display_smarty_template('_content.tpl')`.

**`_content.tpl` (new):**
```html
<div class="container mt-4">
  <div class="card">
    <div class="card-header"><h3 class="mb-0">{$title}</h3></div>
    <div class="card-body">{$content}</div>
  </div>
</div>
```

This eliminates 21 individual templates/pages — the 11 simplest existing templates and the 10 inline-PHP controllers:

**Eliminated templates (replaced by `_content.tpl`):**

| Old template | Title | Content |
|---|---|---|
| `filetype_add.tpl` | "Add Filetype" | text input + save/cancel |
| `filetypes_deleteshow.tpl` | "Delete Filetypes" | multi-select + delete/cancel |
| `filetypes.tpl` | "Allowed Filetypes" | multi-select + save/cancel |
| `user_delete_pick.tpl` | "Delete User" | user dropdown + delete/cancel |
| `user_show_pick.tpl` | "Show User" | user dropdown + show/cancel |
| `user_delete.tpl` | "Delete User" | confirmation text + delete/cancel |
| `user_show.tpl` | "User Info" | user data table + back button |
| `user/edit_pick.tpl` | "Edit User" | user dropdown + modify/cancel |
| `udf/delete_pick.tpl` | "Delete UDF" | UDF dropdown + delete/cancel |
| `udf/delete_form.tpl` | "Delete UDF" | UDF details + confirm/cancel |
| `udf/add.tpl` | "Add UDF" | name/display/type fields + save/cancel |
| `view_file.tpl` | "View File" | click-here link + download button |

**Eliminated inline-PHP controllers (replaced by `_content.tpl`):**

| Controller | Title | Content |
|---|---|---|
| `controllers/search.php` body | "Search" | search form + results |
| `controllers/history.php` body | "History" | revision history table |
| `controllers/admin.php` body | "Admin" | admin section links |
| `controllers/department.php` body | "Departments" | department management |
| `controllers/category.php` body | "Categories" | category management |
| `controllers/profile.php` body | "Profile" | user profile form |
| `controllers/rejects.php` body | "Rejected Documents" | rejected files list |
| `controllers/forgot_password.php` body | "Forgot Password" | email form |
| `controllers/signup.php` body | "Sign Up" | registration form |
| `controllers/access_log.php` (Aura.View) | "Access Log" | access log table |

Each controller will build its HTML body as a string, `$smarty->assign('content', $html)` and `$smarty->assign('title', $title)`, then call `display_smarty_template('_content.tpl')`.

### Templates That Need Individual Files

These have unique interactive layouts that go beyond a simple card:

| Template | Reason |
|---|---|
| `header.tpl` (theme) | BS5 navbar, flash messages, breadcrumbs |
| `footer.tpl` (theme) | Closing tags, footer |
| `head_include.tpl` (theme) | CDN/asset loading |
| `login.tpl` (theme) | Full standalone page, centered card, demo creds |
| `out.tpl` | Tabulator mount point, filter toolbar, action buttons |
| `details.tpl` | Multi-section layout: metadata, action buttons, revision history, comments |
| `add.tpl` | Multi-file upload, includes `_filePermissions.tpl`, complex validation |
| `edit.tpl` | Same as add but with pre-populated data |
| `_filePermissions.tpl` | Included partial — accordion with department + user permission tables |
| `commentform.tpl` | Email form with To/Subject/Comment, multi-select user list |
| `settings.tpl` | Dynamic form with per-field type rendering |
| `user_add.tpl` | 10+ field user creation form with validation |
| `user/edit.tpl` | Same as user_add but with pre-populated data |
| `udf/edit_types_1_and_2.tpl` | Editable list with select/radio options |
| `udf/edit_type_4.tpl` | Same plus primary/secondary select toggle |
| `toBePublished.tpl` | Review queue with authorize/reject buttons |
| `deleteview.tpl` | Undelete/delete button bar |

### Template Design

**`header.tpl` (theme)** — Bootstrap 5 responsive navbar:
- Site branding/logo on the left
- Nav items: Home, Check In, Search, Add Document, Admin dropdown, Logout
- Logged-in user indicator
- Breadcrumb trail below navbar
- Flash messages rendered as BS5 dismissible alerts

**`footer.tpl` (theme)** — Simple:
- `Powered by OpenDocMan` text
- Closing `</body></html>`

**`head_include.tpl` (theme)** — CDN loads:
- Bootstrap 5 CSS + JS
- Tabulator CSS + JS
- Theme-specific `style.css`, `app.js`, `tabulator-config.js`

**`login.tpl` (theme)** — Centered card:
- BS5 card in the middle of viewport
- Logo at top of card
- Username/password fields with BS5 form styling
- "Forgot Password" and "Sign Up" links below
- Error messages as BS5 alerts

**`_content.tpl` (common)** — Generic card wrapper:
- BS5 card with header and body
- `{$title}` in the header, `{$content}` (pre-built HTML) in the body

**`out.tpl` (common)** — Tabulator file listing:
- Search/filter toolbar above the table
- Tabulator table with: filename, status, category, department, modified date, actions
- Checkbox column for batch operations
- Pagination, sortable columns
- Action buttons per row (Download, Edit, Delete, etc.)

**`details.tpl` (common)** — Card-based layout:
- File metadata (filename, description, category, department, dates)
- Action button row: Download, Check Out/In, Edit, Delete, History
- Revision history table (Tabulator)
- Comments/review section

**`add.tpl` / `edit.tpl` (common)** — BS5 forms:
- Properly styled form groups, labels, inputs
- BS5 native validation (`was-validated`)
- Tom Select or `<select multiple>` for multi-select fields
- File upload with drag-and-drop styling

## Theme Extensibility

A new theme can be created by:
1. Copying `public/css/bootstrap5/`, `public/js/bootstrap5/`, and `application/views/bootstrap5/` (the header/footer/login)
2. Renaming the directory
3. Updating CDN URLs in `head_include.tpl` (e.g., Bootstrap 5 → another framework)
4. Overriding `style.css` and `app.js`
5. Optionally overriding individual `common/` templates by placing a `.tpl` of the same name in the theme directory

The shared templates in `common/` provide a stable HTML structure that themes can target with CSS. This is the same system that already exists — the `bootstrap5` theme simply provides a complete, modern reference implementation.

A `THEMING.md` document will be written explaining this process.

## Non-Goals

- **Installer pages** — left for a future pass
- **CSS preprocessing** — no Sass/Less build step for now; CDN + plain CSS overrides
- **JS bundling** — no webpack/vite; CDN + local script files
- **Browser support** — modern browsers only (BS5 requirement)

## Testing

- Update the existing Playwright E2E smoke test (`tests/smoke-uat.spec.ts`) to work with the new theme
- Manual visual QA on every page (login, dashboard, listing, detail, add, edit, search, history, admin pages)
- Verify Tabulator renders correctly with existing data
- Verify BS5 form validation works on all forms
- Verify AJAX flows (check-in, check-out, CSRF) work via vanilla `fetch()`

## Migration Path

1. Create the theme directory and asset files
2. Write `header.tpl`, `footer.tpl`, `head_include.tpl` — provides BS5 chrome to all pages immediately
3. Write `login.tpl` (theme override) — first visible page
4. Write `_content.tpl` — the generic card wrapper
5. Convert inline-PHP controllers to use `_content.tpl` (title + content assignment)
6. Update `common/out.tpl` — replace DataTable with Tabulator mount point
7. Update remaining unique templates individually
8. Update E2E tests
9. Write `THEMING.md`
10. Default theme to `bootstrap5` (update the setting or migration)