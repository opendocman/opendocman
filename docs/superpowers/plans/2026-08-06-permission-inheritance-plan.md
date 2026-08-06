# Permission Inheritance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add category-level permission templates with live inheritance fallback and a dual-mode permission editor

**Architecture:** New `category_perms` table mirrors the existing `user_perms`/`dept_perms` pattern but scoped to categories. A shared JS component provides tabbed-per-level editing and an overview matrix. `getAuthority()` adds a category fallback step before returning 0. Category selection on add/edit pre-fills permissions via AJAX.

**Tech Stack:** PHP 8.x, MySQL/MyISAM, Smarty templates, jQuery, PHPUnit + Mockery, Playwright

---

### Task 1: Schema, Migration, and CategoryPerms Model

**Files:**
- Create: `application/installer/migrations/Version001700.php`
- Modify: `application/installer/SchemaBuilder.php` (add table definition)
- Modify: `application/version.php` (bump ODM_DB_VERSION)
- Modify: `tests/Unit/UserPermsTest.php` (add test file load for new table)
- Test: `tests/Unit/CategoryPermsTest.php`

**Interfaces:**
- Consumes: PDO connection, `$GLOBALS['CONFIG']['db_prefix']`
- Produces: `CategoryPerms` class with `getPermission(int $catId, ?int $userId, ?int $deptId): ?int`, `getTemplate(int $catId): array`, `saveTemplate(int $catId, array $perms): void`, `deleteTemplate(int $catId): void`

- [ ] **Step 1: Write failing CategoryPerms test**

Create `tests/Unit/CategoryPermsTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/CategoryPerms.class.php';

class CategoryPermsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
    }

    private function mockPdoForFetch(string $queryContains, array $fetchResult): PDO
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn($fetchResult);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(fn($q) => str_contains($q, $queryContains)))
            ->andReturn($stmt);
        return $pdo;
    }

    public function testGetPermissionReturnsRightsForDept(): void
    {
        $pdo = $this->mockPdoForFetch('category_perms', ['rights' => 2]);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, deptId: 3);
        $this->assertSame(2, $result);
    }

    public function testGetPermissionReturnsRightsForUser(): void
    {
        $pdo = $this->mockPdoForFetch('category_perms', ['rights' => 4]);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, userId: 10);
        $this->assertSame(4, $result);
    }

    public function testGetPermissionReturnsNullWhenNoRow(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, deptId: 99);
        $this->assertNull($result);
    }

    public function testGetTemplateReturnsAllPermsForCategory(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([
            ['dept_id' => '3', 'user_id' => null, 'rights' => '2'],
            ['dept_id' => null, 'user_id' => '10', 'rights' => '4'],
        ]);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $result = $model->getTemplate(5);
        $this->assertCount(2, $result);
        $this->assertSame(2, $result[0]['rights']);
    }

    public function testSaveTemplateReplacesAllRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->once()->with([
            ':cat_id' => 5, ':dept_id' => 3, ':user_id' => null, ':rights' => 2
        ])->andReturn(true);
        $pdo->shouldReceive('prepare')->twice()->andReturn($stmtDelete, $stmtInsert);
        $model = new CategoryPerms($pdo);
        $model->saveTemplate(5, [
            ['dept_id' => 3, 'user_id' => null, 'rights' => 2],
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist --filter CategoryPermsTest 2>&1 | tail -10
```
Expected: Class "CategoryPerms" not found

- [ ] **Step 3: Create CategoryPerms model**

Create `application/models/CategoryPerms.class.php`:

```php
<?php

class CategoryPerms
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = $GLOBALS['CONFIG']['db_prefix'] . 'category_perms';
    }

    public function getPermission(int $catId, ?int $userId = null, ?int $deptId = null): ?int
    {
        if ($userId !== null && $deptId !== null) {
            throw new InvalidArgumentException('Provide userId or deptId, not both');
        }
        $clauses = [];
        $params = [':cat_id' => $catId];
        if ($userId !== null) {
            $clauses[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        } elseif ($deptId !== null) {
            $clauses[] = 'dept_id = :dept_id';
            $params[':dept_id'] = $deptId;
        } else {
            throw new InvalidArgumentException('Either userId or deptId required');
        }
        $query = "SELECT rights FROM {$this->table} WHERE cat_id = :cat_id AND " . implode(' AND ', $clauses) . ' LIMIT 1';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (int)$row['rights'] : null;
    }

    public function getTemplate(int $catId): array
    {
        $query = "SELECT dept_id, user_id, rights FROM {$this->table} WHERE cat_id = :cat_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':cat_id' => $catId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveTemplate(int $catId, array $perms): void
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $this->pdo->prepare("DELETE FROM {$prefix}category_perms WHERE cat_id = :cat_id")
            ->execute([':cat_id' => $catId]);
        if (empty($perms)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$prefix}category_perms (cat_id, dept_id, user_id, rights) VALUES (:cat_id, :dept_id, :user_id, :rights)"
        );
        foreach ($perms as $perm) {
            $rights = (int)$perm['rights'];
            if ($rights === 0) {
                continue; // Never store "Unset"
            }
            $stmt->execute([
                ':cat_id' => $catId,
                ':dept_id' => $perm['dept_id'] ?? null,
                ':user_id' => $perm['user_id'] ?? null,
                ':rights' => $rights,
            ]);
        }
    }

    public function deleteTemplate(int $catId): void
    {
        $prefix = $GLOBALS['CONFIG']['db_prefix'];
        $this->pdo->prepare("DELETE FROM {$prefix}category_perms WHERE cat_id = :cat_id")
            ->execute([':cat_id' => $catId]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist --filter CategoryPermsTest 2>&1 | tail -10
```
Expected: OK (5 tests, 5 assertions)

- [ ] **Step 5: Add table to SchemaBuilder**

In `SchemaBuilder.php`, add the `category_perms` table definition after the `category` table (around line 30):

