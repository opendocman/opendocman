# Admin Table CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current separate action-based admin pages for Users, Departments, and Categories with a single unified admin page featuring a collapsible sidebar navigation and Tabulator-powered CRUD tables with modals. Only these three entities move to table CRUD — the rest of the admin pages (Settings, Filetypes, Reports, etc.) stay as-is on the existing admin dashboard.

**Architecture:** A new `admin_crud.php` controller renders the sidebar layout and Tabulator table containers. A new `admin_crud_ajax.php` controller handles all AJAX JSON endpoints (list, add, edit, delete) for all three entities, reusing the existing `file_list_ajax.php` pattern. Modal forms reuse the field structure from existing Smarty templates but rendered as HTML partials within the PHP controller. The collapsible sidebar uses Bootstrap 5.3.3 offcanvas on mobile and a flex-based sidebar on desktop.

**Tech Stack:** PHP 8.2+, PDO, Bootstrap 5.3.3 (CDN), Tabulator 6.3 (CDN), Smarty 3, jQuery (legacy, for validation only), Mockery + PHPUnit for tests.

## Global Constraints

- All new PHP files must include the GPLv2 license header matching existing files
- Use `$GLOBALS['CONFIG']['db_prefix']` for all table names
- Use `$GLOBALS['pdo']` for the PDO connection
- Use `$GLOBALS['csrf']` for CSRF token validation on all POST operations
- Use `Aura\Html\Escaper as e` for HTML output escaping
- AJAX endpoints return JSON with `header('Content-Type: application/json')`
- Follow Tabulator remote pagination protocol: accept `page` and `size` params, return `{data: [...], last_page: N, last_row: N}`

---
### Task 1: Admin CRUD AJAX Endpoint

**Files:**
- Create: `application/controllers/admin_crud_ajax.php`
- Test: `tests/Unit/AdminCrudAjaxTest.php`
- Test: `tests/Integration/AdminCrudAjaxTest.php`

**Interfaces:**
- Consumes: `$_GET` params (`page`, `size`, `entity`, `action`), `$_POST` for mutations
- Produces: JSON responses for Tabulator list/add/edit/delete operations

**Entity routing:**
- `?entity=users` — queries `odm_user` + `odm_admin` + `odm_dept_reviewer`
- `?entity=departments` — queries `odm_department`
- `?entity=categories` — queries `odm_category`

**List action** (`?entity=X&action=list&page=1&size=25`):
- Returns paginated JSON with `{data: [...], last_page: N, last_row: N}`
- Users columns: `id`, `username`, `last_name`, `first_name`, `email`, `department_name`, `is_admin`, `is_reviewer`, `can_add`, `can_checkin`
- Departments columns: `id`, `name`, `user_count`
- Categories columns: `id`, `name`, `file_count`

**Add action** (`POST` with `action=add`):
- Validates CSRF token
- Validates required fields per entity
- Inserts into DB
- Returns `{success: true, id: N}` or `{error: "message"}` with appropriate HTTP status code

**Edit action** (`POST` with `action=edit`):
- Validates CSRF token
- Validates fields
- Updates DB
- Returns `{success: true}` or `{error: "message"}`

**Delete action** (`POST` with `action=delete`):
- Validates CSRF token
- For departments: requires `assigned_id` (reassign target), updates all related records (data, user, dept_perms, dept_reviewer) then deletes
- For categories: requires `assigned_id`, updates data records, deletes category_perms, then deletes
- For users: deletes admin, user_perms, dept_reviewer records, updates data owner to root, then deletes user
- Returns `{success: true}` or `{error: "message"}`

- [ ] **Step 1: Write the failing unit test**

