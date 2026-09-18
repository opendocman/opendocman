# UDF Management List with Delete — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give admins a management page listing existing UDF fields with a per-row Delete action (reusing the existing confirm + delete flow), and remove the dead legacy `udf_admin_menu()`/`udf_admin_header()` code.

**Architecture:** A new `udf?submit=manage` route in `udf.php` lists all fields from `odm_udf` into a Smarty template. Each row has a Delete button in a tiny POST form carrying the CSRF token, posting to the existing confirm route (`udf?submit=delete&item=…`), which then runs the existing `udf_functions_delete_udf()` teardown. The admin sidebar link retargets from `udf?submit=add` to `udf?submit=manage`. Two dead functions in `udf_functions.php` are deleted.

**Tech Stack:** PHP 8.4+, Smarty templates (Bootstrap 5), PHPUnit + Mockery, Aura HTML escaper.

## Global Constraints

- PHP floor `>= 8.4` — no legacy syntax.
- All dynamic output must be escaped with `e::h()` in PHP or `|escape:'html'` in Smarty.
- POST is REQUIRED for delete: the existing confirm route (`udf?submit=delete&item=…`) validates CSRF from `$_POST`; a GET link fails with `FormPostException` (missing index/token).
- `{$csrf_token_field}` is already assigned globally in `odm-init.php:87` — no new CSRF plumbing needed in templates.
- Do NOT change the delete backend, confirm flow, add form, or edit forms.
- No DB schema changes. No new translation strings (use existing `$g_lang_*` keys).
- Do NOT modify `application/templates_c/.gitignore` (spurious delete — restore if present).
- The E2E suite has 2 known pre-existing failures unrelated to this work.

---

### Task 1: Add the `manage` route and template with per-row delete

**Files:**
- Modify: `application/controllers/udf.php` (add branch before the `submit == 'add'` branch or after it, within the `elseif` chain)
- Create: `application/views/common/udf/manage.tpl`
- Test: `tests/Unit/UdfFunctionsTest.php` (add a manage-list rendering test)

**Interfaces:**
- Produces: route `udf?submit=manage` (GET) → renders `udf/manage.tpl` with Smarty var `udfs` (array of `['id','table_name','display_name','field_type']`) and `active_admin = 'udf'`.
- Consumes: existing route `udf?submit=delete&item=<table_name>` (POST + CSRF) → confirm page → `udf_functions_delete_udf()`.

- [ ] **Step 1: Write the failing test for the manage-list rendering**

In `tests/Unit/UdfFunctionsTest.php`, add:

```php
    public function testManageTemplateRendersDeleteFormPerRow(): void
    {
        $rows = [
            ['id' => 1, 'table_name' => 'odm_udftbl_alpha', 'display_name' => 'Alpha', 'field_type' => 3],
            ['id' => 2, 'table_name' => 'odm_udftbl_beta_primary', 'display_name' => 'Beta', 'field_type' => 4],
        ];
        ob_start();
        // Render the Smarty template directly with the same variable the
        // controller assigns. The template is compiled by Smarty at runtime;
        // the controller normally assigns 'udfs', 'active_admin', 'csrf_token_field'.
        $GLOBALS['smarty']->assign('udfs', $rows);
        $GLOBALS['smarty']->assign('active_admin', 'udf');
        $GLOBALS['smarty']->assign('csrf_token_field', '');
        display_smarty_template('udf/manage.tpl');
        $output = ob_get_clean();

        $this->assertStringContainsString('Alpha', $output);
        $this->assertStringContainsString('Beta', $output);
        $this->assertStringContainsString('name="submit" value="delete"', $output);
        $this->assertStringContainsString('name="item" value="odm_udftbl_alpha"', $output);
        $this->assertStringContainsString('href="udf?submit=add"', $output);
    }
```

(Adjust `display_smarty_template` call signature to match how other tests call it, if needed — check `tests/` for an existing Smarty-template test. If none exists and the template cannot be rendered in the test harness, instead assert the template file contains the required form fields — see note in Step 2.)

- [ ] **Step 2: Run the test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: FAIL — `manage.tpl` does not exist yet (Smarty error or file-not-found), or the assertions fail.

**If the template cannot be rendered in the unit harness** (no existing Smarty template test in the repo): fall back to a static-content test that reads the template file:

