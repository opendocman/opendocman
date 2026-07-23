# Inline "Add Category" on Add/Edit File Pages

## Problem

Admin users adding or editing a file must navigate away to the admin panel to create a new category if the one they need doesn't exist yet. This breaks flow and adds friction.

## Solution

Add an inline "Add Category" form on the Add File and Edit File pages, visible only to admin users, that creates a new category via AJAX and refreshes the category dropdown in place.

## Files Changed

| File | Change |
|------|--------|
| `application/views/common/add.tpl` | Add inline form + JS after category `<select>` |
| `application/views/common/edit.tpl` | Same as add.tpl |
| `application/controllers/category.php` | Add `?submit=list_json` JSON endpoint with admin check |

## Detailed Design

### 1. Category Controller — JSON endpoints (`category.php`)

Two new branches in the `$_REQUEST['submit']` switch, added before the closing `}`:

**List endpoint** — returns all categories as JSON:
```php
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'list_json') {
    if (!$user_obj->isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    $categories = Category::getAllCategories($pdo);
    $categories = array_map(function($cat) {
        return ['id' => (int)$cat['id'], 'name' => $cat['name']];
    }, $categories);
    echo json_encode($categories);
    exit;
```

**Create endpoint** — creates a category and returns JSON instead of redirecting:
```php
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'add_json') {
    if (!$user_obj->isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    // Validate CSRF token
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

The admin check at the top of category.php (line 35) already gates the entire controller, so unauthorized users never reach any branch. The additional checks here are defense-in-depth.

### 2. Add File template (`add.tpl`)

After the category `<select>` block (line 45-54), add inside the `.mb-3` div, after the closing `</select>`:

```html
                {if $is_admin}
                <button type="button" id="showAddCategory" class="btn btn-sm btn-outline-primary mt-1">+ {$g_lang_button_add_category}</button>
                <div id="addCategoryForm" class="mt-1 p-2 border rounded" style="display:none">
                    <input type="text" id="newCategoryName" class="form-control form-control-sm mb-1" maxlength="40" required>
                    <button type="button" id="saveCategory" class="btn btn-sm btn-primary">{$g_lang_button_add_category}</button>
                    <button type="button" id="cancelCategory" class="btn btn-sm btn-secondary">{$g_lang_button_cancel}</button>
                    <span id="categoryStatus" class="ms-2"></span>
                </div>
                {/if}
```

Inline `<script>` block at the bottom of the template (inside `.tpl`):

```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    var showBtn = document.getElementById('showAddCategory');
    var formDiv = document.getElementById('addCategoryForm');
    var cancelBtn = document.getElementById('cancelCategory');
    var saveBtn = document.getElementById('saveCategory');
    var nameInput = document.getElementById('newCategoryName');
    var statusEl = document.getElementById('categoryStatus');
    var catSelect = document.querySelector('select[name="category"]');

    function toggleForm(show) {
        formDiv.style.display = show ? 'block' : 'none';
        statusEl.textContent = '';
        if (show) nameInput.focus();
    }

    if (showBtn) {
        showBtn.addEventListener('click', function() { toggleForm(true); });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() { toggleForm(false); });
    }
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var name = nameInput.value.trim();
            if (!name) { statusEl.textContent = 'Name required'; return; }

            var fd = new FormData();
            fd.append('submit', 'add_json');
            fd.append('category', name);
            fd.append(window.csrf_field_name, window.csrf_token);

            statusEl.textContent = 'Saving...';
            fetch('category', { method: 'POST', body: fd })
                .then(function(r) {
                    if (!r.ok) { return r.json().then(function(e) { throw new Error(e.error || 'Save failed'); }); }
                    return r.json();
                })
                .then(function(data) {
                    if (!data.success) throw new Error('Save failed');
                    // Refresh the category list
                    return fetch('category?submit=list_json');
                })
                .then(function(r) { return r.json(); })
                .then(function(cats) {
                    var selected = catSelect.value;
                    catSelect.innerHTML = '';
                    cats.forEach(function(c) {
                        var opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        catSelect.appendChild(opt);
                    });
                    // Re-select the newly created category (last one from list)
                    catSelect.value = cats.length ? cats[cats.length - 1].id : selected;
                    nameInput.value = '';
                    toggleForm(false);
                    statusEl.textContent = '';
                })
                .catch(function(err) {
                    statusEl.textContent = 'Error: ' + err.message;
                });
        });
    }
});
</script>
```

### 3. Edit File template (`edit.tpl`)

Identical change at the same location: after the category `<select>` block (line 44-53), add the same button, form div, and script.

The `$is_admin` variable is already assigned in both `add.php` (line 133) and `edit.php` (line 140).

### 4. Language strings

No new strings needed. `$lang['button_add_category']` already exists in all 17 language files and is reused.

## Behavior

- **Admin users:** See a "+ Add Category" link below the category dropdown. Clicking it reveals an inline form with text input and Save/Cancel buttons.
- **Non-admin users:** No change — no button is shown.
- **On save:** POST to `category.php` with CSRF token → on success, fetch JSON list → rebuild `<select>` → select the newly created category → hide form.
- **On error:** Show error message inline via `#categoryStatus`.
- **Cancel:** Hides the form, clears input.

## Edge Cases

- **Network failure:** Error message shown inline, form stays visible, user can retry.
- **Empty name:** Client-side validation prevents submission.
- **CSRF failure:** `add_json` returns 403 with JSON error message, shown inline.
- **Non-admin access to JSON endpoints:** Returns 403 with JSON error.
- **Slow response:** Status shows "Saving..." until complete.