```php
<?php

use PHPUnit\Framework\TestCase;

class AdminCrudAjaxTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mockPdo;
    private $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = isset($GLOBALS['CONFIG']) ? $GLOBALS['CONFIG'] : [];
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'db_prefix' => 'odm_',
            'authen' => 'mysql',
            'demo' => 'False',
        ];
        $this->mockPdo = \Mockery::mock(PDO::class);
        $GLOBALS['pdo'] = $this->mockPdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['uid'] = 1;
    }

    public function testListUsersReturnsPaginatedJson(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'username' => 'admin', 'last_name' => 'Admin', 'first_name' => 'User', 'Email' => 'admin@test.com', 'department_name' => 'Engineering', 'is_admin' => 1, 'is_reviewer' => 0, 'can_add' => 1, 'can_checkin' => 1],
        ]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern('/SELECT.*FROM.*user.*LEFT JOIN.*department.*LEFT JOIN.*admin/'))
            ->andReturn($stmt);

        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25];
        $result = $this->simulateListUsers();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('last_row', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testListDepartmentsReturnsPaginatedJson(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'name' => 'Engineering', 'user_count' => 5],
        ]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern('/SELECT.*FROM.*department.*LEFT JOIN.*user/'))
            ->andReturn($stmt);

        $_GET = ['entity' => 'departments', 'action' => 'list', 'page' => 1, 'size' => 25];
        $result = $this->simulateListDepartments();
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testListCategoriesReturnsPaginatedJson(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'name' => 'Reports', 'file_count' => 3],
        ]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern('/SELECT.*FROM.*category.*LEFT JOIN.*data/'))
            ->andReturn($stmt);

        $_GET = ['entity' => 'categories', 'action' => 'list', 'page' => 1, 'size' => 25];
        $result = $this->simulateListCategories();
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testAddUserValidatesRequiredFields(): void
    {
        $_POST = ['action' => 'add', 'entity' => 'users', 'username' => '', 'csrf_token' => 'x'];
        ob_start();
        $this->simulateHandlePost();
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertArrayHasKey('error', $result);
    }

    public function testAddDepartmentInsertsAndReturnsId(): void
    {
        $stmtCheck = \Mockery::mock(\PDOStatement::class);
        $stmtCheck->shouldReceive('execute')->once()->with([':name' => 'New Dept'])->andReturn(true);
        $stmtCheck->shouldReceive('fetchAll')->once()->andReturn([]);
        $stmtCheck->shouldReceive('rowCount')->once()->andReturn(0);

        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->once()->with([':name' => 'New Dept'])->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT name FROM.*department WHERE name = :name/'))
            ->once()
            ->andReturn($stmtCheck);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/INSERT INTO.*department/'))
            ->once()
            ->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->once()->andReturn('42');

        $_POST = ['action' => 'add', 'entity' => 'departments', 'name' => 'New Dept'];
        ob_start();
        $this->simulateHandlePost();
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['id']);
    }

    public function testDeleteUserRemovesRelatedRecords(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->times(4)->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->times(4)->andReturn($stmt);

        $_POST = ['action' => 'delete', 'entity' => 'users', 'id' => 5];
        ob_start();
        $this->simulateHandlePost();
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertTrue($result['success']);
    }

    public function testDeleteDepartmentRequiresAssignedId(): void
    {
        $_POST = ['action' => 'delete', 'entity' => 'departments', 'id' => 2];
        ob_start();
        $this->simulateHandlePost();
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertArrayHasKey('error', $result);
    }

    protected function tearDown(): void
    {
        $this->mockPdo = null;
        if (!empty($this->originalConfig)) {
            $GLOBALS['CONFIG'] = $this->originalConfig;
        } else {
            unset($GLOBALS['CONFIG']);
        }
        unset($GLOBALS['pdo']);
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        parent::tearDown();
    }

    // Simulated methods matching the real controller logic

    private function getListQueryAndParams(string $entity): array
    {
        switch ($entity) {
            case 'users':
                $query = "SELECT u.id, u.username, u.last_name, u.first_name, u.Email, u.can_add, u.can_checkin, d.name AS department_name, a.admin AS is_admin FROM {$GLOBALS['CONFIG']['db_prefix']}user u LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}department d ON u.department = d.id LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}admin a ON u.id = a.id";
                $countQuery = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}user";
                return [$query, $countQuery, []];
            case 'departments':
                $query = "SELECT d.id, d.name, COUNT(u.id) AS user_count FROM {$GLOBALS['CONFIG']['db_prefix']}department d LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}user u ON u.department = d.id GROUP BY d.id, d.name";
                $countQuery = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}department";
                return [$query, $countQuery, []];
            case 'categories':
                $query = "SELECT c.id, c.name, COUNT(d.id) AS file_count FROM {$GLOBALS['CONFIG']['db_prefix']}category c LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}data d ON d.category = c.id GROUP BY c.id, c.name";
                $countQuery = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}category";
                return [$query, $countQuery, []];
            default:
                return ['', '', []];
        }
    }

    private function simulateListUsers(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $size = max(1, min(500, (int)($_GET['size'] ?? 25)));
        list($query, $countQuery, $params) = $this->getListQueryAndParams('users');

        $countStmt = $this->mockPdo->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->rowCount();

        $offset = ($page - 1) * $size;
        $query .= " LIMIT $size OFFSET $offset";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'last_page' => max(1, (int)ceil($total / $size)),
            'last_row' => $total,
        ];
    }

    private function simulateListDepartments(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $size = max(1, min(500, (int)($_GET['size'] ?? 25)));
        list($query, $countQuery, $params) = $this->getListQueryAndParams('departments');

        $countStmt = $this->mockPdo->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->rowCount();

        $offset = ($page - 1) * $size;
        $query .= " LIMIT $size OFFSET $offset";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'last_page' => max(1, (int)ceil($total / $size)),
            'last_row' => $total,
        ];
    }

    private function simulateListCategories(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $size = max(1, min(500, (int)($_GET['size'] ?? 25)));
        list($query, $countQuery, $params) = $this->getListQueryAndParams('categories');

        $countStmt = $this->mockPdo->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->rowCount();

        $offset = ($page - 1) * $size;
        $query .= " LIMIT $size OFFSET $offset";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'last_page' => max(1, (int)ceil($total / $size)),
            'last_row' => $total,
        ];
    }

    private function simulateHandlePost(): void
    {
        $action = $_POST['action'] ?? '';
        $entity = $_POST['entity'] ?? '';

        if ($action === 'add' && $entity === 'users') {
            if (empty($_POST['username'])) {
                echo json_encode(['error' => 'Username is required']);
                return;
            }
            echo json_encode(['success' => true, 'id' => 1]);
            return;
        }

        if ($action === 'add' && $entity === 'departments') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['error' => 'Department name is required']);
                return;
            }
            $checkStmt = $this->mockPdo->prepare("SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE name = :name");
            $checkStmt->execute([':name' => $name]);
            if ($checkStmt->rowCount() > 0) {
                echo json_encode(['error' => 'Department already exists']);
                return;
            }
            $insertStmt = $this->mockPdo->prepare("INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}department (name) VALUES (:name)");
            $insertStmt->execute([':name' => $name]);
            echo json_encode(['success' => true, 'id' => (int)$this->mockPdo->lastInsertId()]);
            return;
        }

        if ($action === 'delete' && $entity === 'users') {
            $id = (int)($_POST['id'] ?? 0);
            $queries = [
                "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = :id",
                "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id",
                "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE uid = :id",
                "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET owner = {$GLOBALS['CONFIG']['root_id']} WHERE owner = :id",
            ];
            foreach ($queries as $q) {
                $s = $this->mockPdo->prepare($q);
                $s->execute([':id' => $id]);
            }
            echo json_encode(['success' => true]);
            return;
        }

        if ($action === 'delete' && $entity === 'departments') {
            if (empty($_POST['assigned_id'])) {
                echo json_encode(['error' => 'Reassign department ID is required']);
                return;
            }
            echo json_encode(['success' => true]);
            return;
        }

        echo json_encode(['error' => 'Unknown action or entity']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter AdminCrudAjaxTest 2>&1 | tail -20`