```php
    public function testManageTemplateContainsDeleteForm(): void
    {
        $tpl = file_get_contents(APPLICATION_PATH . '/views/common/udf/manage.tpl');
        $this->assertStringContainsString('name="submit" value="delete"', $tpl);
        $this->assertStringContainsString('name="item" value="{$item.table_name|escape:\'html\'}"', $tpl);
        $this->assertStringContainsString('{$csrf_token_field}', $tpl);
        $this->assertStringContainsString('udf?submit=add', $tpl);
    }
```

Pick whichever approach actually works in this repo's test harness; do NOT skip testing.

- [ ] **Step 3: Create `udf/manage.tpl`**

Create `application/views/common/udf/manage.tpl`:

```smarty
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{$g_lang_label_user_defined_fields}</h5>
        <div class="d-flex gap-2 mb-3">
            <a class="btn btn-primary" href="udf?submit=add">{$g_lang_label_add}&nbsp;{$g_lang_label_user_defined_field}</a>
        </div>
        {if empty($udfs)}
            <p class="text-muted">{$g_lang_message_udf_cannot_be_blank}</p>
        {else}
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>{$g_lang_label_display}</th>
                        <th>{$g_lang_type}</th>
                        <th>{$g_lang_label_name}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$udfs item=item}
                    <tr>
                        <td>{$item.display_name|escape:'html'}</td>
                        <td>{$item.field_type|escape:'html'}</td>
                        <td><code>{$item.table_name|escape:'html'}</code></td>
                        <td>
                            <form action="udf" method="POST" class="d-inline">
                                {$csrf_token_field}
                                <input type="hidden" name="submit" value="delete">
                                <input type="hidden" name="item" value="{$item.table_name|escape:'html'}">
                                <button type="submit" class="btn btn-danger btn-sm">{$g_lang_label_delete}</button>
                            </form>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        {/if}
    </div>
</div>
```

Notes:
- `$g_lang_type` is the "Type" label; `$g_lang_message_udf_cannot_be_blank` is an existing "cannot be blank" message used as the empty-list placeholder — if it reads oddly for an empty list, verify what key fits best from `application/includes/language/english.php` and use that instead. Do NOT add new language keys.
- The empty-list `<p>` uses a translation key for the no-records case; prefer an existing key that reads like an empty state (check `english.php` for e.g. a "no results"/"none" message). If none fits, render an empty `<tbody>` with no message — the list itself is self-explanatory. Keep it simple.

- [ ] **Step 4: Add the `manage` route to `udf.php`**

In `application/controllers/udf.php`, add a new `elseif` branch. Place it right AFTER the `submit == 'add'` branch (line 48) and BEFORE the `submit == 'Add User Defined Field'` branch:

```php
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'manage') {
    draw_header(msg('label_user_defined_fields'), $last_message);

    $query = "
      SELECT
        id,
        table_name,
        display_name,
        field_type
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}udf
      ORDER BY
        id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array());
    $result = $stmt->fetchAll();

    $GLOBALS['smarty']->assign('udfs', $result);
    $GLOBALS['smarty']->assign('active_admin', 'udf');
    ob_start();
    display_smarty_template('udf/manage.tpl');
    $GLOBALS['smarty']->assign('content', ob_get_clean());
    display_smarty_template('_admin_content.tpl');
    draw_footer();
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: PASS. If you used the static-file test (Step 2 fallback), it passes once the template exists.

- [ ] **Step 6: Run the full unit/integration suite**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist`
Expected: green (no regressions; baseline 499 tests).

- [ ] **Step 7: Commit**

```bash
git add application/controllers/udf.php application/views/common/udf/manage.tpl tests/Unit/UdfFunctionsTest.php
git commit -m "feat: add UDF management list with per-row delete"
```

---

### Task 2: Retarget the admin sidebar link

**Files:**
- Modify: `application/views/common/_admin_sidebar.tpl` (line 17)

**Interfaces:**
- Consumes: the `udf?submit=manage` route from Task 1.

- [ ] **Step 1: Change the UDF sidebar link**

In `application/views/common/_admin_sidebar.tpl:17`, change:

```smarty
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=add">{$g_lang_label_user_defined_fields|default:'User Defined Fields'}</a></li>
```

to:

```smarty
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=manage">{$g_lang_label_user_defined_fields|default:'User Defined Fields'}</a></li>
```