```php
"CREATE TABLE `{$prefix}category_perms` (
    cat_id int(11) unsigned NOT NULL,
    dept_id int(11) unsigned default NULL,
    user_id int(11) unsigned default NULL,
    rights tinyint(4) NOT NULL default '0',
    KEY cat_perms_idx (cat_id, dept_id, user_id),
    KEY cat_id (cat_id),
    KEY dept_id (dept_id),
    KEY user_id (user_id)
) ENGINE = MYISAM",
```

- [ ] **Step 6: Create migration Version001700.php**

Create `application/installer/migrations/Version001700.php`:

```php
<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001700 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.0';
    }

    public function getDescription(): string
    {
        return 'Add category_perms table for permission inheritance templates';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "CREATE TABLE `{$prefix}category_perms` (
                cat_id int(11) unsigned NOT NULL,
                dept_id int(11) unsigned default NULL,
                user_id int(11) unsigned default NULL,
                rights tinyint(4) NOT NULL default '0',
                KEY cat_perms_idx (cat_id, dept_id, user_id),
                KEY cat_id (cat_id),
                KEY dept_id (dept_id),
                KEY user_id (user_id)
            ) ENGINE = MYISAM"
        );
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}category_perms`");
    }
}
```

- [ ] **Step 7: Bump ODM_DB_VERSION in version.php**

Change `application/version.php` line 21:
```php
const ODM_DB_VERSION = '1.7.0';
```

- [ ] **Step 8: Regenerate database.sql**

```bash
make dump-sql
```

- [ ] **Step 9: Run all existing tests to verify no regression**

```bash
make test-quiet
```
Expected: All tests pass

- [ ] **Step 10: Commit**

```bash
git add application/installer/SchemaBuilder.php \
       application/installer/migrations/Version001700.php \
       application/version.php \
       application/models/CategoryPerms.class.php \
       tests/Unit/CategoryPermsTest.php \
       database.sql
git commit -m "feat: add category_perms table and CategoryPerms model"
```

---

### Task 2: Permission Editor JavaScript Component

**Files:**
- Create: `public/js/permissions-editor.js`
- Modify: `application/views/common/_filePermissions.tpl`

**Interfaces:**
- Consumes: JSON shape from `category.php?submit=get_perms_json` — `{dept_perms: {deptId: rights, ...}, user_perms: {userId: rights, ...}}`
- Produces: jQuery plugin `$('#permissionsEditor').permissionsEditor(options)` with methods `loadTemplate(templateData)`, `getData()` (returns `{department_permission, user_permission}` in form-submit format)

- [ ] **Step 1: Write the permissions-editor.js file**

Create `public/js/permissions-editor.js`:

```javascript
/**
 * Permissions Editor — dual mode component
 *
 * Edit mode: tab-per-level additive selection
 * Overview mode: full matrix with checkmark/X indicators
 *
 * Usage:
 *   $('#perms').permissionsEditor({ departments: [...], users: [...] });
 *   $('#perms').permissionsEditor('loadTemplate', { dept_perms: {...}, user_perms: {...} });
 *   var data = $('#perms').permissionsEditor('getData');
 */