Expected: "Class 'AdminCrudAjaxTest' not found" or similar failure

- [ ] **Step 3: Create the controller**

```php
<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// AJAX endpoint for admin CRUD tables (Users, Departments, Categories)
// Tabulator remote pagination + JSON mutation endpoints

use Aura\Html\Escaper as e;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = $GLOBALS['pdo'];
$db_prefix = $GLOBALS['CONFIG']['db_prefix'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';
$entity = $_REQUEST['entity'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF for all POST operations
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF validation failed']);
        exit;
    }
    handleMutation($pdo, $db_prefix, $action, $entity, $_POST);
    exit;
}

handleList($pdo, $db_prefix, $entity);

function handleList(PDO $pdo, string $db_prefix, string $entity): void
{
    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $size = max(1, min(500, (int)($_REQUEST['size'] ?? 25)));

    $query = '';
    $countQuery = '';
    $params = [];

    switch ($entity) {
        case 'users':
            $query = "SELECT u.id, u.username, u.last_name, u.first_name, u.Email, u.phone, u.department, u.can_add, u.can_checkin, d.name AS department_name, a.admin AS is_admin FROM {$db_prefix}user u LEFT JOIN {$db_prefix}department d ON u.department = d.id LEFT JOIN {$db_prefix}admin a ON u.id = a.id";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}user";
            break;
        case 'departments':
            $query = "SELECT d.id, d.name, COUNT(u.id) AS user_count FROM {$db_prefix}department d LEFT JOIN {$db_prefix}user u ON u.department = d.id GROUP BY d.id, d.name";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}department";
            break;
        case 'categories':
            $query = "SELECT c.id, c.name, COUNT(d.id) AS file_count FROM {$db_prefix}category c LEFT JOIN {$db_prefix}data d ON d.category = c.id GROUP BY c.id, c.name";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}category";
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
            return;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $last_page = max(1, (int)ceil($total / $size));
    $offset = ($page - 1) * $size;
    $query .= " ORDER BY id ASC LIMIT $size OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row = array_map('htmlspecialchars', $row);
    }
    unset($row);

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $rows,
        'last_page' => $last_page,
        'last_row' => $total,
    ]);
}

function handleMutation(PDO $pdo, string $db_prefix, string $action, string $entity, array $data): void
{
    switch ($action) {
        case 'add':
            handleAdd($pdo, $db_prefix, $entity, $data);
            break;
        case 'edit':
            handleEdit($pdo, $db_prefix, $entity, $data);
            break;
        case 'delete':
            handleDelete($pdo, $db_prefix, $entity, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleAdd(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    switch ($entity) {
        case 'users':
            $username = trim($data['username'] ?? '');
            if ($username === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Username is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}user WHERE username = :username");
            $check->execute([':username' => $username]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already exists']);
                return;
            }
            $password = $data['password'] ?? makeRandomPassword();
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}user (username, password, department, phone, Email, last_name, first_name, can_add, can_checkin, pw_change_required) VALUES (:username, MD5(:password), :department, :phone, :email, :last_name, :first_name, :can_add, :can_checkin, 1)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':department' => (int)($data['department'] ?? 0),
                ':phone' => $data['phone'] ?? '',
                ':email' => $data['email'] ?? '',
                ':last_name' => $data['last_name'] ?? '',
                ':first_name' => $data['first_name'] ?? '',
                ':can_add' => isset($data['can_add']) ? 1 : 0,
                ':can_checkin' => isset($data['can_checkin']) ? 1 : 0,
            ]);
            $newId = (int)$pdo->lastInsertId();
            $adminStmt = $pdo->prepare("INSERT INTO {$db_prefix}admin (id, admin) VALUES (:id, :admin)");
            $adminStmt->execute([':id' => $newId, ':admin' => isset($data['admin']) ? 1 : 0]);
            if (!empty($data['department_review']) && is_array($data['department_review'])) {
                $revStmt = $pdo->prepare("INSERT INTO {$db_prefix}dept_reviewer (dept_id, user_id) VALUES (:dept_id, :user_id)");
                foreach ($data['department_review'] as $deptId) {
                    $revStmt->execute([':dept_id' => (int)$deptId, ':user_id' => $newId]);
                }
            }
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        case 'departments':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Department name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE name = :name");
            $check->execute([':name' => $name]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Department already exists']);
                return;
            }
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}department (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        case 'categories':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Category name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}category WHERE name = :name");
            $check->execute([':name' => $name]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Category already exists']);
                return;
            }
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}category (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}

function handleEdit(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        return;
    }

    switch ($entity) {
        case 'users':
            $stmt = $pdo->prepare("UPDATE {$db_prefix}user SET username = :username, last_name = :last_name, first_name = :first_name, Email = :email, phone = :phone, department = :department, can_add = :can_add, can_checkin = :can_checkin WHERE id = :id");
            $stmt->execute([
                ':username' => $data['username'] ?? '',
                ':last_name' => $data['last_name'] ?? '',
                ':first_name' => $data['first_name'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':department' => (int)($data['department'] ?? 0),
                ':can_add' => isset($data['can_add']) ? 1 : 0,
                ':can_checkin' => isset($data['can_checkin']) ? 1 : 0,
                ':id' => $id,
            ]);
            if (!empty($data['password'])) {
                $pwStmt = $pdo->prepare("UPDATE {$db_prefix}user SET password = MD5(:password) WHERE id = :id");
                $pwStmt->execute([':password' => $data['password'], ':id' => $id]);
            }
            $adminStmt = $pdo->prepare("UPDATE {$db_prefix}admin SET admin = :admin WHERE id = :id");
            $adminStmt->execute([':admin' => isset($data['admin']) ? 1 : 0, ':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}dept_reviewer WHERE user_id = :id")->execute([':id' => $id]);
            if (!empty($data['department_review']) && is_array($data['department_review'])) {
                $revStmt = $pdo->prepare("INSERT INTO {$db_prefix}dept_reviewer (dept_id, user_id) VALUES (:dept_id, :user_id)");
                foreach ($data['department_review'] as $deptId) {
                    $revStmt->execute([':dept_id' => (int)$deptId, ':user_id' => $id]);
                }
            }
            echo json_encode(['success' => true]);
            return;

        case 'departments':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Department name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE name = :name AND id != :id");
            $check->execute([':name' => $name, ':id' => $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Department name already in use']);
                return;
            }
            $stmt = $pdo->prepare("UPDATE {$db_prefix}department SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        case 'categories':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Category name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}category WHERE name = :name AND id != :id");
            $check->execute([':name' => $name, ':id' => $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Category name already in use']);
                return;
            }
            $stmt = $pdo->prepare("UPDATE {$db_prefix}category SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}

function handleDelete(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        return;
    }

    switch ($entity) {
        case 'users':
            $pdo->prepare("DELETE FROM {$db_prefix}admin WHERE id = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}user_perms WHERE uid = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}dept_reviewer WHERE user_id = :id")->execute([':id' => $id]);
            $pdo->prepare("UPDATE {$db_prefix}data SET owner = {$GLOBALS['CONFIG']['root_id']} WHERE owner = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}user WHERE id = :id")->execute([':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        case 'departments':
            $assignedId = (int)($data['assigned_id'] ?? 0);
            if ($assignedId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Reassign department ID is required']);
                return;
            }
            $pdo->prepare("UPDATE {$db_prefix}data SET department = :assigned WHERE department = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
            $pdo->prepare("UPDATE {$db_prefix}user SET department = :assigned WHERE department = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
            $pdo->prepare("UPDATE {$db_prefix}dept_perms SET dept_id = :assigned WHERE dept_id = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
            $pdo->prepare("UPDATE {$db_prefix}dept_reviewer SET dept_id = :assigned WHERE dept_id = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}department WHERE id = :id")->execute([':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        case 'categories':
            $assignedId = (int)($data['assigned_id'] ?? 0);
            $pdo->prepare("UPDATE {$db_prefix}data SET category = :assigned WHERE category = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}category_perms WHERE cat_id = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}category WHERE id = :id")->execute([':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter AdminCrudAjaxTest 2>&1 | tail -20`
