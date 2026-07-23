# Inline "Add Category" on Add/Edit File Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an inline "Add Category" AJAX form on the Add File and Edit File pages for admin users.

**Architecture:** Two new JSON endpoints in `category.php` (`list_json` and `add_json`) handle the backend. The existing Smarty templates `add.tpl` and `edit.tpl` get a hidden inline form and vanilla JS that toggles it, POSTs the new category via `fetch()`, and refreshes the `<select>` dropdown.

**Tech Stack:** PHP (vanilla), Smarty templates, vanilla JS (no jQuery), Bootstrap 5

## Global Constraints

- Admin permission check required on all new endpoints (defense-in-depth)
- Use existing CSRF token (`window.csrf_token`, `window.csrf_field_name`) from `head_include.tpl`
- No new jQuery — use `fetch()`, `addEventListener`, `querySelector`
- No new language strings — reuse `$lang['button_add_category']` (already exists in all 17 languages)
- Reuse `$is_admin` Smarty variable (already assigned in both `add.php` and `edit.php`)

---

### Task 1: JSON endpoints in category.php

**Files:**
- Modify: `application/controllers/category.php` (add 2 new `elseif` branches before final closing `}`)

**Interfaces:**
- Produces: `GET /category?submit=list_json` → `[{"id": int, "name": string}, ...]`
- Produces: `POST /category` with `submit=add_json&category=<name>&csrf...` → `{"success": true, "id": int, "name": string}`

- [ ] **Step 1: Add `list_json` and `add_json` branches to category.php**

Open `application/controllers/category.php`. Find the last `elseif` block at line 422:
```php
} elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] == 'Cancel') {
```

After that block (after its closing `}`) and before the file's final closing `}`, add:

```php
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'list_json') {
    if (!$user_obj->isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    $categories = Category::getAllCategories($pdo);
    $categories = array_map(function ($cat) {
        return ['id' => (int)$cat['id'], 'name' => $cat['name']];
    }, $categories);
    echo json_encode($categories);
    exit;
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'add_json') {
    if (!$user_obj->isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'CSRF validation failed']);
        exit;
    }
    $name = trim($_REQUEST['category'] ?? '');
    if ($name === '') {
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(['error' => 'Category name is required']);
        exit;
    }
    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}category (name) VALUES (:name)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':name' => $name]);
    $newId = (int)$pdo->lastInsertId();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'id' => $newId, 'name' => $name]);
    exit;
```

- [ ] **Step 2: Test the JSON endpoints**

Start the app and verify:
```bash
# List endpoint (as admin)
curl -s 'http://localhost:8080/category?submit=list_json' | python3 -m json.tool
# Expected: JSON array of categories (or 403 if not logged in)

# Create endpoint (requires session cookie, so test via browser or with session)
```

If you can get a session cookie, test the create:
```bash
# First get a session cookie by logging in, then:
curl -s -b cookies.txt -X POST -d 'submit=add_json&category=TestCat&csrf_token_name=...' 'http://localhost:8080/category'
# Expected: {"success":true,"id":N,"name":"TestCat"}
```
Then verify `list_json` includes the new category.

- [ ] **Step 3: Commit**

```bash
git add application/controllers/category.php
git commit -m "feat: add list_json and add_json endpoints for inline category creation"
```

---

### Task 2: Inline form in add.tpl

**Files:**
- Modify: `application/views/common/add.tpl`
- Test: Manual browser test

**Interfaces:**
- Consumes: `GET /category?submit=list_json` (from Task 1)
- Consumes: `POST /category` with `submit=add_json` (from Task 1)

- [ ] **Step 1: Add inline form HTML to add.tpl**

Open `application/views/common/add.tpl`. After the closing `</select>` at line 53 and before its `</div>` on line 54, add:

```smarty
                    {if $is_admin}
                    <button type="button" id="showAddCategory" class="btn btn-sm btn-outline-primary mt-1">+ {$g_lang_button_add_category}</button>
                    <div id="addCategoryForm" class="mt-1 p-2 border rounded" style="display:none">
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" id="newCategoryName" class="form-control" maxlength="40" placeholder="{$g_lang_label_name}" required>
                            <button type="button" id="saveCategory" class="btn btn-primary">{$g_lang_button_add_category}</button>
                            <button type="button" id="cancelCategory" class="btn btn-secondary">{$g_lang_button_cancel}</button>
                        </div>
                        <span id="categoryStatus" class="small"></span>
                    </div>
                    {/if}
```

- [ ] **Step 2: Add inline JS to add.tpl**

Open `application/views/common/add.tpl`. After the closing `</form>` (which ends the form), add before the `{include file=...}` line (currently the JS is included after the form, inside the template structure). Actually, the best place is at the end of the file content. Looking at the file structure, the form ends on line 77 and then `_add_footer.tpl` is included from `add.php`. The inline script can go inside the form div or at the end of the file.

Add the following at the end of the file (after line 77):