(function ($) {
    var RIGHT_LABELS = { '-1': 'Forbidden', '1': 'View', '2': 'Read', '3': 'Write', '4': 'Admin' };
    var RIGHT_ORDER = ['view', 'read', 'write', 'admin', 'forbidden'];
    var RIGHT_VALUES = { forbidden: -1, view: 1, read: 2, write: 3, admin: 4 };

    function PermissionsEditor(el, options) {
        this.$el = $(el);
        this.departments = options.departments || [];
        this.users = options.users || [];
        this.state = { dept_perms: {}, user_perms: {} };
        this.render();
    }

    PermissionsEditor.prototype.render = function () {
        var self = this;
        var html = '';

        // Mode toggle
        html += '<div class="mb-2">';
        html += '  <div class="btn-group btn-group-sm" role="group">';
        html += '    <button type="button" class="btn btn-primary perm-mode-btn" data-mode="edit">Edit</button>';
        html += '    <button type="button" class="btn btn-outline-secondary perm-mode-btn" data-mode="overview">Overview</button>';
        html += '  </div>';
        html += '</div>';

        // Edit mode container
        html += '<div class="perm-edit-mode">';
        html += '  <ul class="nav nav-tabs small" role="tablist">';
        $.each(RIGHT_ORDER, function (i, level) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <li class="nav-item" role="presentation">';
            html += '      <button class="nav-link' + (i === 0 ? ' active' : '') + '" data-bs-toggle="tab" data-target="#perm-tab-' + level + '" type="button" role="tab">' + label + '</button>';
            html += '    </li>';
        });
        html += '  </ul>';
        html += '  <div class="tab-content border border-top-0 p-2" style="max-height:300px; overflow-y:auto;">';
        $.each(RIGHT_ORDER, function (ti, level) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <div class="tab-pane' + (ti === 0 ? ' show active' : '') + '" id="perm-tab-' + level + '" role="tabpanel">';
            html += '      <div class="perm-assigned-list" data-level="' + level + '"></div>';
            html += '      <div class="mt-1"><select class="form-select form-select-sm perm-add-select" data-level="' + level + '" style="width:auto;display:inline-block;"><option value="">+ Add...</option></select></div>';
            html += '    </div>';
        });
        html += '  </div>';
        html += '</div>';

        // Overview mode container
        html += '<div class="perm-overview-mode" style="display:none;">';
        html += '  <div class="table-responsive" style="max-height:300px; overflow-y:auto;">';
        html += '    <table class="table table-sm table-striped mb-0">';
        html += '      <thead class="table-light"><tr><th>Name</th><th>Type</th>';
        $.each(RIGHT_ORDER, function (_, level) {
            html += '<th class="text-center">' + RIGHT_LABELS[RIGHT_VALUES[level]] + '</th>';
        });
        html += '      </tr></thead><tbody class="perm-overview-body"></tbody></table>';
        html += '  </div>';
        html += '</div>';

        self.$el.html(html);
        self.bindEvents();
        self.refreshAll();
    };

    PermissionsEditor.prototype.bindEvents = function () {
        var self = this;
        self.$el.off('.perms');

        // Mode toggle
        self.$el.on('click.perms', '.perm-mode-btn', function () {
            var mode = $(this).data('mode');
            self.$el.find('.perm-mode-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
            $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
            if (mode === 'edit') {
                self.$el.find('.perm-edit-mode').show();
                self.$el.find('.perm-overview-mode').hide();
            } else {
                self.$el.find('.perm-edit-mode').hide();
                self.$el.find('.perm-overview-mode').show();
                self.renderOverview();
            }
        });

        // Add from dropdown
        self.$el.on('change.perms', '.perm-add-select', function () {
            var val = $(this).val();
            if (!val) return;
            var level = $(this).data('level');
            var parts = val.split(':');
            var type = parts[0], id = parseInt(parts[1]);
            var rightVal = RIGHT_VALUES[level];
            if (type === 'dept') {
                self.state.dept_perms[id] = rightVal;
            } else {
                self.state.user_perms[id] = rightVal;
            }
            self.refreshTab(level);
            $(this).val('');
        });

        // Remove button
        self.$el.on('click.perms', '.perm-remove-btn', function () {
            var type = $(this).data('type');
            var id = parseInt($(this).data('id'));
            var level = $(this).data('level');
            if (type === 'dept') {
                delete self.state.dept_perms[id];
            } else {
                delete self.state.user_perms[id];
            }
            self.refreshTab(level);
        });

        // Overview click navigates to edit tab
        self.$el.on('click.perms', '.perm-overview-body td', function () {
            var level = $(this).data('level');
            if (!level) return;
            self.$el.find('.perm-mode-btn[data-mode="edit"]').click();
            self.$el.find('[data-target="#perm-tab-' + level + '"]').tab('show');
        });
    };

    PermissionsEditor.prototype.refreshTab = function (level) {
        var self = this;
        var rightVal = RIGHT_VALUES[level];
        var assigned = [];

        // Collect dept assignments
        $.each(self.state.dept_perms, function (id, rights) {
            if (rights === rightVal) {
                var dept = self.findDept(parseInt(id));
                if (dept) assigned.push({ type: 'dept', id: id, name: dept.name });
            }
        });
        // Collect user assignments
        $.each(self.state.user_perms, function (id, rights) {
            if (rights === rightVal) {
                var user = self.findUser(parseInt(id));
                if (user) assigned.push({ type: 'user', id: id, name: user.last_name + ', ' + user.first_name });
            }
        });

        var listEl = self.$el.find('.perm-assigned-list[data-level="' + level + '"]');
        if (assigned.length === 0) {
            listEl.html('<div class="text-muted small p-1">None assigned</div>');
        } else {
            var html = '';
            $.each(assigned, function (_, item) {
                var badge = item.type === 'dept' ? 'secondary' : 'info';
                html += '<span class="badge bg-' + badge + ' me-1 mb-1">';
                html += item.name;
                html += ' <a href="#" class="text-white text-decoration-none perm-remove-btn" data-type="' + item.type + '" data-id="' + item.id + '" data-level="' + level + '">&times;</a>';
                html += '</span> ';
            });
            listEl.html(html);
        }

        // Update add dropdown — exclude already assigned items at THIS level
        var selectEl = self.$el.find('.perm-add-select[data-level="' + level + '"]');
        var currentOptions = '<option value="">+ Add...</option>';
        $.each(self.departments, function (_, dept) {
            if (!self.state.dept_perms[dept.id] || self.state.dept_perms[dept.id] !== rightVal) {
                currentOptions += '<option value="dept:' + dept.id + '">[Dept] ' + dept.name + '</option>';
            }
        });
        $.each(self.users, function (_, user) {
            if (!self.state.user_perms[user.id] || self.state.user_perms[user.id] !== rightVal) {
                currentOptions += '<option value="user:' + user.id + '">[User] ' + user.last_name + ', ' + user.first_name + '</option>';
            }
        });
        selectEl.html(currentOptions);
    };

    PermissionsEditor.prototype.refreshAll = function () {
        var self = this;
        $.each(RIGHT_ORDER, function (_, level) {
            self.refreshTab(level);
        });
    };

    PermissionsEditor.prototype.renderOverview = function () {
        var self = this;
        var html = '';
        var allItems = [];

        $.each(self.departments, function (_, d) {
            allItems.push({ id: d.id, name: d.name, type: 'dept' });
        });
        $.each(self.users, function (_, u) {
            allItems.push({ id: u.id, name: u.last_name + ', ' + u.first_name, type: 'user' });
        });

        $.each(allItems, function (_, item) {
            html += '<tr>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="badge bg-' + (item.type === 'dept' ? 'secondary' : 'info') + '">' + item.type + '</span></td>';
            $.each(RIGHT_ORDER, function (_, level) {
                var rightVal = RIGHT_VALUES[level];
                var perms = item.type === 'dept' ? self.state.dept_perms : self.state.user_perms;
                var set = perms[item.id] === rightVal;
                html += '<td class="text-center" data-level="' + level + '" style="cursor:pointer;">';
                html += set ? '<span class="text-success fw-bold">&#10003;</span>' : '<span class="text-muted">&#9679;</span>';
                html += '</td>';
            });
            html += '</tr>';
        });
        self.$el.find('.perm-overview-body').html(html || '<tr><td colspan="7" class="text-muted">No permissions set</td></tr>');
    };

    PermissionsEditor.prototype.findDept = function (id) {
        var found = null;
        $.each(this.departments, function (_, d) { if (d.id === id) { found = d; return false; } });
        return found;
    };

    PermissionsEditor.prototype.findUser = function (id) {
        var found = null;
        $.each(this.users, function (_, u) { if (u.id === id) { found = u; return false; } });
        return found;
    };

    PermissionsEditor.prototype.loadTemplate = function (data) {
        this.state.dept_perms = data.dept_perms || {};
        // Convert string keys to int
        var deptPerms = {};
        $.each(this.state.dept_perms, function (k, v) { deptPerms[parseInt(k)] = v; });
        this.state.dept_perms = deptPerms;
        var userPerms = {};
        $.each(data.user_perms || {}, function (k, v) { userPerms[parseInt(k)] = v; });
        this.state.user_perms = userPerms;
        this.refreshAll();
    };

    PermissionsEditor.prototype.getData = function () {
        var deptPerm = {};
        $.each(this.state.dept_perms, function (id, rights) { deptPerm[id] = rights; });
        var userPerm = {};
        $.each(this.state.user_perms, function (id, rights) { userPerm[id] = rights; });
        return { department_permission: deptPerm, user_permission: userPerm };
    };

    // jQuery plugin
    $.fn.permissionsEditor = function (methodOrOptions) {
        if (typeof methodOrOptions === 'string') {
            var instance = this.data('permsEditor');
            if (instance && instance[methodOrOptions]) {
                return instance[methodOrOptions].apply(instance, Array.prototype.slice.call(arguments, 1));
            }
        } else {
            var options = methodOrOptions || {};
            if (!this.data('permsEditor')) {
                this.data('permsEditor', new PermissionsEditor(this, options));
            }
        }
        return this;
    };
})(jQuery);
```

- [ ] **Step 2: Update _filePermissions.tpl to use the new component**

Rewrite `application/views/common/_filePermissions.tpl`:

```smarty
<div id="permissionsEditor" class="w-50">
    <p class="text-muted small mb-2">{$g_lang_filepermissionspage_edit_department_permissions}</p>