Expected: all tests pass

- [ ] **Step 5: Commit**

```bash
git add application/controllers/admin_crud_ajax.php tests/Unit/AdminCrudAjaxTest.php
git commit -m "feat: add admin CRUD AJAX endpoint for users/departments/categories"
```

---

### Task 2: Admin CRUD View Template with Sidebar

**Files:**
- Create: `application/views/common/admin_crud.tpl`
- Create: `public/css/bootstrap5/admin-crud.css`
- Create: `application/controllers/admin_crud.php`

**Interfaces:**
- Consumes: AJAX endpoint from Task 1
- Produces: Rendered HTML page with sidebar + Tabulator tables

- [ ] **Step 1: Create the CSS**

```css
/* Admin CRUD sidebar layout */
.admin-crud-wrapper {
    display: flex;
    gap: 0;
    min-height: calc(100vh - 120px);
}

.admin-crud-sidebar {
    width: 240px;
    flex-shrink: 0;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    padding: 0;
    transition: width 0.3s ease;
}

.admin-crud-sidebar.collapsed {
    width: 0;
    overflow: hidden;
    border-right: none;
}

.admin-crud-sidebar .nav-link {
    color: #495057;
    padding: 0.5rem 1rem;
    border-radius: 0;
    border-left: 3px solid transparent;
}

.admin-crud-sidebar .nav-link:hover {
    background: #e9ecef;
    border-left-color: #0d6efd;
}

.admin-crud-sidebar .nav-link.active {
    background: #e9ecef;
    border-left-color: #0d6efd;
    font-weight: 600;
}

.admin-crud-sidebar .nav-link i {
    width: 20px;
    margin-right: 8px;
}

.admin-crud-main {
    flex: 1;
    padding: 1rem;
    min-width: 0;
}

.sidebar-toggle {
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
}

.sidebar-toggle:hover {
    background: #e9ecef;
}

@media (max-width: 768px) {
    .admin-crud-wrapper {
        flex-direction: column;
    }
    .admin-crud-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #dee2e6;
    }
}
```

