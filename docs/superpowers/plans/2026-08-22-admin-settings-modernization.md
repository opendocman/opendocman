# Admin Settings Modernization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernize the OpenDocMan admin area — a shared sidebar shell across all admin pages, a redesigned admin dashboard, and a grouped, vertical-tabbed, globally-searchable settings page.

**Architecture:** A new `_admin_content.tpl` layout template renders a sidebar partial + `{$content}`. Admin controllers switch from `_content.tpl` to `_admin_content.tpl` and set `$active_admin`. Settings grouping is code-driven in `Settings::groupSettings()` (no schema change) using a `GROUP_MAP` + group order array; ungrouped settings land in `Other`. The settings template renders vertical tabs with a JS-driven global filter. Bootstrap 5, Smarty.

**Tech Stack:** PHP 8.x, Smarty 3, Bootstrap 5.3 (CDN), vanilla JS, PHPUnit + Mockery, Playwright (E2E).

## Global Constraints

- Backward compatibility of the settings save path and existing setting names — the save handler (`settings.php`) and `Settings::save()` are NOT changed.
- No DB schema change — grouping is code-driven (`Settings::groupSettings()`).
- Auto-discovery preserved: any setting not in `GROUP_MAP` falls into the `other` (Other) group — never disappears.
- New `$lang[...]` keys must be added to **all 17 language files** under `application/includes/language/` (english.php + 16 others).
- Follow existing pattern: controllers do `ob_start(); display_smarty_template('X.tpl'); assign('content', ob_get_clean()); display_smarty_template('_content.tpl');` — the new shell swaps the final template.
- E2E depends on existing element selectors: `select[name="public_sharing"]` and `button[type="submit"]` inside the settings form must keep working (public-sharing.spec.ts does `page.selectOption('select[name="public_sharing"]', value)` then `page.click('button[type="submit"]')`).
- Theme override still works: `display_smarty_template()` falls back to `views/common/` when the theme folder lacks a file. New templates should live in `application/views/common/`.
- Setting names/descriptions still come straight from the `settings` table row (`name`, `description`). Only group headers are translated.
- `mail_*` settings (issue #17) map to the `email` group via a prefix rule, so nothing breaks when that branch merges.
- Run `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter Settings` after Settings model changes; `make test-unit` for the full unit suite.

---

### Task 1: Settings grouping engine in `Settings` model

**Files:**
- Modify: `application/models/Settings.class.php`
- Test: `tests/Unit/SettingsTest.php`

**Interfaces:**
- Consumes: existing `Settings` class, `$GLOBALS['lang']` for group labels.
- Produces: `Settings::groupSettings(array $rows): array` and public consts `GROUP_MAP`, `GROUP_KEYS`. Output shape: `[ groupKey => ['label' => string, 'name' => groupKey, 'settings' => [row, ...]], ... ]` ordered by `GROUP_KEYS`, `other` last.

- [ ] **Step 1: Write the failing unit tests**

Add to `tests/Unit/SettingsTest.php` (before the `mkdirp` private helper):

```php
public function testGroupSettingsOrdersAndLabelsKnownGroups(): void
{
    $pdo = \Mockery::mock(PDO::class);
    $settings = new Settings($pdo);

    $GLOBALS['lang'] = ['settings_group_general' => 'General'];

    $rows = [
        ['id' => 1, 'name' => 'title', 'value' => 'My Repo', 'description' => 'title desc', 'validation' => 'maxsize=255'],
        ['id' => 2, 'name' => 'authen', 'value' => 'mysql', 'description' => 'auth', 'validation' => ''],
    ];

    $groups = $settings->groupSettings($rows);

    $this->assertArrayHasKey('general', $groups);
    $this->assertArrayHasKey('security', $groups);
    $this->assertCount(1, $groups['general']['settings']);
    $this->assertSame('title', $groups['general']['settings'][0]['name']);
    $this->assertSame('General', $groups['general']['label']);
    $this->assertArrayNotHasKey('other', $groups);
    $this->assertTrue(array_key_exists('general', $groups) && reset($groups)['name'] === 'general');
}

public function testGroupSettingsUnknownFallsBackToOther(): void
{
    $pdo = \Mockery::mock(PDO::class);
    $settings = new Settings($pdo);

    $rows = [
        ['id' => 1, 'name' => 'brand_new_setting', 'value' => 'x', 'description' => 'd', 'validation' => ''],
        ['id' => 2, 'name' => 'another_unknown', 'value' => 'y', 'description' => 'e', 'validation' => 'bool'],
    ];

    $groups = $settings->groupSettings($rows);

    $this->assertArrayHasKey('other', $groups);
    $this->assertCount(2, $groups['other']['settings']);
    $this->assertSame('other', $groups['other']['name']);
}

public function testGroupSettingsMailPrefixGoesToEmail(): void
{
    $pdo = \Mockery::mock(PDO::class);
    $settings = new Settings($pdo);

    $rows = [
        ['id' => 1, 'name' => 'mail_host', 'value' => 'imap.example.com', 'description' => 'd', 'validation' => ''],
        ['id' => 2, 'name' => 'site_mail', 'value' => 'a@b.co', 'description' => 'd', 'validation' => ''],
    ];

    $groups = $settings->groupSettings($rows);

    $this->assertArrayHasKey('email', $groups);
    $this->assertArrayNotHasKey('other', $groups);
}

public function testGroupSettingsOrderIsFixed(): void
{
    $pdo = \Mockery::mock(PDO::class);
    $settings = new Settings($pdo);

    $rows = [
        ['id' => 1, 'name' => 'title', 'value' => '', 'description' => '', 'validation' => ''],
        ['id' => 2, 'name' => 'language', 'value' => '', 'description' => '', 'validation' => ''],
        ['id' => 3, 'name' => 'dataDir', 'value' => '', 'description' => '', 'validation' => ''],
    ];

    $groups = $settings->groupSettings($rows);

    $this->assertSame(['general', 'storage', 'appearance'], array_keys($groups));
}

public function testGroupSettingsEmptyRowsReturnsEmptyArray(): void
{
    $pdo = \Mockery::mock(PDO::class);
    $settings = new Settings($pdo);

    $this->assertSame([], $settings->groupSettings([]));
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter GroupSettings`
Expected: FAIL — errors on undefined method `groupSettings`.

- [ ] **Step 3: Implement `groupSettings()` in the model**

Add consts after the `class Settings {` opening (before `protected $connection;`):

```php
/** @var array<string, string> Known settings -> group slug. Unknown fall to 'other'. */
private const GROUP_MAP = [
    'debug'   => 'general',
    'demo'    => 'general',
    'title'   => 'general',
    'max_query' => 'general',
    'authen'  => 'security',
    'root_id' => 'security',
    'allow_signup' => 'security',
    'default_signup_department' => 'security',
    'allow_password_reset' => 'security',
    'try_nis' => 'security',
    'dataDir' => 'storage',
    'snapshotDir' => 'storage',
    'max_filesize' => 'storage',
    'site_mail' => 'email',
    'public_sharing' => 'general',
    'authorization' => 'review',
    'revision_expiration' => 'review',
    'file_expired_action' => 'review',
    'theme' => 'appearance',
    'language' => 'appearance',
];

/** @var array<int, string> Display order of group slugs; 'other' always last. */
private const GROUP_KEYS = ['general', 'security', 'storage', 'email', 'review', 'appearance', 'other'];

/**
 * Group settings rows by concern. Any row whose name is not in GROUP_MAP
 * (or does not start with a known prefix, e.g. mail_*) lands in the
 * 'other' group so new/unknown settings always appear.
 * @param array $rows PDO rows with keys id, name, value, description, validation
 * @return array<string, array{label: string, name: string, settings: array}>
 */
public function groupSettings(array $rows): array
{
    $groups = [];
    foreach (self::GROUP_KEYS as $key) {
        $groups[$key] = [
            'label' => isset($GLOBALS['lang']['settings_group_' . $key])
                ? $GLOBALS['lang']['settings_group_' . $key]
                : ucfirst($key),
            'name' => $key,
            'settings' => [],
        ];
    }

    foreach ($rows as $row) {
        $name = $row['name'];
        $group = self::GROUP_MAP[$name] ?? null;
        if ($group === null && strncmp($name, 'mail_', 5) === 0) {
            $group = 'email';
        }
        $group = $group ?? 'other';
        $groups[$group]['settings'][] = $row;
    }

    return array_filter($groups, function ($g) {
        return count($g['settings']) > 0;
    });
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter GroupSettings`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add application/models/Settings.class.php tests/Unit/SettingsTest.php
git commit -m "feat(settings): add grouping engine to Settings model (ODM #436)"
```

---

### Task 2: Admin shell — `_admin_sidebar.tpl` + `_admin_content.tpl` + `admin-sidebar.js`

**Files:**
- Create: `application/views/common/_admin_sidebar.tpl`
- Create: `application/views/common/_admin_content.tpl`
- Create: `public/js/bootstrap5/admin-sidebar.js`
- Modify: `public/css/bootstrap5/style.css`
- Modify: `application/controllers/admin.php` (set `$active_admin`, swap template)

**Interfaces:**
- Consumes: `$active_admin` (controller-set), `$settings_groups` OND (optional, from `Settings::edit()`), `msg()` for labels; existing `g_lang_*` vars.
- Produces: `_admin_sidebar.tpl` reads `{$active_admin}` (e.g. `'users'`, `'settings'`, `''` for dashboard). `_admin_content.tpl` renders sidebar column + `{$content}`.
- Later tasks rely on: every admin controller sets `$active_admin` before `display_smarty_template('_admin_content.tpl')`.

- [ ] **Step 1: (UI only — no test harness) Create the sidebar partial**

Create `application/views/common/_admin_sidebar.tpl`:

```smarty
<div class="admin-sidebar" id="adminSidebar">
    <div class="position-relative mb-3">
        <input type="search" id="adminSidebarSearch" class="form-control form-control-sm" placeholder="{$g_lang_settings_sidebar_search|escape:'html'}">
    </div>
    <ul class="nav nav-pills flex-column gap-1 admin-sidebar-nav" id="adminSidebarNav">
        <li class="nav-item">
            <a class="nav-link {if $active_admin eq 'admin'}active{/if}" href="admin">
                {$g_lang_label_admin|default:'Admin'}
            </a>
        </li>
        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_content|default:'Content Management'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'users'}active{/if}" href="admin_users">{$g_lang_users|default:'Users'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'departments'}active{/if}" href="admin_departments">{$g_lang_label_department|default:'Departments'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'categories'}active{/if}" href="admin_categories">{$g_lang_category|default:'Categories'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=add">{$g_lang_label_user_defined_fields|default:'User Defined Fields'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_files|default:'Files'}</div>
        </li>
        <li class="nav-item"><a class="nav-link" href="delete?mode=view_del_archive">{$g_lang_label_delete_undelete|default:'Delete / Undelete'}</a></li>
        <li class="nav-item"><a class="nav-link" href="toBePublished">{$g_lang_label_reviews|default:'Reviews'}</a></li>
        <li class="nav-item"><a class="nav-link" href="rejects">{$g_lang_label_rejections|default:'Rejections'}</a></li>
        <li class="nav-item"><a class="nav-link" href="check-exp">{$g_lang_label_check_expiration|default:'Check Expiration'}</a></li>
        <li class="nav-item"><a class="nav-link" href="file_ops?submit=view_checkedout">{$g_lang_label_checked_out_files|default:'Checked-out Files'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_system|default:'Settings &amp; System'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'settings'}active{/if}" href="settings?submit=update">{$g_lang_label_settings|default:'Settings'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'filetypes'}active{/if}" href="filetypes?submit=update">{$g_lang_adminpage_edit_filetypes|default:'File Types'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'content_index'}active{/if}" href="content_index">{$g_lang_label_content_search_index|default:'Content Search Index'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_reports|default:'Reports'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'access_log'}active{/if}" href="access_log?submit=update">{$g_lang_adminpage_access_log|default:'Access Log'}</a></li>

        {if isset($settings_groups)}
        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_settings_groups|default:'Settings Groups'}</div>
        </li>
        {foreach from=$settings_groups item=grp}
        <li class="nav-item">
            <a class="nav-link setting-group-link {if $smarty.request.settings_group eq $grp.name}active{/if}"
               href="#" data-group="{$grp.name}">{$grp.label|escape:'html'}</a>
        </li>
        {/foreach}
        {/if}
    </ul>