</div>

<script src="js/permissions-editor.js"></script>
<script>
$(document).ready(function () {
    var departments = [
        {foreach from=$avail_depts item=dept}
        {id: {$dept.id}, name: '{$dept.name|escape:'javascript'}'},
        {/foreach}
    ];
    var users = [
        {foreach from=$avail_users item=user}
        {id: {$user.id}, last_name: '{$user.last_name|escape:'javascript'}', first_name: '{$user.first_name|escape:'javascript'}'},
        {/foreach}
    ];
    $('#permissionsEditor').permissionsEditor({ departments: departments, users: users });

    {if isset($dept_perms) || isset($user_perms)}
    $('#permissionsEditor').permissionsEditor('loadTemplate', {
        dept_perms: {json_encode($dept_perms ?? [])},
        user_perms: {json_encode($user_perms ?? [])}
    });
    {/if}
});
</script>
```

- [ ] **Step 3: Load the add.php and edit.php in browser, verify permissions editor renders**
- [ ] **Step 4: Commit**

```bash
git add public/js/permissions-editor.js application/views/common/_filePermissions.tpl
git commit -m "feat: add dual-mode permission editor component"
```

---

### Task 3: Category Template Admin (Controller + Views)

**Files:**
- Modify: `application/controllers/category.php`
- Modify: `application/views/admin/category_add.tpl`
- Modify: `application/views/admin/category_update.tpl`
- Test: `tests/Unit/CategoryTemplateControllerTest.php`

**Interfaces:**
- Consumes: `CategoryPerms::saveTemplate()`, `CategoryPerms::getTemplate()`
- Produces: AJAX endpoint `category.php?submit=get_perms_json&cat_id=X` returning JSON
- Produces: Category add/update forms with permission editor embedded

- [ ] **Step 1: Write failing controller test**

Create `tests/Unit/CategoryTemplateControllerTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class CategoryTemplateControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetPermsJsonReturnsEmptyWhenNoPerms(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('getTemplate')->with(1)->andReturn([]);
        $result = $catPerms->getTemplate(1);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPermsJsonReturnsDeptAndUserPerms(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('getTemplate')->with(2)->andReturn([
            ['dept_id' => '3', 'user_id' => null, 'rights' => '1'],
            ['dept_id' => null, 'user_id' => '10', 'rights' => '4'],
        ]);
        $rows = $catPerms->getTemplate(2);
        $deptPerms = [];
        $userPerms = [];
        foreach ($rows as $row) {
            $rights = (int)$row['rights'];
            if ($row['dept_id'] !== null) {
                $deptPerms[(int)$row['dept_id']] = $rights;
            } elseif ($row['user_id'] !== null) {
                $userPerms[(int)$row['user_id']] = $rights;
            }
        }
        $this->assertEquals([3 => 1], $deptPerms);
        $this->assertEquals([10 => 4], $userPerms);
    }

    public function testSaveTemplateSkipsZeroRights(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('saveTemplate')->with(1, \Mockery::on(function ($perms) {
            foreach ($perms as $p) {
                if ((int)$p['rights'] === 0) {
                    return false;
                }
            }
            return true;
        }))->once();
        $catPerms->saveTemplate(1, [['dept_id' => 3, 'user_id' => null, 'rights' => 2]]);
    }

    public function testSaveTemplateOnlyStoresNonZeroRights(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 1])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->once()->with([
            ':cat_id' => 1, ':dept_id' => 3, ':user_id' => null, ':rights' => 2
        ])->andReturn(true);
        $pdo->shouldReceive('prepare')->twice()->andReturn($stmtDelete, $stmtInsert);

        $model = new CategoryPerms($pdo);
        $model->saveTemplate(1, [
            ['dept_id' => 3, 'user_id' => null, 'rights' => 2],  // stored
            ['dept_id' => 4, 'user_id' => null, 'rights' => 0],  // skipped
        ]);
    }
}
```

- [ ] **Step 2: Add get_perms_json AJAX endpoint to category.php**

Add before the `cancel` handler (before line 422):

```php
} elseif (isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'get_perms_json') {
    if (!$user_obj->isAdmin()) {
        header('Content-Type: application/json');
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $catId = (int)$_REQUEST['cat_id'];
    $catPerms = new CategoryPerms($pdo);
    $rows = $catPerms->getTemplate($catId);
    $deptPerms = [];
    $userPerms = [];
    foreach ($rows as $row) {
        $rights = (int)$row['rights'];
        if ($row['dept_id'] !== null) {
            $deptPerms[(int)$row['dept_id']] = $rights;
        } elseif ($row['user_id'] !== null) {
            $userPerms[(int)$row['user_id']] = $rights;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['dept_perms' => $deptPerms, 'user_perms' => $userPerms]);
    exit;
```

- [ ] **Step 3: Add save handler for category permissions on category create**

In the `Add Category` handler (around line 73), insert after the INSERT query (after line 87):

```php
// Save category permission template if provided
if (isset($_POST['department_permission']) || isset($_POST['user_permission'])) {
    $catPerms = new CategoryPerms($pdo);
    $perms = [];
    if (isset($_POST['department_permission'])) {
        foreach ($_POST['department_permission'] as $deptId => $rights) {
            $perms[] = ['dept_id' => (int)$deptId, 'user_id' => null, 'rights' => (int)$rights];
        }
    }
    if (isset($_POST['user_permission'])) {
        foreach ($_POST['user_permission'] as $userId => $rights) {
            $perms[] = ['dept_id' => null, 'user_id' => (int)$userId, 'rights' => (int)$rights];
        }
    }
    $catPerms->saveTemplate((int)$pdo->lastInsertId(), $perms);
}
```

- [ ] **Step 4: Add save handler for category update**

In the `updatecategory` handler (around line 399), insert after the UPDATE query (after line 417):

```php
// Save category permission template
$catPerms = new CategoryPerms($pdo);
$perms = [];
if (isset($_POST['department_permission'])) {
    foreach ($_POST['department_permission'] as $deptId => $rights) {
        $perms[] = ['dept_id' => (int)$deptId, 'user_id' => null, 'rights' => (int)$rights];
    }
}
if (isset($_POST['user_permission'])) {
    foreach ($_POST['user_permission'] as $userId => $rights) {
        $perms[] = ['dept_id' => null, 'user_id' => (int)$userId, 'rights' => (int)$rights];
    }
}
$catPerms->saveTemplate($id, $perms);
```

- [ ] **Step 5: Delete category_perms on category delete**

In the `deletecategory` handler (around line 157), insert after the DELETE query (after line 174):

```php
// Remove category permission template
$catPerms = new CategoryPerms($pdo);
$catPerms->deleteTemplate((int)$_REQUEST['id']);
```

- [ ] **Step 6: Add permission editor to category add form**

In the `add` handler (after line 62, before the closing `</form>`), insert:

```html
<hr>
<h6>Default permissions for documents in this category (optional)</h6>
<div id="categoryPermsEditor"></div>
```

And add the JS init block after the existing `<script>` block (after line 67):

```html
<script src="js/permissions-editor.js"></script>
<script>
$(document).ready(function () {
    var departments = <?php echo json_encode($avail_depts ?? []); ?>;
    var users = <?php echo json_encode($avail_users ?? []); ?>;
    $('#categoryPermsEditor').permissionsEditor({ departments: departments, users: users });
});
</script>
```

The controller also needs to load `$avail_depts` and `$avail_users` before rendering the add form. Add near the top of the `add` handler (after line 42):

```php
$avail_depts_query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
$avail_depts_stmt = $pdo->prepare($avail_depts_query);
$avail_depts_stmt->execute();
$avail_depts = $avail_depts_stmt->fetchAll(PDO::FETCH_ASSOC);

$avail_users_query = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name, first_name";
$avail_users_stmt = $pdo->prepare($avail_users_query);
$avail_users_stmt->execute();
$avail_users = $avail_users_stmt->fetchAll(PDO::FETCH_ASSOC);
```

- [ ] **Step 7: Add permission editor to category update form**

In the `Update` handler (after line 338, before the closing `</form>` at line 350), insert:

```html
<hr>
<h6>Default permissions for documents in this category (optional)</h6>
<div id="categoryPermsEditor"></div>
```

And add the JS init block after the existing `<script>` block (after line 355):

```html
<script src="js/permissions-editor.js"></script>
<script>
$(document).ready(function () {
    var departments = <?php echo json_encode($avail_depts ?? []); ?>;
    var users = <?php echo json_encode($avail_users ?? []); ?>;
    $('#categoryPermsEditor').permissionsEditor({ departments: departments, users: users });
    // Load existing template
    $.getJSON('category?submit=get_perms_json&cat_id=<?php echo (int)$_REQUEST['item']; ?>', function (data) {
        $('#categoryPermsEditor').permissionsEditor('loadTemplate', data);
    });
});
</script>
```

Also need `$avail_depts` and `$avail_users` loaded for the Update view. Add after line 317:

```php
$avail_depts_query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
$avail_depts_stmt = $pdo->prepare($avail_depts_query);
$avail_depts_stmt->execute();
$avail_depts = $avail_depts_stmt->fetchAll(PDO::FETCH_ASSOC);

$avail_users_query = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name, first_name";
$avail_users_stmt = $pdo->prepare($avail_users_query);
$avail_users_stmt->execute();
$avail_users = $avail_users_stmt->fetchAll(PDO::FETCH_ASSOC);
```

- [ ] **Step 8: Verify in browser — navigate to admin category add/update, confirm the permission editor loads and works**
- [ ] **Step 9: Commit**

```bash
git add application/controllers/category.php tests/Unit/CategoryTemplateControllerTest.php
git commit -m "feat: add category permission template admin UI and AJAX endpoint"
```

---

### Task 4: Live Inheritance in getAuthority()

**Files:**
- Modify: `application/models/UserPermission.class.php`
- Modify: `application/controllers/file_list_ajax.php` (batch loading for category perms)
- Modify: `tests/Unit/UserPermissionOrchestratorTest.php`
- Test: `tests/Unit/UserPermissionInheritanceTest.php`

**Interfaces:**
- Consumes: `CategoryPerms::getPermission()`
- Modifies: `UserPermission::getAuthority(int $data_id): int` — adds category fallback
- Modifies: `file_list_ajax.php` — adds step to batch-load category perms

- [ ] **Step 1: Write failing inheritance test**

Create `tests/Unit/UserPermissionInheritanceTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/UserPermission.class.php';

class UserPermissionInheritanceTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
    }

    private function makePermission(int $uid, $pdo, $mockUser, $mockUP, $mockDP, $mockCP): UserPermission
    {
        $up = new UserPermission($uid, $pdo);
        $up->user_obj = $mockUser;
        $up->user_perms_obj = $mockUP;
        $up->dept_perms_obj = $mockDP;
        $up->category_perms_obj = $mockCP;
        return $up;
    }

    public function testGetAuthorityFallsBackToCategoryWhenNoDocPerms(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtStub = \Mockery::mock(\PDOStatement::class);
        $stmtStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();
        $pdo->shouldReceive('prepare')->andReturn($stmtStub)->zeroOrMoreTimes();

        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn(10)->byDefault();
        $mockUser->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $mockUser->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false)->byDefault();

        // No doc-level perms — both return -999 (no row)
        $mockUP = \Mockery::mock(User_Perms::class)->makePartial();
        $mockUP->FORBIDDEN_RIGHT = -1; $mockUP->NONE_RIGHT = 0;
        $mockUP->VIEW_RIGHT = 1; $mockUP->READ_RIGHT = 2;
        $mockUP->WRITE_RIGHT = 3; $mockUP->ADMIN_RIGHT = 4;
        $mockUP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        $mockDP = \Mockery::mock(Dept_Perms::class)->makePartial();
        $mockDP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        // Category has view right for user's dept
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->with(5, null, 3)->andReturn(1);
        $mockCP->shouldReceive('getPermission')->with(5, 10, null)->andReturn(null);
        $mockCP->shouldReceive('getPermission')->with(5, 10)->andReturn(null);

        // Mock FileData constructor DB queries: findName + loadData
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 5, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Admin check -> isAdmin() => false
        $stmtAdmin = \Mockery::mock(\PDOStatement::class);
        $stmtAdmin->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtAdmin->shouldReceive('fetchColumn')->once()->andReturn(0);
        $stmtAdmin->shouldReceive('rowCount')->once()->andReturn(1);

        // isReviewerForFile -> false
        $stmtReviewer = \Mockery::mock(\PDOStatement::class);
        $stmtReviewer->shouldReceive('execute')->once()->with([':user_id' => 10, ':file_id' => 42])->andReturn(true);
        $stmtReviewer->shouldReceive('rowCount')->once()->andReturn(0);

        // User constructor queries
        $stmtUFind = \Mockery::mock(\PDOStatement::class);
        $stmtUFind->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUFind->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtUFind->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtURow = \Mockery::mock(\PDOStatement::class);
        $stmtURow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtURow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        $pdo->shouldReceive('prepare')->andReturn(
            $stmtUFind, $stmtURow, // User constructor
            $stmtFindName, $stmtLoad, // FileData constructor
            $stmtAdmin, // isAdmin check
            $stmtReviewer, // isReviewerForFile check
            $stmtStub, $stmtStub, $stmtStub, $stmtStub, $stmtStub, $stmtStub // User_Perms/Dept_Perms constructors
        )->zeroOrMoreTimes();

        $mockUP = \Mockery::mock(User_Perms::class)->makePartial();
        $mockUP->FORBIDDEN_RIGHT = -1; $mockUP->NONE_RIGHT = 0;
        $mockUP->VIEW_RIGHT = 1; $mockUP->READ_RIGHT = 2;
        $mockUP->WRITE_RIGHT = 3; $mockUP->ADMIN_RIGHT = 4;
        $mockUP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        $mockDP = \Mockery::mock(Dept_Perms::class)->makePartial();
        $mockDP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->with(5, null, 3)->andReturn(1); // dept fallback
        $mockCP->shouldReceive('getPermission')->with(5, 10, null)->andReturn(null); // user fallback

        $up = new UserPermission(10, $pdo);
        $up->user_obj = $mockUser;
        $up->user_perms_obj = $mockUP;
        $up->dept_perms_obj = $mockDP;
        $up->category_perms_obj = $mockCP;

        $this->assertSame(1, $up->getAuthority(42), 'Should fall back to category dept perms when no doc-level perms');
    }
}
```

- [ ] **Step 2: Update UserPermission.class.php — add category_perms_obj and modify getAuthority**

Add property after line 29 (near `$dept_perms_obj`):

```php
public $category_perms_obj;
```

In the constructor (around line 88, after `$this->dept_perms_obj`), add:

```php
$this->category_perms_obj = new CategoryPerms($this->connection);
```

Modify `getAuthority()` method (lines 264-291) — the fallback logic after department check:

```php
// Before the final return, add category fallback
if ($department_permissions >= 0 && $department_permissions <= 4) {
    return $department_permissions;
}