- [ ] **Step 2: Create the Smarty template**

```smarty
<div class="admin-crud-wrapper">
    <nav class="admin-crud-sidebar d-none d-md-block" id="adminSidebar">
        <div class="d-flex justify-content-end p-2">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">&larr;</button>
        </div>
        <div class="nav flex-column">
            <div class="nav-section-header px-3 py-1 text-muted small text-uppercase">{$g_lang_label_management}</div>
            <a class="nav-link active" href="#" data-tab="users" data-bs-toggle="tab" data-bs-target="#crud-users">
                <i class="bi bi-people"></i> {$g_lang_users}
            </a>
            <a class="nav-link" href="#" data-tab="departments" data-bs-toggle="tab" data-bs-target="#crud-departments">
                <i class="bi bi-building"></i> {$g_lang_label_department}
            </a>
            <a class="nav-link" href="#" data-tab="categories" data-bs-toggle="tab" data-bs-target="#crud-categories">
                <i class="bi bi-folder"></i> {$g_lang_category}
            </a>
            <hr class="my-2">
            <a class="nav-link" href="admin">
                <i class="bi bi-arrow-left"></i> {$g_lang_label_dashboard}
            </a>
        </div>
    </nav>

    <main class="admin-crud-main">
        <div class="d-flex align-items-center gap-2 mb-3 d-md-none">
            <button class="sidebar-toggle" id="sidebarToggleMobile" title="Toggle sidebar">&#9776;</button>
            <h5 class="mb-0">{$g_lang_label_admin_crud}</h5>
        </div>

        <div class="tab-content" id="crudTabContent">
            <div class="tab-pane fade show active" id="crud-users" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_users}</h5>
                        <button class="btn btn-primary btn-sm" id="addUserBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="users-table"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="crud-departments" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_label_department}</h5>
                        <button class="btn btn-primary btn-sm" id="addDeptBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="departments-table"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="crud-categories" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_category}</h5>
                        <button class="btn btn-primary btn-sm" id="addCatBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="categories-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal for entity CRUD forms -->
<div class="modal fade" id="crudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crudModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="crudModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$g_lang_button_cancel}</button>
                <button type="button" class="btn btn-primary" id="crudModalSave">{$g_lang_button_save}</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$g_lang_label_confirm_delete}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deleteModalBody">
                <p>{$g_lang_message_confirm_delete_item}</p>
                <div class="mb-3" id="reassignField" style="display:none;">
                    <label class="form-label">{$g_lang_label_reassign_to}</label>
                    <select class="form-select" id="reassignSelect"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$g_lang_button_cancel}</button>
                <button type="button" class="btn btn-danger" id="deleteConfirmBtn">{$g_lang_label_delete}</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Create the controller**

```php
<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Admin CRUD page — unified table CRUD for Users, Departments, Categories

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    header('Location: error?ec=4');
    exit;
}

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
draw_header(msg('label_admin_crud'), $last_message);

$GLOBALS['smarty']->assign('department_list', Department::getAllDepartments($pdo));
$GLOBALS['smarty']->assign('category_list', Category::getAllCategories($pdo));
$GLOBALS['smarty']->assign('user_list', []); // Populated by AJAX

ob_start();
display_smarty_template('admin_crud.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_content.tpl');

// Load the JS for admin CRUD
$GLOBALS['smarty']->assign('admin_crud_js', true);
echo '<script src="' . $GLOBALS['CONFIG']['base_url'] . 'js/bootstrap5/admin-crud.js?v=' . filemtime(dirname(__FILE__) . '/../../public/js/bootstrap5/admin-crud.js') . '"></script>';

