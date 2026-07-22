# Bootstrap 5 Modern UI Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a new "bootstrap5" theme with a modern UI, replacing jQuery with vanilla JS/BS5 native and DataTables with Tabulator.

**Architecture:** Theme provides chrome (header, footer, login) in `views/bootstrap5/`. A shared `_content.tpl` in `common/` renders any simple page as a card. Unique templates (out, details, add, edit, etc.) stay in `common/` with updated HTML. The theme's CSS handles all visual styling.

**Tech Stack:** Bootstrap 5 (CDN), Tabulator (MIT), vanilla JS, Tom Select

## Global Constraints

- Bootstrap 5 loaded from CDN in `head_include.tpl` — no build step
- Tabulator loaded from CDN — MIT license is GPL-compatible
- jQuery fully removed — no jQuery dependency anywhere
- jQuery Validate replaced by BS5 native HTML5 validation
- jQuery MultiSelect replaced by Tom Select
- All pages must work with the existing theme fallback system: `display_smarty_template()` checks theme dir first, falls back to `common/`
- Theme name: `bootstrap5` (directory name in `views/` and `public/css|js/`)
- The `_content.tpl` template renders `{$title}` + `{$content}` in a BS5 card
- Inline-PHP controllers use `ob_start()`/`ob_get_clean()` to capture HTML, then assign to `$content` and call `display_smarty_template('_content.tpl')`

---
### Task 1: Theme Chrome Files

**Files:**
- Create: `application/views/bootstrap5/header.tpl`
- Create: `application/views/bootstrap5/footer.tpl`
- Create: `application/views/bootstrap5/head_include.tpl`
- Create: `public/css/bootstrap5/style.css`
- Create: `public/js/bootstrap5/app.js`
- Create: `public/js/bootstrap5/tabulator-config.js`

**Interfaces:**
- Consumes: existing `draw_header()` assigns `{$userName}`, `{$can_add}`, `{$can_checkin}`, `{$isadmin}`, `{$breadCrumb}`, `{$site_title}`, `{$base_url}`, `{$page_title}`, `{$lastmessage}` to Smarty
- Consumes: existing `draw_footer()` assigns `{$site_title}`
- Produces: BS5-themed chrome for all pages

- [ ] **Step 1: Create `head_include.tpl`**

```smarty
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.4/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{$g_base_url}/css/bootstrap5/style.css">
{$csrf_meta}
```

- [ ] **Step 2: Create `header.tpl`**

```smarty
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$page_title|escape} - {$site_title|escape}</title>
    {include file="head_include.tpl"}
</head>
<body>
    {if $g_lang_username != ''}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="out">{$site_title|escape}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="out">{$g_lang_outpage_file_listing}</a></li>
                    {if $can_checkin eq '1'}<li class="nav-item"><a class="nav-link" href="check-out">{$g_lang_check_out_page_check_out}</a></li>{/if}
                    <li class="nav-item"><a class="nav-link" href="search">{$g_lang_search}</a></li>
                    {if $can_add eq '1'}<li class="nav-item"><a class="nav-link" href="add">{$g_lang_add_page_add_document}</a></li>{/if}
                    {if $isadmin eq 'yes'}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">{$g_lang_label_admin}</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin">{$g_lang_label_admin}</a></li>
                            <li><a class="dropdown-item" href="settings">{$g_lang_label_settings}</a></li>
                            <li><a class="dropdown-item" href="access_log">{$g_lang_access_log_page_access_log}</a></li>
                        </ul>
                    </li>
                    {/if}
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="profile">{$userName|escape}</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout">{$g_lang_label_logout}</a></li>
                </ul>
            </div>
        </div>
    </nav>
    {/if}
    {if $breadCrumb ne ''}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-light">{$breadCrumb}</ol>
    </nav>
    {/if}
    {if $lastmessage ne ''}
    <div class="container mt-2">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {$lastmessage|escape}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    {/if}
    <main class="container-fluid py-3">
```

- [ ] **Step 3: Create `footer.tpl`**

```smarty
    </main>
    <footer class="footer mt-auto py-3 bg-light text-center text-muted small">
        <span>{$g_lang_powered_by} <a href="https://www.opendocman.com/" class="text-decoration-none">OpenDocMan</a></span>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3/dist/js/tabulator.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4/dist/js/tom-select.complete.min.js"></script>
    <script src="{$g_base_url}/js/bootstrap5/app.js"></script>
    <script src="{$g_base_url}/js/bootstrap5/tabulator-config.js"></script>
</body>
</html>
```