// Category fallback
$catId = $fileData->getCategory();
if ($catId > 0) {
    $catUserPerm = $this->category_perms_obj->getPermission($catId, userId: $this->uid);
    if ($catUserPerm !== null) {
        return $catUserPerm;
    }
    $catDeptPerm = $this->category_perms_obj->getPermission($catId, deptId: $this->user_obj->getDeptId());
    if ($catDeptPerm !== null) {
        return $catDeptPerm;
    }
}

return 0;
```

The full updated `getAuthority()` method becomes:

```php
public function getAuthority($data_id)
{
    $data_id = (int) $data_id;
    $fileData = new FileData($data_id, $this->connection);

    if ($this->user_obj === null) {
        error_log("UserPermission::getAuthority - user_obj is null for UID: " . $this->uid);
        return $this->FORBIDDEN_RIGHT;
    }

    if ($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id)) {
        return $this->ADMIN_RIGHT;
    }

    if ($fileData->isOwner($this->uid) && $fileData->isLocked()) {
        return $this->WRITE_RIGHT;
    }

    $user_permissions = $this->user_perms_obj->getPermission($data_id);
    $department_permissions = $this->dept_perms_obj->getPermission($data_id);

    if ($user_permissions >= $this->user_perms_obj->NONE_RIGHT && $user_permissions <= $this->user_perms_obj->ADMIN_RIGHT) {
        return $user_permissions;
    }

    if ($department_permissions >= 0 && $department_permissions <= 4) {
        return $department_permissions;
    }

    // Category fallback
    $catId = $fileData->getCategory();
    if ($catId > 0) {
        $catUserPerm = $this->category_perms_obj->getPermission($catId, userId: $this->uid);
        if ($catUserPerm !== null) {
            return $catUserPerm;
        }
        $catDeptPerm = $this->category_perms_obj->getPermission($catId, deptId: $this->user_obj->getDeptId());
        if ($catDeptPerm !== null) {
            return $catDeptPerm;
        }
    }

    return 0;
}
```

- [ ] **Step 3: Run existing tests to verify no regression**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UserPermission 2>&1 | tail -20
```
Expected: All existing tests pass