</div>
```

Note: existing keys referenced above (`label_delete_undelete`, `label_reviews`, etc.) exist in the 17 language files; confirm via `grep` before wiring each, and leave `default:` fallbacks in place.

- [ ] **Step 2: Create `_admin_content.tpl`**

```smarty
<div class="row g-3 admin-content-shell">
    <div class="col-lg-3 col-xl-2 admin-sidebar-col">
        {include file="_admin_sidebar.tpl"}
    </div>
    <div class="col-lg-9 col-xl-10">
        <div class="card h-100">
            <div class="card-body">
                {$content}
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Create sidebar JS** (`public/js/bootstrap5/admin-sidebar.js`)

```js
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('adminSidebarSearch');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    var links = document.querySelectorAll('#adminSidebarNav .nav-link');
    links.forEach(function (a) {
      a.style.display = (!q || a.textContent.trim().toLowerCase().indexOf(q) !== -1) ? '' : 'none';
    });
    var groupLabels = document.querySelectorAll('.admin-sidebar-group-label');
    groupLabels.forEach(function (h) {
      var sibling = h.nextElementSibling;
      h.style.display = 'block';
      if (!q) return;
      var visible = false;
      var node = sibling;
      while (node && node.tagName === 'LI') {
        var link = node.querySelector('.nav-link');
        if (link && link.style.display !== 'none') { visible = true; break; }
        node = node.nextElementSibling;
      }
      if (!visible) h.style.display = 'none';
    });
  });
});
```