- [ ] **Step 4: Create `style.css`**

```css
/* Bootstrap 5 Modern UI Theme */
body {
    background-color: #f5f7fa;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.container-fluid {
    flex: 1;
}
.breadcrumb {
    background-color: #e9ecef;
    border-radius: 0;
}
.card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
```

- [ ] **Step 5: Create `app.js`**

```js
// Bootstrap 5 Modern UI — app.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Initialize Tom Select on all multi-selects
    document.querySelectorAll('select[multiple]').forEach(function(el) {
        new TomSelect(el, { plugins: ['remove_button'] });
    });
});
```

- [ ] **Step 6: Create `tabulator-config.js`**

```js
// Tabulator shared configuration
var tabulatorDefaults = {
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 25,
    paginationSizeSelector: [10, 25, 50, 100],
    movableColumns: true,
    resizableColumns: true,
    placeholder: 'No data available'
};
```

- [ ] **Step 7: Commit**

```bash
git add application/views/bootstrap5/ public/css/bootstrap5/ public/js/bootstrap5/
git commit -m "feat: create bootstrap5 theme chrome files (header, footer, head_include, CSS, JS)"
```

---
### Task 2: Login Page Template

**Files:**
- Create: `application/views/bootstrap5/login.tpl`

**Interfaces:**
- Consumes: existing `{$g_lang_*}` language strings, `{$g_base_url}`, `{$csrf_token_field}`
- Produces: modern BS5 login page

- [ ] **Step 1: Create `login.tpl`**

```smarty
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$g_lang_label_login} - {$site_title|escape}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{$g_base_url}/css/bootstrap5/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="{$g_base_url}/images/logo.png" alt="{$site_title|escape}" class="mb-3" style="max-height: 60px;">
                            <h4>{$site_title|escape}</h4>
                        </div>
                        {if $lastmessage ne ''}
                            <div class="alert alert-danger">{$lastmessage|escape}</div>
                        {/if}
                        <form action="index" method="post">
                            {$csrf_token_field}
                            <div class="mb-3">
                                <label for="username" class="form-label">{$g_lang_label_username}</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">{$g_lang_label_password}</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{$g_lang_label_login}</button>
                        </form>
                        <div class="text-center mt-3">
                            {if $g_lang_allow_password_reset eq 'True'}
                                <a href="forgot_password" class="text-decoration-none small">{$g_lang_forgot_password}</a>
                            {/if}
                            {if $g_lang_allow_signup eq 'True'}
                                {if $g_lang_allow_password_reset eq 'True'}| {/if}
                                <a href="signup" class="text-decoration-none small">{$g_lang_signup}</a>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add application/views/bootstrap5/login.tpl
git commit -m "feat: add bootstrap5 login page template"
```

---
### Task 3: `_content.tpl` and Inline-PHP Controller Conversion

**Files:**
- Create: `application/views/common/_content.tpl`
- Modify: `controllers/forgot_password.php` (3 branches)
- Modify: `controllers/signup.php` (2 branches)
- Modify: `controllers/search.php` (2 branches)
- Modify: `controllers/history.php` (1 branch)
- Modify: `controllers/admin.php` (1 branch)
- Modify: `controllers/department.php` (1 branch)
- Modify: `controllers/category.php` (1 branch)
- Modify: `controllers/profile.php` (1 branch)
- Modify: `controllers/rejects.php` (1 branch)
- Modify: `controllers/access_log.php` (1 branch)

**Interfaces:**
- Consumes: `{$title}` (string), `{$content}` (HTML string) from Smarty
- Produces: BS5 card wrapping any page content

- [ ] **Step 1: Create `_content.tpl`**

```smarty
<div class="container mt-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">{$title}</h3>
        </div>
        <div class="card-body">
            {$content}
        </div>
    </div>
</div>
```

- [ ] **Step 2: Convert `forgot_password.php`**

Replace the three inline-HTML render blocks with `ob_start()` / `$content` / `display_smarty_template('_content.tpl')`.

In the default form branch (line 250-273), replace:
```php
draw_header(msg('forgot_password'), $last_message);
?>
        <p><?php echo msg('message_this_site_has_high_security')?></p>
        <form action="forgot_password" method="post">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <table border="0">
        ...
        </form>
        <?php
        draw_footer();
```