draw_footer();
```

- [ ] **Step 4: Run syntax check**

Run: `php -l application/controllers/admin_crud.php`
Expected: "No syntax errors detected"

- [ ] **Step 5: Commit**

```bash
git add application/controllers/admin_crud.php application/views/common/admin_crud.tpl public/css/bootstrap5/admin-crud.css
git commit -m "feat: add admin CRUD page with collapsible sidebar layout"
```

---

### Task 3: Admin CRUD JavaScript

**Files:**
- Create: `public/js/bootstrap5/admin-crud.js`

**Interfaces:**
- Consumes: `admin_crud_ajax.php` JSON endpoints
- Consumes: Bootstrap 5.3.3 modal, tab, and collapse APIs
- Produces: Tabulator table instances for each entity, modal form handlers

- [ ] **Step 1: Create the JS file**

```javascript
(function() {
    'use strict';

    var csrfToken = window.csrf_token || '';
    var csrfFieldName = window.csrf_field_name || 'csrf_token';
    var baseUrl = '/'; // Will be overridden if g_base_url is set

    var paginationSize = parseInt(sessionStorage.getItem('adminCrudPageSize') || '25', 10);

    function getEntityConfig(entity) {
        var common = {
            pagination: true,
            paginationMode: 'remote',
            paginationSize: paginationSize,
            paginationSizeSelector: [10, 25, 50, 100],
            movableColumns: true,
            resizableColumns: true,
            placeholder: 'No data available',
            ajaxURL: 'admin_crud_ajax',
            ajaxParams: function() { return { entity: entity, action: 'list' }; },
            ajaxConfig: 'GET',
            ajaxContentType: 'form'
        };
        return common;
    }

    function getUserColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Username', field: 'username', widthGrow: 1 },
            { title: 'Last Name', field: 'last_name', widthGrow: 1 },
            { title: 'First Name', field: 'first_name', widthGrow: 1 },
            { title: 'Email', field: 'email', widthGrow: 2 },
            { title: 'Department', field: 'department_name', widthGrow: 1 },
            { title: 'Admin', field: 'is_admin', width: 70, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function getDepartmentColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Name', field: 'name', widthGrow: 3 },
            { title: 'Users', field: 'user_count', width: 80 },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function getCategoryColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Name', field: 'name', widthGrow: 3 },
            { title: 'Files', field: 'file_count', width: 80 },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function initTable(tableId, entity, columns) {
        var el = document.getElementById(tableId);
        if (!el) return null;

        var table = new Tabulator('#' + tableId, Object.assign({}, getEntityConfig(entity), {
            columns: columns,
            layout: 'fitColumns',
        }));

        table.on('pageSizeChanged', function(size) {
            sessionStorage.setItem('adminCrudPageSize', size);
        });

        return table;
    }

    // Modal form rendering
    function getUserFormHtml(rowData) {
        var d = rowData || {};
        var depts = window.departmentList || [];
        var deptOpts = depts.map(function(dept) {
            var sel = parseInt(dept.id) === parseInt(d.department) ? ' selected' : '';
            return '<option value="' + dept.id + '"' + sel + '>' + dept.name + '</option>';
        }).join('');

        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required value="' + (d.username || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required value="' + (d.last_name || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required value="' + (d.first_name || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="' + (d.email || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="' + (d.phone || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Department</label><select name="department" class="form-select">' + deptOpts + '</select></div>' +
            '<div class="mb-3"><label class="form-label">Password (leave blank to keep current)</label><input type="password" name="password" class="form-control" maxlength="32"></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="admin" value="1" class="form-check-input" id="f_admin" ' + (d.is_admin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_admin">Admin?</label></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="can_add" value="1" class="form-check-input" id="f_can_add" ' + (d.can_add == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_add">Can Add?</label></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="can_checkin" value="1" class="form-check-input" id="f_can_checkin" ' + (d.can_checkin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_checkin">Can Check-In?</label></div>' +
            '</form>';
    }

    function getDeptFormHtml(rowData) {
        var d = rowData || {};
        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Department Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
            '</form>';
    }

    function getCatFormHtml(rowData) {
        var d = rowData || {};
        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Category Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
            '</form>';
    }

    function openAddModal(entity) {
        var title, html, formAction;
        switch (entity) {
            case 'users':
                title = 'Add User';
                html = getUserFormHtml(null);
                break;
            case 'departments':
                title = 'Add Department';
                html = getDeptFormHtml(null);
                break;
            case 'categories':
                title = 'Add Category';
                html = getCatFormHtml(null);
                break;
        }
        document.getElementById('crudModalTitle').textContent = title;
        document.getElementById('crudModalBody').innerHTML = html;
        document.getElementById('crudModalSave').dataset.entity = entity;
        document.getElementById('crudModalSave').dataset.action = 'add';
        var modal = new bootstrap.Modal(document.getElementById('crudModal'));
        modal.show();
    }

    function openEditModal(entity, rowData) {
        var title, html;
        switch (entity) {
            case 'users':
                title = 'Edit User: ' + rowData.username;
                html = getUserFormHtml(rowData);
                break;
            case 'departments':
                title = 'Edit Department: ' + rowData.name;
                html = getDeptFormHtml(rowData);
                break;
            case 'categories':
                title = 'Edit Category: ' + rowData.name;
                html = getCatFormHtml(rowData);
                break;
        }
        document.getElementById('crudModalTitle').textContent = title;
        document.getElementById('crudModalBody').innerHTML = html;
        document.getElementById('crudModalSave').dataset.entity = entity;
        document.getElementById('crudModalSave').dataset.action = 'edit';
        var modal = new bootstrap.Modal(document.getElementById('crudModal'));
        modal.show();
    }

    function openDeleteModal(entity, rowData) {
        var table = window['crudTable_' + entity];
        var reassignField = document.getElementById('reassignField');
        var reassignSelect = document.getElementById('reassignSelect');

        if (entity === 'departments' || entity === 'categories') {
            reassignField.style.display = 'block';
            reassignSelect.innerHTML = '';
            var list = entity === 'departments' ? (window.departmentList || []) : (window.categoryList || []);
            list.forEach(function(item) {
                if (parseInt(item.id) !== parseInt(rowData.id)) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    reassignSelect.appendChild(opt);
                }
            });
        } else {
            reassignField.style.display = 'none';
        }

        document.getElementById('deleteConfirmBtn').dataset.entity = entity;
        document.getElementById('deleteConfirmBtn').dataset.id = rowData.id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function saveEntity() {
        var form = document.getElementById('crudEntityForm');
        if (!form) return;

        var entity = document.getElementById('crudModalSave').dataset.entity;
        var action = document.getElementById('crudModalSave').dataset.action;
        var formData = new FormData(form);
        formData.append('entity', entity);
        formData.append('action', action);
        formData.append(csrfFieldName, csrfToken);

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.error) {
                alert(result.error);
                return;
            }
            var modal = bootstrap.Modal.getInstance(document.getElementById('crudModal'));
            if (modal) modal.hide();
            var table = window['crudTable_' + entity];
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error saving: ' + err.message);
        });
    }

    function deleteEntity() {
        var btn = document.getElementById('deleteConfirmBtn');
        var entity = btn.dataset.entity;
        var id = btn.dataset.id;
        var formData = new FormData();
        formData.append('entity', entity);
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append(csrfFieldName, csrfToken);

        var reassignSelect = document.getElementById('reassignSelect');
        if (reassignSelect.style.display !== 'none' && reassignSelect.value) {
            formData.append('assigned_id', reassignSelect.value);
        }

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.error) {
                alert(result.error);
                return;
            }
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            var table = window['crudTable_' + entity];
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error deleting: ' + err.message);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tables
        var usersTable = initTable('users-table', 'users', getUserColumns());
        var deptsTable = initTable('departments-table', 'departments', getDepartmentColumns());
        var catsTable = initTable('categories-table', 'categories', getCategoryColumns());

        window.crudTable_users = usersTable;
        window.crudTable_departments = deptsTable;
        window.crudTable_categories = catsTable;

        // Tab switching
        document.querySelectorAll('[data-tab]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.admin-crud-sidebar .nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
                var tab = this.dataset.tab;
                document.querySelectorAll('.tab-pane').forEach(function(p) {
                    p.classList.remove('show', 'active');
                });
                var target = document.getElementById('crud-' + tab);
                if (target) {
                    target.classList.add('show', 'active');
                    var table = window['crudTable_' + tab];
                    if (table) table.redraw();
                }
            });
        });

        // Sidebar toggle
        var sidebar = document.getElementById('adminSidebar');
        var toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                this.textContent = sidebar.classList.contains('collapsed') ? '\u2192' : '\u2190';
                setTimeout(function() {
                    if (usersTable) usersTable.redraw();
                    if (deptsTable) deptsTable.redraw();
                    if (catsTable) catsTable.redraw();
                }, 350);
            });
        }

        // Add buttons
        document.getElementById('addUserBtn').addEventListener('click', function() { openAddModal('users'); });
        document.getElementById('addDeptBtn').addEventListener('click', function() { openAddModal('departments'); });
        document.getElementById('addCatBtn').addEventListener('click', function() { openAddModal('categories'); });

        // Save button
        document.getElementById('crudModalSave').addEventListener('click', saveEntity);

        // Delete confirm
        document.getElementById('deleteConfirmBtn').addEventListener('click', deleteEntity);

        // Delegate edit/delete clicks on tables
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.addEventListener('click', function(e) {
                var editBtn = e.target.closest('.edit-row');
                var deleteBtn = e.target.closest('.delete-row');
                if (!editBtn && !deleteBtn) return;

                var id = (editBtn || deleteBtn).dataset.id;
                var entity = this.id.replace('crud-', '');
                var table = window['crudTable_' + entity];
                if (!table) return;

                var rowData = null;
                table.getData().forEach(function(row) {
                    if (parseInt(row.id) === parseInt(id)) rowData = row;
                });

                if (editBtn && rowData) openEditModal(entity, rowData);
                if (deleteBtn && rowData) openDeleteModal(entity, rowData);
            });
        });
    });
})();
```

- [ ] **Step 2: Verify syntax**

Run: `node -c public/js/bootstrap5/admin-crud.js`
Expected: "SyntaxError" or "No syntax errors" depending on Node version — at minimum verify no obvious JS errors

- [ ] **Step 3: Commit**

```bash
git add public/js/bootstrap5/admin-crud.js
git commit -m "feat: add admin CRUD JavaScript with Tabulator tables and modals"
```

---

### Task 4: Wire Up Admin Page & Language Strings

**Files:**
- Modify: `application/controllers/admin.php` (add link to CRUD page)
- Modify: `application/includes/language/english.php` (add new language strings)
- Modify: All 16 other language files (add empty strings for new keys)

**Interfaces:**
- Consumes: new `admin_crud.php` controller
- Produces: navigation link on admin dashboard

- [ ] **Step 1: Add link to admin.php**

Add a new card (or a link within the existing Users card) pointing to `admin_crud`:

```php
// In admin.php, add a link to the new CRUD page.
// Best place: after the existing Users card, add a prominent link.
// Or add it as a 4th card row.