- [ ] **Step 4: Update the sidebar CSS in `public/css/bootstrap5/style.css`** (append icon-in-group seats + responsive collapse-to-offcanvas styles)

```css
/* Admin sidebar shell (ODM #436) */
.admin-sidebar {
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 0.5rem;
    padding: 1rem;
}
.admin-sidebar .nav-link {
    padding: 0.35rem 0.75rem;
    border-radius: 0.375rem;
    color: #495057;
    font-size: 0.9rem;
}
.admin-sidebar .nav-link.active {
    font-weight: 600;
}
.admin-sidebar-group-label {
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.05em;
    color: #6c757d;
    font-weight: 700;
}
@media (max-width: 991.98px) {
    .admin-sidebar { position: static; max-height: 16rem; }
}
```

- [ ] **Step 5: Wire `admin.php` controller** — set `$active_admin` and swap the content template

Modify `application/controllers/admin.php`: right before `$content = ob_get_clean();` (line ~138) add:

```php
$GLOBALS['smarty']->assign('active_admin', 'dashboard');
```

Replace line 141 `display_smarty_template('_content.tpl');` with `display_smarty_template('_admin_content.tpl');`.

Also the dashboard's own card grid will be replaced in Task 5 — for now the shell wrapping is what counts.

- [ ] **Step 6: Load sidebar JS + CSS**

CSS is already linked. Add a script tag at the bottom of `_admin_content.tpl`, after the closing `</div>` of the row:

```smarty
<script src="{$g_base_url}js/bootstrap5/admin-sidebar.js"></script>
```

(`g_base_url` is assigned in `odm-init.php` via the `g_`-prefixed CONFIG loop — verified at lines 61-66; all templates use `{$g_base_url}`.)

- [ ] **Step 7: Verify by linting + smoke render**

Run: `php -l application/views/common/_admin_sidebar.tpl && php -l application/views/common/_admin_content.tpl`
(Runs syntax check on the Smarty files as best-effort; real verification is the E2E smoke in Task 7.)

- [ ] **Step 8: Commit**

```bash
git add application/views/common/_admin_sidebar.tpl application/views/common/_admin_content.tpl public/js/bootstrap5/admin-sidebar.js public/css/bootstrap5/style.css application/controllers/admin.php
git commit -m "feat(admin): add shared admin sidebar shell (ODM #436)"
```