With:
```php
draw_header(msg('forgot_password'), $last_message);
ob_start();
?>
        <p><?php echo msg('message_this_site_has_high_security')?></p>
        <form action="forgot_password" method="post">
        <?php echo $GLOBALS['csrf']->getTokenField(); ?>
        <div class="mb-3">
            <label for="username" class="form-label"><?php echo msg('username')?>:</label>
            <input type="text" class="form-control" id="username" name="username" size="25" maxlength="25">
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
<?php
$content = ob_get_clean();
$GLOBALS['smarty']->assign('content', $content);
display_smarty_template('_content.tpl');
draw_footer();
```

Apply the same pattern to the reset-password form branch (line 127-154).

- [ ] **Step 3: Convert `signup.php`**

Find the inline-HTML block (the form display) and wrap it with `ob_start()` / `$content` / `display_smarty_template('_content.tpl')` replacing the existing `draw_header()`/`draw_footer()` sandwich.

- [ ] **Step 4: Convert `search.php`, `history.php`, `admin.php`, `department.php`, `category.php`, `profile.php`, `rejects.php`, `access_log.php`**

For each controller, locate the `draw_header()` / `echo` HTML / `draw_footer()` pattern. Wrap the HTML body in `ob_start()` / `ob_get_clean()`, assign to `$content`, and call `display_smarty_template('_content.tpl')`.

- [ ] **Step 5: Commit**

```bash
git add application/views/common/_content.tpl application/controllers/forgot_password.php application/controllers/signup.php application/controllers/search.php application/controllers/history.php application/controllers/admin.php application/controllers/department.php application/controllers/category.php application/controllers/profile.php application/controllers/rejects.php application/controllers/access_log.php
git commit -m "feat: add _content.tpl and convert inline-PHP controllers to use it"
```

---
### Task 4: Tabulator File Listing (out.tpl)

**Files:**
- Modify: `application/views/common/out.tpl`
- Modify: `application/controllers/helpers/functions.php` (list_files function)

**Interfaces:**
- Consumes: `{$file_list_arr}` (array of file data), `{$showCheckBox}`, `{$limit_reached}` from Smarty
- Consumes: `sort_browser()` JS output (currently inline)
- Produces: Tabulator table in `out.tpl`

- [ ] **Step 1: Update `out.tpl`**

Replace the existing DataTable-based table with a Tabulator mount point:

```smarty
<div class="container mt-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">{$g_lang_outpage_file_listing}</h3>
        </div>
        <div class="card-body">
            <div id="file-table"></div>
            {if $limit_reached}
                <div class="alert alert-warning mt-2">{$g_lang_message_max_number_of_results}</div>
            {/if}
        </div>
    </div>
</div>
```

- [ ] **Step 2: Update `list_files()` in `functions.php`**

Remove the `display_smarty_template('out.tpl')` call at line 377. Instead, assign the `$file_list_arr` to Smarty and let `out.php` call `display_smarty_template('out.tpl')` after `list_files()` returns.

This means the `sort_browser()` function's inline JS (which generates the filter toolbar) should also be moved — the filter toolbar will be handled by Tabulator's built-in header filtering.

- [ ] **Step 3: Update `out.php`**

Replace the `list_files()` call pattern. Currently `out.php` calls `list_files()` which both builds the data array AND renders the template. Change so `list_files()` only builds the data, and `out.php` renders the template:

```php
// out.php - after list_files returns
$GLOBALS['smarty']->assign('file_list_arr', $file_list_arr);
display_smarty_template('out.tpl');
```

- [ ] **Step 4: Add Tabulator initialization in `tabulator-config.js`**

```js
// Initialize file listing table
function initFileTable() {
    var tableData = document.getElementById('file-table-data');
    if (!tableData) return;
    
    var data = JSON.parse(tableData.textContent);
    new Tabulator('#file-table', Object.assign({}, tabulatorDefaults, {
        data: data,
        columns: [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Filename', field: 'filename', widthGrow: 2 },
            { title: 'Description', field: 'description', widthGrow: 3 },
            { title: 'Created', field: 'created_date', width: 120 },
            { title: 'Modified', field: 'modified_date', width: 120 },
            { title: 'Author', field: 'owner_name', width: 150 },
            { title: 'Department', field: 'dept_name', width: 120 },
            { title: 'Size', field: 'filesize', width: 80 }
        ]
    }));
}
document.addEventListener('DOMContentLoaded', initFileTable);
```

- [ ] **Step 5: Commit**