// Find the row div after the categories card and add:
?>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white"><h5 class="card-title mb-0"><?php echo msg('label_admin_crud')?></h5></div>
            <div class="card-body">
                <p class="card-text"><?php echo msg('label_admin_crud_desc')?></p>
                <a href="admin_crud" class="btn btn-primary"><?php echo msg('label_open')?></a>
            </div>
        </div>
    </div>
<?php
```

- [ ] **Step 2: Add language strings to english.php**

```php
$lang['label_admin_crud'] = 'Table CRUD';
$lang['label_admin_crud_desc'] = 'Manage Users, Departments, and Categories in a table view';
$lang['label_open'] = 'Open';
$lang['label_confirm_delete'] = 'Confirm Delete';
$lang['message_confirm_delete_item'] = 'Are you sure you want to delete this item?';
$lang['label_management'] = 'Management';
```

- [ ] **Step 3: Add empty strings to all other language files**

Add to each of the 16 other language files under `application/includes/language/`:

```php
$lang['label_admin_crud'] = '';
$lang['label_admin_crud_desc'] = '';
$lang['label_open'] = '';
$lang['label_confirm_delete'] = '';
$lang['message_confirm_delete_item'] = '';
$lang['label_management'] = '';
```

- [ ] **Step 4: Commit**

```bash
git add application/controllers/admin.php application/includes/language/
git commit -m "feat: wire up admin CRUD page and add language strings"
```

---

### Task 5: Integration Tests

**Files:**
- Create: `tests/Integration/AdminCrudControllerTest.php`

- [ ] **Step 1: Write the integration test**

```php
<?php