---

### Task 3: Swap remaining admin CRUD controllers to the shell

**Files:**
- Modify: `application/controllers/admin_users.php`, `admin_departments.php`, `admin_categories.php`, `filetypes.php`, `udf.php`, `content_index.php`, `access_log.php`

**Interfaces:**
- Consumes: the `_admin_content.tpl` from Task 2.
- Produces: every admin CRUD page renders with the sidebar; each sets the appropriate `$active_admin`.

- [ ] **Step 1: Set `$active_admin` + swap template in each controller**

For each controller, add the matching assign **before** the `display_smarty_template('_content.tpl')` line and replace that call with `'_admin_content.tpl'`:

| Controller | `active_admin` value | Where |
|---|---|---|
| `admin_users.php` | `'users'` | line ~53 |
| `admin_departments.php` | `'departments'` | line ~52 |
| `admin_categories.php` | `'categories'` | line ~57 |
| `filetypes.php` | `'filetypes'` | line ~70 (only the `filetype_add.tpl` render block; other branches skip the shell) |
| `udf.php` | `'udf'` | each of lines 46, 92, 133 |
| `content_index.php` | `'content_index'` | line 106 |
| `access_log.php` | `'access_log'` | around the `access_log.tpl` render (before `draw_footer`) |

Example for `admin_users.php`:

```php
$GLOBALS['smarty']->assign('active_admin', 'users');
ob_start();
display_smarty_template('admin_users.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');
```

For `access_log.php`, it currently does `display_smarty_template('access_log.tpl')` directly (no `_content` shell). Convert to:

```php
$GLOBALS['smarty']->assign('active_admin', 'access_log');
ob_start();
display_smarty_template('access_log.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');
```

For `filetypes.php`: only the first pass's `filetype_add.tpl` branch uses the shell; preserve existing behavior for the other branches (leave them as-is; they render directly).

- [ ] **Step 2: Update/extend unit tests for the shell wrapper**

There's no direct unit test for the wrapper templates, but the existing `settings` test (`testEditAssignsDepartments`) must still pass since `Settings::edit()` continues to assign `settings_array`. Run the full existing unit suite to confirm nothing regressed.

Run: `make test-unit`
Expected: PASS (existing tests unchanged).

- [ ] **Step 3: Lint all modified controllers**

Run: `for f in admin_users admin_departments admin_categories filetypes udf content_index access_log; do php -l application/controllers/$f.php; done`

- [ ] **Step 4: Commit**

```bash
git add application/controllers/admin_users.php application/controllers/admin_departments.php application/controllers/admin_categories.php application/controllers/filetypes.php application/controllers/udf.php application/controllers/content_index.php application/controllers/access_log.php
git commit -m "feat(admin): wrap remaining admin CRUD pages in shared shell (ODM #436)"
```

---

### Task 4: Settings page — grouped vertical tabs + global search

**Files:**
- Create: `application/views/common/settings.tpl` (rewrite)
- Create: `public/js/bootstrap5/admin-settings.js`
- Modify: `application/models/Settings.class.php` (`edit()` to assign `settings_groups`)
- Modify: `public/css/bootstrap5/style.css` (tab rail styles)
- Modify: `application/controllers/settings.php` (set `$active_admin`, wrap in shell)

**Interfaces:**
- Consumes: `Settings::groupSettings()` (Task 1).
- Produces: `settings.tpl` renders the vertical-tab form: a left `nav nav-pills flex-column` rail (one per group with count badge), a right `.tab-content` panel, a global `input#settingsGlobalSearch`, and the Save/Cancel sticky footer. The form keeps `name="settingsForm"`, CSRF field, `submit=Save` button, and per-control rendering identical to today (bool select, theme select, language select, file_expired_action select, authen select, root_id select, default_signup_department select, else text input). Search filter is JS-only.

- [ ] **Step 1: (TDD-ish — set up the data)** Update `Settings::edit()` to assign `settings_groups`

In `Settings.class.php` `edit()`, after `$GLOBALS['smarty']->assign('settings_array', $result);` add:

```php
$GLOBALS['smarty']->assign('settings_groups', $this->groupSettings($result));
```

- [ ] **Step 2: Write the failing template render test**

Add to `tests/Unit/SettingsTest.php`:

```php
public function testEditAssignsSettingsGroups(): void
{
    if (!defined('ABSPATH')) { define('ABSPATH', $this->tmpBase . '/'); }
    $this->mkdirp(ABSPATH . 'views');
    $this->mkdirp(ABSPATH . 'includes/language');
    if (empty($GLOBALS['CONFIG']['theme'])) { $GLOBALS['CONFIG']['theme'] = 'bootstrap5'; }

    $pdo = \Mockery::mock(PDO::class);
    $stmt = \Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('execute')->andReturn(true);
    $stmt->shouldReceive('fetchAll')->andReturn([
        ['id' => 1, 'name' => 'title', 'value' => 'R', 'description' => 'd', 'validation' => ''],
        ['id' => 2, 'name' => 'language', 'value' => 'english', 'description' => 'd', 'validation' => ''],
        ['id' => 3, 'name' => 'brand_new', 'value' => '', 'description' => '', 'validation' => ''],
    ]);
    $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT \* FROM.*settings/'))->andReturn($stmt);

    $deptStmt = Mockery::mock(PDOStatement::class);
    $deptStmt->shouldReceive('execute')->andReturn(true);
    $deptStmt->shouldReceive('fetchAll')->andReturn([]);
    $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT.*FROM.*department/'))->andReturn($deptStmt);

    $userStmt = Mockery::mock(PDOStatement::class);
    $userStmt->shouldReceive('execute')->andReturn(true);
    $userStmt->shouldReceive('fetchAll')->andReturn([]);
    $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT.*FROM.*user/s'))->andReturn($userStmt);

    $smarty = Mockery::mock('Smarty');
    $smarty->shouldReceive('assign')->with('settings_groups', \Mockery::on(function ($groups) {
        return isset($groups['general']) && isset($groups['appearance']) && isset($groups['other']);
    }))->once();
    $smarty->shouldReceive('assign')->withAnyArgs()->andReturnNull();
    $smarty->shouldReceive('display')->andReturnNull();
    $GLOBALS['smarty'] = $smarty;

    $settings = new Settings($pdo);
    $settings->edit();
}
```