```bash
git add application/views/common/out.tpl application/controllers/helpers/functions.php application/controllers/out.php public/js/bootstrap5/tabulator-config.js
git commit -m "feat: replace DataTable with Tabulator in file listing"
```

---
### Task 5: Update Document Workflow Templates

**Files:**
- Modify: `application/views/common/details.tpl`
- Modify: `application/views/common/add.tpl`
- Modify: `application/views/common/edit.tpl`
- Modify: `application/views/common/_filePermissions.tpl`
- Modify: `application/views/common/view_file.tpl`

**Interfaces:**
- Consumes: existing Smarty variables from `controllers/details.php`, `controllers/add.php`, `controllers/edit.php`
- Produces: BS5-modernized document workflow pages

- [ ] **Step 1: Update `details.tpl`**

Replace the table-based layout with BS5 cards. Use Tabulator for the revision history table. Replace inline jQuery popup JS with vanilla JS.

```smarty
<div class="container mt-3">
    <div class="card mb-3">
        <div class="card-header"><h3 class="mb-0">{$g_lang_details_page_details}</h3></div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{$g_lang_label_file_name}</dt>
                <dd class="col-sm-9"><a href="{$view_link|escape}">{$realname|escape}</a></dd>
                <dt class="col-sm-3">{$g_lang_label_description}</dt>
                <dd class="col-sm-9">{$description|escape}</dd>
                <dt class="col-sm-3">{$g_lang_label_category}</dt>
                <dd class="col-sm-9">{$category_name|escape}</dd>
                <dt class="col-sm-3">{$g_lang_label_department}</dt>
                <dd class="col-sm-9">{$dept_name|escape}</dd>
                <dt class="col-sm-3">{$g_lang_label_created_date}</dt>
                <dd class="col-sm-9">{$created_date|escape}</dd>
                <dt class="col-sm-3">{$g_lang_label_modified_date}</dt>
                <dd class="col-sm-9">{$modified_date|escape}</dd>
                <dt class="col-sm-3">{$g_lang_label_author}</dt>
                <dd class="col-sm-9">{$owner_name|escape}</dd>
            </dl>
            <div class="btn-group" role="group">
                <a href="{$view_link|escape}" class="btn btn-outline-primary">{$g_lang_view}</a>
                <a href="{$checkout_link|escape}" class="btn btn-outline-secondary">{$g_lang_check_out}</a>
                <a href="{$edit_link|escape}" class="btn btn-outline-warning">{$g_lang_edit}</a>
                <a href="{$delete_link|escape}" class="btn btn-outline-danger">{$g_lang_delete}</a>
                <a href="{$history_link|escape}" class="btn btn-outline-info">{$g_lang_history}</a>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Update `add.tpl` and `edit.tpl`**

Replace table-based form with BS5 form groups. Replace jQuery validation with BS5 native validation. Replace inline `<script>` tags with data attributes.

- [ ] **Step 3: Update `_filePermissions.tpl`**

Replace the accordion with BS5 accordion component. Replace inline jQuery with vanilla JS.

- [ ] **Step 4: Update `view_file.tpl`**

Replace table-based layout with a simple BS5 card:

```smarty
<div class="container mt-3">
    <div class="card">
        <div class="card-body text-center">
            <p class="card-text">{$g_lang_message_to_view_your_file}</p>
            <a class="btn btn-primary" target="_new" href="view_file?submit=view&id={$file_id|escape:'html'}&mimetype={$mimetype|escape:'html'}">{$g_lang_button_click_here}</a>
            <hr>
            <form action="view_file" method="get" class="d-inline">
                {$csrf_token_field}
                <input type="hidden" name="id" value="{$file_id|escape:'html'}">
                <input type="hidden" name="mimetype" value="{$mimetype|escape:'html'}">
                <button type="submit" name="submit" value="Download" class="btn btn-success">{$g_lang_message_if_you_are_unable_to_view2}</button>
            </form>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Commit**

```bash
git add application/views/common/details.tpl application/views/common/add.tpl application/views/common/edit.tpl application/views/common/_filePermissions.tpl application/views/common/view_file.tpl
git commit -m "feat: update document workflow templates for BS5"
```

---
### Task 6: Update Admin and Settings Templates

**Files:**
- Modify: `application/views/common/settings.tpl`
- Modify: `application/views/common/user_add.tpl`
- Modify: `application/views/common/user/edit.tpl`
- Modify: `application/views/common/commentform.tpl`