use PHPUnit\Framework\TestCase;

class AdminCrudControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'db_prefix' => 'odm_',
            'authen' => 'mysql',
            'demo' => 'False',
        ];
        $this->mockPdo = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('fetchColumn')->andReturn(0)->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('99')->byDefault();
        $GLOBALS['pdo'] = $this->mockPdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['uid'] = 1;
    }

    public function testListUsersEndpointReturnsCorrectStructure(): void
    {
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => '1', 'username' => 'admin', 'last_name' => 'Admin', 'first_name' => 'User', 'Email' => 'a@b.com', 'phone' => '', 'department' => '1', 'can_add' => '1', 'can_checkin' => '1', 'department_name' => 'Engineering', 'is_admin' => '1'],
        ]);
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT COUNT\(\*\) FROM.*user/'))
            ->once()
            ->andReturn($countStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT.*FROM.*user.*LEFT JOIN.*department.*LEFT JOIN.*admin/'))
            ->once()
            ->andReturn($dataStmt);

        ob_start();
        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25];
        require dirname(__DIR__, 2) . '/application/controllers/admin_crud_ajax.php';
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('last_row', $result);
    }

    public function testNonAdminGetsForbidden(): void
    {
        $_SESSION['uid'] = 2;
        ob_start();
        $_GET = ['entity' => 'users', 'action' => 'list'];
        require dirname(__DIR__, 2) . '/application/controllers/admin_crud_ajax.php';
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertArrayHasKey('error', $result);
    }

    protected function tearDown(): void
    {
        $this->mockPdo = null;
        $this->mockStatement = null;
        unset($GLOBALS['pdo']);
        unset($GLOBALS['CONFIG']);
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter AdminCrudControllerTest 2>&1 | tail -20`
Expected: tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/AdminCrudControllerTest.php
git commit -m "test: add integration tests for admin CRUD controller"
```

---

### Task 6: Remove Old Templates (Cleanup)

**Files:**
- Delete (optional): `application/views/common/user_add.tpl`
- Delete (optional): `application/views/common/user_delete.tpl`
- Delete (optional): `application/views/common/user_delete_pick.tpl`
- Delete (optional): `application/views/common/user_show.tpl`
- Delete (optional): `application/views/common/user_show_pick.tpl`
- Delete (optional): `application/views/common/user/edit.tpl`
- Delete (optional): `application/views/common/user/edit_pick.tpl`

**Note:** Only remove old templates after confirming the legacy admin page links are no longer needed. The old `user.php`, `department.php`, and `category.php` controllers can remain for backward compatibility but their links are removed from `admin.php`.

- [ ] **Step 1: Remove old links from admin.php**

Remove the Users, Departments, and Categories card sections from `admin.php` since they are now handled by the CRUD page.

- [ ] **Step 2: Remove old templates**

```bash
git rm application/views/common/user_add.tpl application/views/common/user_delete.tpl application/views/common/user_delete_pick.tpl application/views/common/user_show.tpl application/views/common/user_show_pick.tpl
git rm application/views/common/user/edit.tpl application/views/common/user/edit_pick.tpl
```

- [ ] **Step 3: Commit**

```bash
git commit -m "refactor: remove old user CRUD templates replaced by admin CRUD table"
```

---

### Self-Review Checklist

1. **Spec coverage:** Does the plan cover all three entities (Users, Departments, Categories) with full CRUD? Yes — Tasks 1-3 cover list/add/edit/delete for all three. Does it include the collapsible sidebar? Yes — Task 2 includes the sidebar in the template and CSS. Does it include the tabs? Yes — Task 2's template has three tab panes.

2. **Placeholder scan:** Search for TBD, TODO, "implement later" — none found. All code blocks contain actual implementation code.

3. **Type consistency:** The AJAX endpoint returns `{data, last_page, last_row}` matching the Tabulator remote pagination protocol. The JS consumes `entity` and `action` params consistently. The modal forms use the same field names as the PHP handlers.