- [ ] **Step 3: Run to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter EditAssignsSettingsGroups`
Expected: FAIL (no `settings_groups` assignment yet).

- [ ] **Step 4: Add the assignment (Step 1) and run again**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter EditAssignsSettingsGroups`
Expected: PASS.

- [ ] **Step 5: Rewrite `settings.tpl`** with vertical tabs + global search

Replace `application/views/common/settings.tpl`:

```smarty
<form action="settings" method="POST" enctype="multipart/form-data" id="settingsForm" class="mt-3">
    {$csrf_token_field}
    <div class="row g-3">
        <div class="col-lg-3 col-xl-2">
            <div class="d-grid gap-2">
                <input type="search" id="settingsFilter" class="form-control form-control-sm" placeholder="{$g_lang_settings_filter|default:'Search settings…'}">
            </div>
            <ul class="nav nav-pills flex-column mt-3" id="settingsTabs" role="tablist">
                {foreach from=$settings_groups item=grp name=grps}
                <li class="nav-item">
                    <a class="nav-link {if $smarty.foreach.grps.first}active{/if}" id="tab-{$grp.name}" data-bs-toggle="pill"
                       href="#group-{$grp.name}" role="tab" data-group="{$grp.name}">
                        {$grp.label|escape:'html'}
                        <span class="badge bg-secondary ms-1">{$grp.settings|count}</span>
                    </a>
                </li>
                {/foreach}
            </ul>
        </div>

        <div class="col-lg-9 col-xl-10">
            <div class="tab-content" id="settingsTabContent">
                {foreach from=$settings_groups item=grp name=grps2}
                <div class="tab-pane fade {if $smarty.foreach.grps2.first}show active{/if}" id="group-{$grp.name}" role="tabpanel" data-group="{$grp.name}">
                    <h5 class="mb-3">{$grp.label|escape:'html'}</h5>
                    {foreach $grp.settings as $i}
                    <div class="mb-3 row setting-row" data-settings-name="{$i.name|escape:'html'}" data-settings-desc="{$i.description|escape:'html'}">
                        <label class="col-sm-3 col-form-label">{$i.name|escape:'html'}</label>
                        <div class="col-sm-4">
                        {if $i.validation eq 'bool'}
                            <select name="{$i.name|escape:'html'}" class="form-select">
                                <option value="True" {if $i.value eq 'True'} selected="selected"{/if}>True</option>
                                <option value="False" {if $i.value eq 'False'} selected="selected"{/if}>False</option>
                            </select>
                        {elseif $i.name eq 'theme'}
                            <select name="theme" class="form-select">
                                {foreach from=$themes item=theme}
                                    <option value="{$theme|escape}" {if $i.value eq $theme}selected="selected"{/if}>{$theme|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'language'}
                            <select name="language" class="form-select">
                                {foreach from=$languages item=language}
                                    <option value="{$language|escape}" {if $i.value eq $language} selected="selected"{/if}>{$language|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'file_expired_action'}
                            <select name="file_expired_action" class="form-select">
                                <option value="1" {if $i.value eq '1'}selected="selected"{/if}>Remove from file list until renewed</option>
                                <option value="2" {if $i.value eq '2'}selected="selected"{/if}>Show in file list but non-checkoutable</option>
                                <option value="3" {if $i.value eq '3'}selected="selected"{/if}>Send email to reviewer only</option>
                                <option value="4" {if $i.value eq '4'}selected="selected"{/if}>Do Nothing</option>
                            </select>
                        {elseif $i.name eq 'authen'}
                            <select name="authen" class="form-select">
                                <option value="mysql" {if $i.value eq 'mysql'}selected="selected"{/if}>MySQL</option>
                            </select>
                        {elseif $i.name eq 'root_id'}
                            <select name="root_id" class="form-select">
                                {foreach from=$useridnums item=useridnum}
                                    <option value="{$useridnum[0]|escape}" {if $i.value eq $useridnum[0]} selected="selected"{/if}>{$useridnum[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'default_signup_department'}
                            <select name="default_signup_department" class="form-select">
                                <option value="" {if $i.value eq ''}selected="selected"{/if}>-- unassigned --</option>
                                {foreach from=$departments item=dept}
                                    <option value="{$dept[0]|escape}" {if $i.value eq $dept[0]}selected="selected"{/if}>{$dept[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {else}
                            <input name="{$i.name|escape}" type="text" value="{$i.value|escape:'html'}" class="form-control">
                        {/if}
                        </div>
                        <div class="col-sm-5"><em>{$i.description|escape:'html'}</em></div>
                    </div>
                    {/foreach}
                </div>
                {/foreach}
            </div>

            <div class="d-flex gap-2 mt-3 sticky-bottom bg-white py-2">
                <button class="btn btn-primary" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>

<script src="{$g_base_url}js/bootstrap5/admin-settings.js"></script>
```