- [ ] **Step 4: Update file_list_ajax.php with batch category permissions**

After the department permissions batch load (Step 6, around line 277), add:

```php
// Step 6.5: Batch load category permissions for fallback
$query = "
    SELECT d.id AS fid, cp.dept_id, cp.user_id, cp.rights
    FROM {$db_prefix}data d
    JOIN {$db_prefix}category_perms cp ON cp.cat_id = d.category
    WHERE d.id IN ($in_placeholders)
      AND (cp.user_id = ? OR (cp.user_id IS NULL AND cp.dept_id = ?))
";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($page_params, [$_SESSION['uid'], $user_obj->getDeptId()]));
$category_perms_map = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fid = (int)$row['fid'];
    if (!isset($category_perms_map[$fid])) {
        $category_perms_map[$fid] = [];
    }
    if ($row['user_id'] !== null) {
        $category_perms_map[$fid]['user'] = (int)$row['rights'];
    } else {
        $category_perms_map[$fid]['dept'] = (int)$row['rights'];
    }
}
```

Update the permission resolution block (around line 302-313) to include category fallback:

```php
} else {
    $access = isset($user_perms_map[$fileid]) ? $user_perms_map[$fileid] : -999;
    if ($access >= 0 && $access <= 4) {
        $userAccessLevel = $access;
    } else {
        $deptAccess = isset($dept_perms_map[$fileid]) ? $dept_perms_map[$fileid] : -999;
        if ($deptAccess >= 0 && $deptAccess <= 4) {
            $userAccessLevel = $deptAccess;
        } else {
            // Category fallback
            if (isset($category_perms_map[$fileid]['user'])) {
                $userAccessLevel = $category_perms_map[$fileid]['user'];
            } elseif (isset($category_perms_map[$fileid]['dept'])) {
                $userAccessLevel = $category_perms_map[$fileid]['dept'];
            } else {
                $userAccessLevel = 0;
            }
        }
    }
}
```