```smarty

<script>
document.addEventListener('DOMContentLoaded', function() {
    var showBtn = document.getElementById('showAddCategory');
    var formDiv = document.getElementById('addCategoryForm');
    var cancelBtn = document.getElementById('cancelCategory');
    var saveBtn = document.getElementById('saveCategory');
    var nameInput = document.getElementById('newCategoryName');
    var statusEl = document.getElementById('categoryStatus');
    var catSelect = document.querySelector('select[name="category"]');

    if (!showBtn) return;

    function toggleForm(show) {
        formDiv.style.display = show ? 'block' : 'none';
        statusEl.textContent = '';
        if (show) nameInput.focus();
    }

    showBtn.addEventListener('click', function () { toggleForm(true); });
    cancelBtn.addEventListener('click', function () { toggleForm(false); });

    saveBtn.addEventListener('click', function () {
        var name = nameInput.value.trim();
        if (!name) { statusEl.textContent = 'Name required'; nameInput.focus(); return; }

        var fd = new FormData();
        fd.append('submit', 'add_json');
        fd.append('category', name);
        fd.append(window.csrf_field_name, window.csrf_token);

        statusEl.textContent = 'Saving...';
        saveBtn.disabled = true;

        fetch('category', { method: 'POST', body: fd })
            .then(function (r) {
                if (!r.ok) return r.json().then(function (e) { throw new Error(e.error || 'Save failed'); });
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error('Save failed');
                return fetch('category?submit=list_json');
            })
            .then(function (r) { return r.json(); })
            .then(function (cats) {
                catSelect.innerHTML = '';
                cats.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    catSelect.appendChild(opt);
                });
                catSelect.value = cats.length ? cats[cats.length - 1].id : '';
                nameInput.value = '';
                toggleForm(false);
                statusEl.textContent = '';
            })
            .catch(function (err) {
                statusEl.textContent = 'Error: ' + err.message;
            })
            .finally(function () {
                saveBtn.disabled = false;
            });
    });
});
</script>
```

- [ ] **Step 3: Test the inline form**

1. Load the Add File page at `http://localhost:8080/add`
2. As admin, verify you see the "+ Add Category" button below the category dropdown
3. Click it — verify the inline form appears
4. Enter a category name and click "Add Category" — verify the form saves and the dropdown refreshes with the new category selected
5. Verify Cancel hides the form
6. Log out or test as non-admin — verify no button appears

- [ ] **Step 4: Commit**

```bash
git add application/views/common/add.tpl
git commit -m "feat: add inline add-category form to Add File page"
```

---

### Task 3: Inline form in edit.tpl

**Files:**
- Modify: `application/views/common/edit.tpl`
- Test: Manual browser test

**Interfaces:**
- Consumes: `GET /category?submit=list_json` (from Task 1)
- Consumes: `POST /category` with `submit=add_json` (from Task 1)

- [ ] **Step 1: Add inline form HTML to edit.tpl**

Open `application/views/common/edit.tpl`. After the closing `</select>` at line 52 and before its `</div>` on line 53, add the same block as in Task 2:

```smarty
                    {if $is_admin}
                    <button type="button" id="showAddCategory" class="btn btn-sm btn-outline-primary mt-1">+ {$g_lang_button_add_category}</button>
                    <div id="addCategoryForm" class="mt-1 p-2 border rounded" style="display:none">
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" id="newCategoryName" class="form-control" maxlength="40" placeholder="{$g_lang_label_name}" required>
                            <button type="button" id="saveCategory" class="btn btn-primary">{$g_lang_button_add_category}</button>
                            <button type="button" id="cancelCategory" class="btn btn-secondary">{$g_lang_button_cancel}</button>
                        </div>
                        <span id="categoryStatus" class="small"></span>
                    </div>
                    {/if}
```

- [ ] **Step 2: Add inline JS to edit.tpl**

Open `application/views/common/edit.tpl`. After the closing `</form>` at line 3 (the form tag opens on line 3 and the file ends at line 76), add the same JS block at the end of the file:

```smarty

<script>
document.addEventListener('DOMContentLoaded', function() {
    var showBtn = document.getElementById('showAddCategory');
    var formDiv = document.getElementById('addCategoryForm');
    var cancelBtn = document.getElementById('cancelCategory');
    var saveBtn = document.getElementById('saveCategory');
    var nameInput = document.getElementById('newCategoryName');
    var statusEl = document.getElementById('categoryStatus');
    var catSelect = document.querySelector('select[name="category"]');

    if (!showBtn) return;

    function toggleForm(show) {
        formDiv.style.display = show ? 'block' : 'none';
        statusEl.textContent = '';
        if (show) nameInput.focus();
    }

    showBtn.addEventListener('click', function () { toggleForm(true); });
    cancelBtn.addEventListener('click', function () { toggleForm(false); });

    saveBtn.addEventListener('click', function () {
        var name = nameInput.value.trim();
        if (!name) { statusEl.textContent = 'Name required'; nameInput.focus(); return; }

        var fd = new FormData();
        fd.append('submit', 'add_json');
        fd.append('category', name);
        fd.append(window.csrf_field_name, window.csrf_token);

        statusEl.textContent = 'Saving...';
        saveBtn.disabled = true;

        fetch('category', { method: 'POST', body: fd })
            .then(function (r) {
                if (!r.ok) return r.json().then(function (e) { throw new Error(e.error || 'Save failed'); });
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error('Save failed');
                return fetch('category?submit=list_json');
            })
            .then(function (r) { return r.json(); })
            .then(function (cats) {
                catSelect.innerHTML = '';
                cats.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    catSelect.appendChild(opt);
                });
                catSelect.value = cats.length ? cats[cats.length - 1].id : '';
                nameInput.value = '';
                toggleForm(false);
                statusEl.textContent = '';
            })
            .catch(function (err) {
                statusEl.textContent = 'Error: ' + err.message;
            })
            .finally(function () {
                saveBtn.disabled = false;
            });
    });
});
</script>
```

- [ ] **Step 3: Test the inline form on Edit page**

1. Log in as admin
2. Navigate to Edit File page for any document (`http://localhost:8080/edit?id=N`)
3. Verify the "+ Add Category" button appears below the category dropdown
4. Test the full flow — create a category, verify dropdown refreshes
5. Verify non-admins don't see the button

- [ ] **Step 4: Commit**

```bash
git add application/views/common/edit.tpl
git commit -m "feat: add inline add-category form to Edit File page"
```