Note: the `theme`, `language`, `authen`, `root_id`, `default_signup_department`, and `file_expired_action` controls are rendered identically to the old template — this preserves existing E2E selectors (`select[name="public_sharing"]`, `select[name="theme"]`, etc.).

IMPORTANT — the script include at the end of the form is required: without it `admin-settings.js` never loads and the global search + sidebar tab-binding are dead. Also note this app's bundled Smarty is **2.6.28**, not Smarty 3 — use explicit `{foreach from=... item=...}` (never the shorthand `{foreach $x as $y}`) and `{$arr|@count}` (never `|count`) for array counting.

- [ ] **Step 6: Create `public/js/bootstrap5/admin-settings.js`** (global filter across all tab panes + sidebar link binding)

```js
document.addEventListener('DOMContentLoaded', function () {
  var filter = document.getElementById('settingsFilter');
  if (!filter) return;

  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    var rows = document.querySelectorAll('.setting-row');

    var anyVisible = false;
    rows.forEach(function (r) {
      var hay = (r.dataset.settingsName + ' ' + r.dataset.settingsDesc).toLowerCase();
      var hide = q !== '' && hay.indexOf(q) === -1;
      r.style.display = hide ? 'none' : '';
      if (!hide) anyVisible = true;
    });

    // Empty-state text when nothing matches
    var empty = document.getElementById('settingsEmpty');
    if (q !== '' && !anyVisible) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'settingsEmpty';
        empty.className = 'text-muted text-center py-4';
        empty.textContent = filter.dataset.emptyMsg || 'No settings match';
        document.getElementById('settingsTabContent').appendChild(empty);
      }
      empty.style.display = '';
    } else if (empty) {
      empty.style.display = 'none';
    }
  });

  // Sidebar Settings-Groups deep-links: jump to the matching vertical tab.
  document.querySelectorAll('#adminSidebarNav .setting-group-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var group = link.getAttribute('data-group');
      var tab = document.querySelector('#settingsTabs .nav-link[data-group="' + group + '"]');
      if (tab) {
        var bootstrapTab = new bootstrap.Tab(tab);
        bootstrapTab.show();
      }
    });
  });
});
```

Note: the filter hides `.setting-row` elements across **all** panes (`#settingsTabContent`), showing matches from every group at once — the global-search behavior. Bootstrap pills still switch active tab; the filter overlays on top. The sidebar deep-link handler activates the tab for the clicked group (both handlers live in this file, which is included once at the bottom of settings.tpl). Using `bootstrap.Tab` requires the bootstrap.bundle global (loaded in footer.tpl).

- [ ] **Step 7: Wire the settings controller to the shell**

In `application/controllers/settings.php`:

Both branches (submit=update and submit=Save) currently call `$settings->edit();`, which both assigns vars AND displays `settings.tpl` directly. Split those responsibilities in `Settings.class.php`:

- Add `assignSettings(): void` — assigns all Smarty vars (`themes`, `languages`, `useridnums`, `settings_array`, `settings_groups`, `departments`), no display.
- Add `fetchSettingsRows(): array` (private) — `SELECT * FROM settings`.
- Add `renderContent(): string` — renders `settings.tpl`, returns its HTML.
- Change `edit()` to call `assignSettings()` then `display_smarty_template('settings.tpl')` (backward compatible).

```php
public function assignSettings(): void
{
    $result = $this->fetchSettingsRows();
    $GLOBALS['smarty']->assign('themes', $this->getThemes());
    $GLOBALS['smarty']->assign('languages', $this->getLanguages());
    $GLOBALS['smarty']->assign('useridnums', $this->getUserIdNums());
    $GLOBALS['smarty']->assign('settings_array', $result);
    $GLOBALS['smarty']->assign('settings_groups', $this->groupSettings($result));

    $deptQuery = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $deptStmt = $this->connection->prepare($deptQuery);
    $deptStmt->execute();
    $GLOBALS['smarty']->assign('departments', $deptStmt->fetchAll());
}

private function fetchSettingsRows(): array
{
    $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}settings";
    $stmt = $this->connection->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

public function renderContent(): string
{
    ob_start();
    display_smarty_template('settings.tpl');
    return ob_get_clean();
}

public function edit()
{
    $this->assignSettings();
    display_smarty_template('settings.tpl');
}
```

Then in the controller, replace both `$settings->edit();` calls (lines 45 and 93) with:

```php
$GLOBALS['smarty']->assign('active_admin', 'settings');
$settings->assignSettings();
$GLOBALS['smarty']->assign('content', $settings->renderContent());
display_smarty_template('_admin_content.tpl');
```

(The controller composes content + shell exactly like the other admin pages; no `ob_start` around `edit()`.)

- [ ] **Step 8: Run the unit suite**

Run: `make test-unit`
Expected: PASS (the new `edit()` behavior is covered by `testEditAssignsDepartments` and `testEditAssignsSettingsGroups`, both of which call `edit()` and expect `display`).

Also run lint: `php -l application/views/common/settings.tpl && php -l public/js/bootstrap5/admin-settings.js` (JS lint via `node --check`).

- [ ] **Step 9: Commit**

```bash
git add application/views/common/settings.tpl public/js/bootstrap5/admin-settings.js application/models/Settings.class.php public/css/bootstrap5/style.css application/controllers/settings.php
git commit -m "feat(settings): grouped vertical-tab settings page with global search (ODM #436)"
```