- [ ] **Step 5: Verify no regression in file listing**

Start the dev server and browse to a folder with files that have no explicit permissions but belong to a category with a template. Verify they appear.
```bash
php -S localhost:8080 -t public/
```

- [ ] **Step 6: Commit**

```bash
git add application/models/UserPermission.class.php \
       application/controllers/file_list_ajax.php \
       tests/Unit/UserPermissionInheritanceTest.php
git commit -m "feat: add category fallback to getAuthority() and file listing"
```

---

### Task 5: Add/Edit Page Integration

**Files:**
- Modify: `application/controllers/add.php`
- Modify: `application/controllers/edit.php`
- Modify: `application/views/common/add.tpl`
- Modify: `application/views/common/edit.tpl`
- Modify: `application/controllers/helpers/functions.php` (maybe — for shared permission loading logic)
- Test: `tests/Integration/CategoryPermissionFlowTest.php`

- [ ] **Step 1: Write failing integration test**

Create `tests/Integration/CategoryPermissionFlowTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class CategoryPermissionFlowTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
    }

    public function testFullFlowWithMockedPdo(): void
    {
        // Mock PDO to return a category with template, then verify getAuthority fallback
        $stmtStub = \Mockery::mock(\PDOStatement::class);
        $stmtStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtStub)->zeroOrMoreTimes();

        // Create CategoryPerms with real class but stub PDO
        $catPerms = new CategoryPerms($pdo);
        $template = $catPerms->getTemplate(1);
        $this->assertIsArray($template);
    }
}
```

- [ ] **Step 2: Update add.php — pass department/user data to template, wire AJAX**

In `application/controllers/add.php`, after loading categories (around line 113-136), add:

```php
// Load departments and users for permission editor
$deptQuery = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
$deptStmt = $pdo->prepare($deptQuery);
$deptStmt->execute();
$GLOBALS['smarty']->assign('avail_depts', $deptStmt->fetchAll(PDO::FETCH_ASSOC));

$userQuery = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name, first_name";
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute();
$GLOBALS['smarty']->assign('avail_users', $userStmt->fetchAll(PDO::FETCH_ASSOC));
```

- [ ] **Step 3: Update add.tpl — add category-change JS**

After the category `<select>` element in `add.tpl`:

```html
<script>
$(document).ready(function () {
    $('select[name="category"]').on('change', function () {
        var catId = $(this).val();
        if (!catId) return;
        $.getJSON('category?submit=get_perms_json&cat_id=' + catId, function (data) {
            $('#permissionsEditor').permissionsEditor('loadTemplate', data);
        });
    });
});
</script>
```

- [ ] **Step 4: Update edit.php — load existing permissions for the document**

In `edit.php`, after loading categories (around line 107-146), add the same department/user loading as in Task 5 Step 2.

Also load existing document-level permissions to pre-populate the editor:

