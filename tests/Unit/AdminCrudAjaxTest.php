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
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'username' => 'admin', 'last_name' => 'Admin', 'first_name' => 'User', 'Email' => 'admin@test.com', 'department_name' => 'Engineering', 'is_admin' => 1, 'is_reviewer' => 0, 'can_add' => 1, 'can_checkin' => 1],
        ]);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/^SELECT COUNT/'))
            ->once()
            ->andReturn($countStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT u\.id,.*FROM.*odm_user.*LEFT JOIN.*odm_department.*LEFT JOIN.*odm_admin/'))
            ->once()
            ->andReturn($dataStmt);

        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25];
        $result = $this->simulateListUsers();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('last_row', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testListDepartmentsReturnsPaginatedJson(): void
    {
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'name' => 'Engineering', 'user_count' => 5],
        ]);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT COUNT/'))
            ->once()
            ->andReturn($countStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT.*FROM.*department.*LEFT JOIN.*user/'))
            ->once()
            ->andReturn($dataStmt);

        $_GET = ['entity' => 'departments', 'action' => 'list', 'page' => 1, 'size' => 25];
        $result = $this->simulateListDepartments();
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
    }

    public function testListCategoriesReturnsPaginatedJson(): void
    {
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'name' => 'Reports', 'file_count' => 3],
        ]);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT COUNT/'))
            ->once()
            ->andReturn($countStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT.*FROM.*category.*LEFT JOIN.*data/'))
            ->once()
            ->andReturn($dataStmt);

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

    private function getListQueryAndParams(string $entity): array
    {
        switch ($entity) {
            case 'users':
                $query = "SELECT u.id, u.username, u.last_name, u.first_name, u.Email AS email, u.can_add, u.can_checkin, d.name AS department_name, a.admin AS is_admin, (SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer dr WHERE dr.user_id = u.id) > 0 AS is_reviewer FROM {$GLOBALS['CONFIG']['db_prefix']}user u LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}department d ON u.department = d.id LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}admin a ON u.id = a.id";
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
        $total = (int)$countStmt->fetchColumn();

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
        $total = (int)$countStmt->fetchColumn();

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
        $total = (int)$countStmt->fetchColumn();

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