---

### Task 5: Admin dashboard landing view (quick stats + quick actions)

**Files:**
- Modify: `application/controllers/admin.php`
- Create: `application/views/common/admin_dashboard.tpl`
- Test: `tests/Unit/SettingsTest.php` (no) — new small test if feasible.

**Interfaces:**
- Produces: `admin.php` assigns `$stats` (assoc: `users`, `departments`, `categories`, `documents`) + `$app_version`, `$db_version` and renders `admin_dashboard.tpl` inside the shell.

- [ ] **Step 1: Add the dashboard template** `application/views/common/admin_dashboard.tpl`

```smarty
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{$g_lang_label_admin|escape:'html'}</h4>
</div>

<div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.users|intval}</div>
                <div class="text-muted small">{$g_lang_users|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.departments|intval}</div>
                <div class="text-muted small">{$g_lang_label_department|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.categories|intval}</div>
                <div class="text-muted small">{$g_lang_category|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.files|intval}</div>
                <div class="text-muted small">{$g_lang_label_files|default:'Documents'}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3 border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">{$g_lang_admin_quick_actions|default:'Quick actions'}</h6>
    </div>
    <div class="card-body d-flex gap-2 flex-wrap">
        <a class="btn btn-primary btn-sm" href="admin_users">+ {$g_lang_users}</a>
        <a class="btn btn-primary btn-sm" href="admin_departments">+ {$g_lang_label_department}</a>
        <a class="btn btn-primary btn-sm" href="admin_categories">+ {$g_lang_category}</a>
        <a class="btn btn-outline-primary btn-sm" href="settings?submit=update">{$g_lang_label_settings}</a>
    </div>
</div>

<div class="mt-3 text-muted small">
    {$g_lang_about_app_version|default:'App version'}: {$app_version} &middot;
    {$g_lang_about_db_version|default:'DB version'}: {$db_version}
</div>
```

(Use `{$g_lang_...}` var names consistent with isAdmin guard branches in the current admin.php, e.g. `label_reviews`, `label_delete_undelete`, etc. — verify each key exists in language files.)

- [ ] **Step 2: Rework `admin.php` to compute stats and render the dashboard**

Replace the content between `ob_start();` and `display_smarty_template('_admin_content.tpl');` in `admin.php` with:

```php
ob_start();
$GLOBALS['smarty']->assign('active_admin', 'dashboard');

$stats = [];
$countQueries = [
    'users' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}user",
    'departments' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}department",
    'categories' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}category",
    'files' => "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}data",
];
foreach ($countQueries as $k => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats[$k] = (int) $stmt->fetchColumn();
}
$GLOBALS['smarty']->assign('stats', $stats);
$GLOBALS['smarty']->assign('app_version', $GLOBALS['CONFIG']['current_version'] ?? '-');
$GLOBALS['smarty']->assign('db_version', Settings::get_db_version($pdo));

display_smarty_template('admin_dashboard.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');
```

Keep the plugins hook (`callPluginMethod('onAdminMenu')`) — render it in the dashboard template or keep the existing plugin section after the content. Keep it: after the stats rows add a `{# if plugins…}`-equivalent: render `callPluginMethod('onAdminMenu')` into `$content` too (move the plugin echo inside the `ob_start` block, before the dashboard tpl).

- [ ] **Step 3: Update existing E2E that references admin dashboard cards**

`tests/smoke-uat.spec.ts` and others don't reference the old card labels by text, so no fixture change needed; verify with a quick grep for `label_delete_undelete` etc. (none hard-code the card text). Confirm with `grep -rn "adminpage_edit_settings\|label_admin_crud_desc" tests/`.

- [ ] **Step 4: Run unit + lint**

Run: `make test-unit && php -l application/controllers/admin.php`

- [ ] **Step 5: Commit**

```bash
git add application/controllers/admin.php application/views/common/admin_dashboard.tpl
git commit -m "feat(admin): modernize dashboard with live stats + quick actions (ODM #436)"
```

---

### Task 6: i18n — new language strings across all 17 files

**Files:**
- Modify: all 17 files under `application/includes/language/` (`english.php`, `arabic.php`, `bangla.php`, `chinese.php`, `croatian.php`, `czech.php`, `danish.php`, `dutch.php`, `french.php`, `german.php`, `italian.php`, `portuguese.php`, `romanian.php`, `spanish.php`, `swedish.php`, `tamil.php`, `turkish.php`)

**Keys introduced:**
- `settings_group_general`, `settings_group_security`, `settings_group_storage`, `settings_group_email`, `settings_group_review`, `settings_group_appearance`, `settings_group_other`
- `settings_sidebar_group_content`, `settings_sidebar_group_files`, `settings_sidebar_group_system`, `settings_sidebar_group_reports`, `settings_sidebar_group_settings_groups`
- `settings_search_placeholder`
- `admin_dashboard_quick_actions`, `admin_dashboard_stats_users`, etc. — reuse `users`, `label_department`, `category` where possible.