- [ ] **Step 2: Verify no other link points to `udf?submit=add` as the primary admin entry**

Run: `grep -rn "udf?submit=add" application/ --include="*.tpl" --include="*.php" | grep -v templates_c`
Expected: only the "Add New UDF" button inside `manage.tpl` (the management page's add entry) references `udf?submit=add`. The sidebar no longer does.

- [ ] **Step 3: Commit**

```bash
git add application/views/common/_admin_sidebar.tpl
git commit -m "feat: point admin sidebar UDF link at the management list"
```

---

### Task 3: Remove dead legacy `udf_admin_menu()` / `udf_admin_header()`

**Files:**
- Modify: `application/controllers/helpers/udf_functions.php` (delete lines ~483-504)

**Interfaces:**
- Consumes: nothing (both functions have ZERO call sites — verified).

- [ ] **Step 1: Delete the two dead functions**

In `application/controllers/helpers/udf_functions.php`, delete these two function definitions entirely (the block starting `function udf_admin_header()` and ending at the closing `}` of `udf_admin_menu()`):

```php
    function udf_admin_header()
    {
        echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' .msg('label_user_defined_fields'). '</font></th>';
    }

    function udf_admin_menu()
    {
        global $pdo;

        echo '<td valign=top><table border=0>';
        echo '<tr><td><b><a href="udf?submit=add&state=' . (e::h($_REQUEST['state'] + 1)).'">' .msg('label_add'). '</a></b></td></tr>';
        echo '<tr><td><b><a href="udf?submit=deletepick&state=' . (e::h($_REQUEST['state'] + 1)).'">' .msg('label_delete'). '</a></b></td></tr>';
        echo '<tr><td><hr></td></tr>';
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            echo '<tr><td><b><a href="udf?submit=edit&udf='. e::h($row[0]) .'&state=' . (e::h($_REQUEST['state'] + 1)).'">'. e::h($row[2]) .'</a></b></td></tr>';
        }
        echo '</table></td>';
    }
```

(Line numbers may have shifted from earlier tasks — locate by function name, not line number.)

- [ ] **Step 2: Confirm zero remaining call sites**

Run: `grep -rn "udf_admin_menu\|udf_admin_header" application/ --include="*.php" | grep -v vendor | grep -v templates_c`
Expected: no output (both are gone; nothing references them).

- [ ] **Step 3: Run the full test suite**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist`
Expected: green (baseline 499 tests — the removal affects no tests since the functions were never called).

- [ ] **Step 4: Lint the changed file**

Run: `php application/vendor/bin/phplint application/controllers/helpers/udf_functions.php`
Expected: `OK! (Files: 1, Success: 1)`.

- [ ] **Step 5: Commit**

```bash
git add application/controllers/helpers/udf_functions.php
git commit -m "refactor: remove dead udf_admin_menu and udf_admin_header functions"
```

---

### Task 4: End-to-end verification

**Files:** none

- [ ] **Step 1: Run the full PHPUnit suite**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist`
Expected: green.

- [ ] **Step 2: Lint all changed PHP files**

Run: `php application/vendor/bin/phplint application/controllers/udf.php application/controllers/helpers/udf_functions.php tests/Unit/UdfFunctionsTest.php`
Expected: `OK! (Files: 3, Success: 3)`.

- [ ] **Step 3: Manual browser check — management list renders**

With the app running (dev server on :8080, DB has the `asda` UDF field from earlier work):
- Log in as admin/admin → Admin → "User Defined Fields" in the sidebar.
- Expected: the management list page renders, showing the `asda` field (display name, type 4, table name) and an "Add" button.

- [ ] **Step 4: Manual browser check — delete works**

- On the management list, click Delete next to `asda`.
- Expected: the existing confirm page shows the field name with Yes/No.
- Click Yes.
- Expected: redirected to admin with "successfully deleted" message; `asda` no longer in `odm_udf`; its helper tables (`odm_udftbl_asdf_primary`, `odm_udftbl_asdf_secondary`) dropped.

- [ ] **Step 5: Confirm git state is clean of spurious changes**

Run: `git status --short`
Expected: no `application/templates_c/.gitignore` deletion. If present, restore it (`git restore application/templates_c/.gitignore`) and do NOT commit it.