```php
// Load existing document-level permissions
$deptPermQuery = "SELECT dept_id, rights FROM {$GLOBALS['CONFIG']['db_prefix']}dept_perms WHERE fid = :fid";
$deptPermStmt = $pdo->prepare($deptPermQuery);
$deptPermStmt->execute([':fid' => $id]);
$deptPerms = [];
while ($row = $deptPermStmt->fetch(PDO::FETCH_ASSOC)) {
    $deptPerms[(int)$row['dept_id']] = (int)$row['rights'];
}

$userPermQuery = "SELECT uid, rights FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE fid = :fid";
$userPermStmt = $pdo->prepare($userPermQuery);
$userPermStmt->execute([':fid' => $id]);
$userPerms = [];
while ($row = $userPermStmt->fetch(PDO::FETCH_ASSOC)) {
    $userPerms[(int)$row['uid']] = (int)$row['rights'];
}

$GLOBALS['smarty']->assign('dept_perms_json', json_encode($deptPerms));
$GLOBALS['smarty']->assign('user_perms_json', json_encode($userPerms));
```

- [ ] **Step 5: Update edit.tpl — load existing perms + category-change handler**

```html
<script>
$(document).ready(function () {
    // Load existing document permissions
    var deptPerms = {$dept_perms_json|default:'{}'};
    var userPerms = {$user_perms_json|default:'{}'};
    $('#permissionsEditor').permissionsEditor('loadTemplate', {
        dept_perms: deptPerms,
        user_perms: userPerms
    });

    // On category change, re-fetch template and overwrite
    $('select[name="category"]').on('change', function () {
        var catId = $(this).val();
        if (!catId) return;
        $.getJSON('category?submit=get_perms_json&cat_id=' + catId, function (data) {
            $('#permissionsEditor').permissionsEditor('loadTemplate', data);
        });
    });
});
</script>
```

- [ ] **Step 6: Update add.php and edit.php form submission to read from the editor**

The form already submits `department_permission[dept_id]` and `user_permission[user_id]` as radio button values. The new editor's `getData()` returns the same format, so the existing save logic in `edit.php` (lines 223-278) and `add.php` (lines 340-358) works unchanged.

- [ ] **Step 7: Verify in browser — create a category with template, then add a file in that category, verify permissions pre-fill**
- [ ] **Step 8: Verify category change — edit a file, change its category, verify permissions re-fill**
- [ ] **Step 9: Commit**

```bash
git add application/controllers/add.php application/controllers/edit.php \
       application/views/common/add.tpl application/views/common/edit.tpl \
       tests/Integration/CategoryPermissionFlowTest.php
git commit -m "feat: wire category template pre-fill into add/edit document pages"
```

---

### Task 6: Update Existing Tests for Inheritance

**Files:**
- Modify: `tests/Unit/UserPermissionOrchestratorTest.php`

- [ ] **Step 1: Update UserPermissionOrchestratorTest**

Add a `category_perms_obj` mock to the setup methods. In `testGetAuthorityReturnsAdminForAdminUser`, `testGetAuthorityReturnsAdminForReviewerOfFile`, `testGetAuthorityReturnsWriteWhenOwnerAndLocked`, `testGetAuthorityPrefersUserPermissionWithinBounds` — inject a mock `CategoryPerms` that returns null so the new fallback path doesn't interfere.

Add after line 29 (near `$dpOver` usage):

```php
$mockCP = \Mockery::mock(CategoryPerms::class);
$mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
```

And in the `$up` injection block, after `$up->dept_perms_obj = $mockDP;`:

```php
$up->category_perms_obj = $mockCP;
```

- [ ] **Step 2: Run all tests to verify**

```bash
make test-quiet
```
Expected: All tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/UserPermissionOrchestratorTest.php
git commit -m "test: adapt existing tests for category permission fallback"
```

---

### Task 7: E2E Test Update

**Files:**
- Modify: `tests/smoke-uat.spec.ts`

- [ ] **Step 1: Add Playwright test for permission inheritance**

In `tests/smoke-uat.spec.ts`, add a new test block after the existing tests:

```typescript
test('admin can set category permission template and it pre-fills on add file', async ({ page }) => {
  // Login
  await retryGoto(page, '/');
  await page.fill('input[name="username"]', process.env.ADMIN_USER || 'admin');
  await page.fill('input[name="password"]', process.env.ADMIN_PASSWORD || 'password');
  await page.click('button[type="submit"]');

  // Navigate to admin -> categories -> add
  await retryGoto(page, '/admin');
  await page.click('a[href*="category?submit=add"]');

  // Create a category with a name
  const catName = 'Test-Category-' + Date.now();
  await page.fill('input[name="category"]', catName);

  // Verify "Unset" label appears (not "None")
  await expect(page.locator('text=Unset')).toBeVisible();
  await expect(page.locator('text=None')).toHaveCount(0);

  // Go to add file page
  await retryGoto(page, '/add');
  await page.selectOption('select[name="category"]', { label: catName });

  // Verify the permissions editor loaded (the container exists)
  await expect(page.locator('#permissionsEditor')).toBeVisible();
});

test('permission inheritance falls back to category perms in file listing', async ({ page }) => {
  // Login as admin
  await retryGoto(page, '/');
  await page.fill('input[name="username"]', process.env.ADMIN_USER || 'admin');
  await page.fill('input[name="password"]', process.env.ADMIN_PASSWORD || 'password');
  await page.click('button[type="submit"]');

  // Navigate to out.php (file listing)
  await retryGoto(page, '/out');

  // Verify page loads without error
  await expect(page.locator('body')).toBeVisible();
});
```

- [ ] **Step 2: Run E2E tests**

```bash
npm run test:e2e
```
Expected: All tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/smoke-uat.spec.ts
git commit -m "test: add E2E tests for permission inheritance"
```

---

### Task 8: Run make dump-sql and Final Verification

**Files:**
- Modify: `database.sql` (regenerated)

- [ ] **Step 1: Regenerate database.sql**

```bash
make dump-sql
```

- [ ] **Step 2: Run all tests**

```bash
make test-quiet
```
Expected: All tests pass

- [ ] **Step 3: Run E2E test**

```bash
npm run test:e2e
```
Expected: All tests pass

- [ ] **Step 4: Final commit**

```bash
git add database.sql
git commit -m "chore: regenerate database.sql with category_perms table"
```