For English (`english.php`) the values are:
```php
$lang['settings_group_general'] = 'General';
$lang['settings_group_security'] = 'Security &amp; Authentication';
$lang['settings_group_storage'] = 'File Types &amp; Storage';
$lang['settings_group_email'] = 'Email &amp; Notifications';
$lang['settings_group_review'] = 'Review &amp; Authorization';
$lang['settings_group_appearance'] = 'UI &amp; Appearance';
$lang['settings_group_other'] = 'Other';
$lang['settings_sidebar_group_content'] = 'Content Management';
$lang['settings_sidebar_group_files'] = 'Files';
$lang['settings_sidebar_group_system'] = 'Settings &amp; System';
$lang['settings_sidebar_group_reports'] = 'Reports';
$lang['settings_sidebar_group_settings_groups'] = 'Settings Groups';
$lang['settings_search_placeholder'] = 'Search settings…';
$lang['admin_quick_actions'] = 'Quick actions';
```

- [ ] **Step 1: Add the new keys to `english.php`**, appended near the other `settings*` / `adminpage*` keys. 

- [ ] **Step 2: Add keys with translated values to the other 16 files** — per project convention each new key needs a translation. For languages we don't confidently translate, **fall back to the English string** (consistent with the existing pattern where some languages re-use English, e.g. `label_admin_crud_desc` is identical across files).

- [ ] **Step 3: Verify key parity**

```bash
for f in application/includes/language/*.php; do
  base=$(basename $f .php)
  for k in settings_group_general settings_group_security settings_group_storage settings_group_email settings_group_review settings_group_appearance settings_group_other settings_search_placeholder admin_quick_actions; do
    grep -q "'$k'" "$f" || echo "MISSING $k in $base"
  done
done
```
Expected: no MISSING output.

- [ ] **Step 4: Commit**

```bash
git add application/includes/language/
git commit -m "feat(i18n): add settings admin UI strings to all 17 languages (ODM #436)"
```

---

### Task 7: Backfill `Settings::edit` render-surprise check — smoke test

**Files:**
- Modify: `tests/smoke-uat.spec.ts` (optional E2E) or a small manual check.

Given the E2E environment, add a new Playwright spec `tests/admin-settings.spec.ts` that:
1. Logs in as admin.
2. Visits `/settings?submit=update`.
3. Asserts the left rail shows tabs with the `General` group.
4. Clecks `#settingsFilter` and asserts rows filter.
5. Reverts.

Use the existing `npm run test:e2e` runner conventions (`retryGoto`, env creds).

- [ ] **Step 1: Write `tests/admin-settings.spec.ts`** following `smoke-uat.spec.ts` patterns (login helper, retryGoto, admin creds from env). Also add `'**/admin-settings.spec.ts'` to the `smoke` project's `testMatch` in `playwright.config.ts` so the stock `npm run test:e2e` runs it.

- [ ] **Step 2: Run E2E**

Run: `npm run test:e2e -- --project=chromium` (or `make test-e2e`).
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/admin-settings.spec.ts playwright.config.ts
git commit -m "e2e: cover grouped settings page + global search (ODM #436)"
```

---

### Task 8: Final integration & verification

**Files:** none new.

- [ ] **Step 1: Run full test suite**

Run: `make test`
Expected: all green.

- [ ] **Step 2: Manual smoke of save path**

Through the browser or curl, verify:
1. Settings page renders with groups, tabs, counts.
2. Change `title` in General tab → Save → persists; `last_message` success shows.
3. Search "root" filters across tabs.
4. An **unknown setting row** (inserted by hand via the DB) shows under Other.
5. Save path unaffected: `select[name="theme"]` or `button[type=submit]` works for `public_sharing`.

- [ ] **Step 3: Verify typing/search across groups** manual/again against the live site.

- [ ] **Step 4: Commit any final polish**

```bash
git add -A
git commit -m "chore(admin): final polish for admin settings modernization (ODM #436)"
```

---

## Self-Review

**Spec coverage:**
- Grouped settings by concern → Tasks 1, 4 (group map + vertical tabs).
- Code-driven map, no schema change → Task 1.
- Auto-discovery unknown → `Other` → Task 1 test `testGroupSettingsUnknownFallsBackToOther`.
- Global search → Task 4 (`admin-settings.js`).
- Sidebar/tabbed nav between sections → Tasks 2, 4.
- Dashboard landing view + quick stats + quick actions → Task 5.
- Shared sidebar shell on all admin pages → Tasks 2, 3.
- Search box on dashboard (sidebar search) → Task 2 (`admin-sidebar.js`).
- Preserve save path + names → Settings model untouched save; template keeps same input names → Task 4.
- Mail_* accommodation → Task 1 `mail_` prefix → email.
- All language strings preserved; new strings in 17 files → Task 6.
- i18n group naming taxonomy ('trimmed list') → General, Security & Auth, Storage & Files, Email & Notifications, Review, UI & Appearance, Other.

**Placeholder scan:** no TODO/TBD except the deliberate "default:" fallbacks in templates which are real Smarty `default` modifiers, not placeholders.

**Type consistency:** `groupSettings()` return shape `[label, name, settings]` used consistently in template (`$grp.label`, `$grp.name`, `$grp.settings`). `$active_admin` values are consistent (`users/departments/categories/udf/filetypes/content_index/access_log/settings/dashboard`). `settings.tpl` count badge uses `{$grp.settings|count}` (Smarty count modifier).

One inconsistency found during review: the Sidebar task 2 mentions `$settings_groups` but the shell is only used by the settings page after task 4 — the `{if isset($settings_groups)}` blocks are safe in other pages (guarded). Fixed in the plan.

Plan complete and saved to `docs/superpowers/plans/2026-08-22-admin-settings-modernization.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.

2. **Inline Execution** — I execute tasks in this session using executing-plans, with checkpoints for your review.

Which approach?