**Interfaces:**
- Consumes: existing Smarty variables from `controllers/settings.php`, `controllers/user.php`
- Produces: BS5-modernized admin/settings pages

- [ ] **Step 1: Update `settings.tpl`**

Replace the table-based form with BS5 form groups. Each setting row becomes a form group with label and input. Theme/language dropdowns get BS5 styling.

- [ ] **Step 2: Update `user_add.tpl` and `user/edit.tpl`**

Replace table-based form with BS5 form groups. Replace jQuery validation with BS5 native validation. Replace the multi-select with Tom Select (initialized by `app.js`).

- [ ] **Step 3: Update `commentform.tpl`**

Replace table-based email form with BS5 form groups. Replace inline jQuery checkbox logic with vanilla JS.

- [ ] **Step 4: Commit**

```bash
git add application/views/common/settings.tpl application/views/common/user_add.tpl application/views/common/user/edit.tpl application/views/common/commentform.tpl
git commit -m "feat: update admin and settings templates for BS5"
```

---
### Task 7: Update Remaining Templates

**Files:**
- Modify: `application/views/common/toBePublished.tpl`
- Modify: `application/views/common/deleteview.tpl`
- Modify: `application/views/common/udf/edit_types_1_and_2.tpl`
- Modify: `application/views/common/udf/edit_type_4.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/filetype_add.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/filetypes_deleteshow.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/filetypes.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/user_delete_pick.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/user_show_pick.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/user_delete.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/user_show.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/user/edit_pick.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/udf/delete_pick.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/udf/delete_form.tpl`
- Delete (replaced by `_content.tpl`): `application/views/common/udf/add.tpl`

**Interfaces:**
- Consumes: existing Smarty variables from `controllers/toBePublished.php`
- Produces: BS5-modernized remaining pages, removed obsolete templates

- [ ] **Step 1: Update `toBePublished.tpl`** — Replace with BS5 card layout, review queue as Tabulator table
- [ ] **Step 2: Update `deleteview.tpl`** — Replace with BS5 button bar
- [ ] **Step 3: Update `udf/edit_types_1_and_2.tpl` and `udf/edit_type_4.tpl`** — Replace table-based layout with BS5, replace inline JS with vanilla JS
- [ ] **Step 4: Delete the 11 obsolete templates** (replaced by `_content.tpl`)
- [ ] **Step 5: Commit**

```bash
git add application/views/common/toBePublished.tpl application/views/common/deleteview.tpl application/views/common/udf/ git rm application/views/common/filetype_add.tpl application/views/common/filetypes_deleteshow.tpl application/views/common/filetypes.tpl application/views/common/user_delete_pick.tpl application/views/common/user_show_pick.tpl application/views/common/user_delete.tpl application/views/common/user_show.tpl application/views/common/user/edit_pick.tpl application/views/common/udf/delete_pick.tpl application/views/common/udf/delete_form.tpl application/views/common/udf/add.tpl
git commit -m "feat: update remaining templates for BS5, remove obsolete templates"
```

---
### Task 8: Update E2E Tests and Documentation

**Files:**
- Modify: `tests/smoke-uat.spec.ts`
- Create: `THEMING.md`

**Interfaces:**
- Consumes: new Bootstrap 5 login page selectors and DOM structure
- Produces: passing E2E tests, documentation for theme creation

- [ ] **Step 1: Update E2E smoke test**

Update playwright selectors to match the new BS5 login page (e.g., `.form-control`, `.btn-primary`, etc.). The test flow remains the same: navigate to login, enter credentials, verify dashboard, change a setting, verify persistence.

- [ ] **Step 2: Create `THEMING.md`**

Document how to create a new theme by copying the `bootstrap5` theme directory, updating CDN URLs, and overriding CSS/JS. Include the `display_smarty_template()` fallback explanation.

- [ ] **Step 3: Commit**

```bash
git add tests/smoke-uat.spec.ts THEMING.md
git commit -m "chore: update E2E tests and add THEMING.md"
```

---
### Task 9: Set Default Theme to `bootstrap5`

**Files:**
- Modify: `application/models/Settings.php` (or the migration that sets the default theme)

**Interfaces:**
- Consumes: existing theme switching logic in Settings
- Produces: new installations default to `bootstrap5` theme

- [ ] **Step 1: Update default theme value**

In the settings model or installer, change the default theme value from `default` to `bootstrap5`.

- [ ] **Step 2: Commit**

```bash
git add application/models/Settings.php
git commit -m "feat: set bootstrap5 as the default theme"
```