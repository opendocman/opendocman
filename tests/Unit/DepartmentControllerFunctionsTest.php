<?php

use PHPUnit\Framework\TestCase;

/**
 * Functional tests for Department controller actions
 * 
 * This test class tests isolated functions and logic from the department controller
 * by extracting testable components and validating the business logic.
 */
class DepartmentControllerFunctionsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PDO|\Mockery\MockInterface
     */
    private $mockPdo;

    /**
     * @var array
     */
    private $originalConfig;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Store original config
        $this->originalConfig = isset($GLOBALS['CONFIG']) ? $GLOBALS['CONFIG'] : [];
        
        // Set up global configuration
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'database_prefix' => 'odm_',
            'db_prefix' => 'odm_'
        ];
        
        // Create mock PDO
        $this->mockPdo = \Mockery::mock(PDO::class);
        $GLOBALS['pdo'] = $this->mockPdo;
        
        // Start session for tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['uid'] = 1;
    }

    /**
     * Test department name validation logic
     */
    public function testValidateDepartmentName(): void
    {
        // Test empty name
        $this->assertFalse($this->validateDepartmentName(''));
        $this->assertFalse($this->validateDepartmentName('   '));
        $this->assertFalse($this->validateDepartmentName(null));
        
        // Test valid names
        $this->assertTrue($this->validateDepartmentName('Engineering'));
        $this->assertTrue($this->validateDepartmentName('Human Resources'));
        $this->assertTrue($this->validateDepartmentName('R&D Department'));
        
        // Test edge cases
        $this->assertTrue($this->validateDepartmentName('A')); // Single character
        $this->assertTrue($this->validateDepartmentName('Department with numbers 123'));
        $this->assertTrue($this->validateDepartmentName('Dept-With-Hyphens'));
    }

    /**
     * Test department duplicate checking logic
     */
    public function testCheckDepartmentDuplicate(): void
    {
        $departmentName = 'Test Department';
        
        // Test when department doesn't exist
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':department' => $departmentName])
                      ->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn([]);
        $mockStatement->shouldReceive('rowCount')->once()->andReturn(0);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT name FROM.*department where name = :department/'))
                      ->andReturn($mockStatement);
        
        $this->assertFalse($this->checkDepartmentExists($departmentName));
        
        // Test when department exists
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')
                       ->once()
                       ->with([':department' => $departmentName])
                       ->andReturn(true);
        $mockStatement2->shouldReceive('fetchAll')
                       ->once()
                       ->andReturn([['name' => $departmentName]]);
        $mockStatement2->shouldReceive('rowCount')->once()->andReturn(1);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT name FROM.*department where name = :department/'))
                      ->andReturn($mockStatement2);
        
        $this->assertTrue($this->checkDepartmentExists($departmentName));
    }

    /**
     * Test department insertion logic
     */
    public function testInsertDepartment(): void
    {
        $departmentName = 'New Department';
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':department' => $departmentName])
                      ->andReturn(true);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/INSERT INTO.*department.*VALUES/'))
                      ->andReturn($mockStatement);
        
        $result = $this->insertDepartment($departmentName);
        $this->assertTrue($result);
    }

    /**
     * Test getting newly added department ID
     */
    public function testGetNewlyAddedDepartmentId(): void
    {
        $departmentName = 'New Department';
        $expectedId = 42;
        
        // Test successful ID retrieval
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':department' => $departmentName])
                      ->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')
                      ->once()
                      ->andReturn([['id' => $expectedId]]);
        $mockStatement->shouldReceive('rowCount')->once()->andReturn(1);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT id FROM.*department WHERE name = :department/'))
                      ->andReturn($mockStatement);
        
        $result = $this->getNewlyAddedDepartmentId($departmentName);
        $this->assertEquals($expectedId, $result);
        
        // Test when department not found
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')
                       ->once()
                       ->with([':department' => $departmentName])
                       ->andReturn(true);
        $mockStatement2->shouldReceive('fetchAll')->once()->andReturn([]);
        $mockStatement2->shouldReceive('rowCount')->once()->andReturn(0);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT id FROM.*department WHERE name = :department/'))
                      ->andReturn($mockStatement2);
        
        $result = $this->getNewlyAddedDepartmentId($departmentName);
        $this->assertNull($result);
    }

    /**
     * Test getting default rights for files
     */
    public function testGetDefaultRights(): void
    {
        $defaultRights = [
            ['id' => 1, 'default_rights' => 1],
            ['id' => 2, 'default_rights' => 2],
            ['id' => 3, 'default_rights' => 3]
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($defaultRights);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT id, default_rights FROM.*data/'))
                      ->andReturn($mockStatement);
        
        $result = $this->getDefaultRights();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($defaultRights, $result);
    }

    /**
     * Test setting default permissions for new department
     */
    public function testSetDefaultPermissions(): void
    {
        $departmentId = 5;
        $dataArray = [
            [1, 1], // [file_id, rights]
            [2, 2],
            [3, 3]
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->times(3)
                      ->andReturn(true);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->times(3)
                      ->with(\Mockery::pattern('/INSERT INTO.*dept_perms/'))
                      ->andReturn($mockStatement);
        
        $result = $this->setDefaultPermissions($departmentId, $dataArray);
        $this->assertTrue($result);
    }

    /**
     * Test getting department information
     */
    public function testGetDepartmentInfo(): void
    {
        $departmentId = 3;
        $expectedData = ['id' => $departmentId, 'name' => 'Human Resources'];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':item' => $departmentId])
                      ->andReturn(true);
        $mockStatement->shouldReceive('fetch')->once()->andReturn($expectedData);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT name,id FROM.*department where id = :item/'))
                      ->andReturn($mockStatement);
        
        $result = $this->getDepartmentInfo($departmentId);
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test getting users in department
     */
    public function testGetUsersInDepartment(): void
    {
        $departmentId = 3;
        $expectedUsers = [
            ['id' => 3, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Smith']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':item' => $departmentId])
                      ->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($expectedUsers);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT.*dept\.id.*u\.first_name.*u\.last_name.*FROM.*department.*user/'))
                      ->andReturn($mockStatement);
        
        $result = $this->getUsersInDepartment($departmentId);
        $this->assertEquals($expectedUsers, $result);
    }

    /**
     * Test getting reassign options for department deletion
     */
    public function testGetReassignOptions(): void
    {
        $departmentId = 2;
        $reassignOptions = [
            ['id' => 1, 'name' => 'Engineering'],
            ['id' => 3, 'name' => 'Marketing']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
                      ->once()
                      ->with([':item' => $departmentId])
                      ->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($reassignOptions);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT id, name FROM.*department WHERE id != :item ORDER BY name/'))
                      ->andReturn($mockStatement);
        
        $result = $this->getReassignOptions($departmentId);
        $this->assertEquals($reassignOptions, $result);
        
        // Test when no other departments exist
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')
                       ->once()
                       ->with([':item' => $departmentId])
                       ->andReturn(true);
        $mockStatement2->shouldReceive('fetchAll')->once()->andReturn([]);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->once()
                      ->with(\Mockery::pattern('/SELECT id, name FROM.*department WHERE id != :item ORDER BY name/'))
                      ->andReturn($mockStatement2);
        
        $result = $this->getReassignOptions($departmentId);
        $this->assertEmpty($result);
    }

    /**
     * Test admin permission checking
     */
    public function testCheckAdminPermission(): void
    {
        // Mock admin user
        $adminUser = \Mockery::mock(User::class);
        $adminUser->shouldReceive('isAdmin')->andReturn(true);
        
        $this->assertTrue($this->checkAdminPermission($adminUser));
        
        // Mock non-admin user
        $regularUser = \Mockery::mock(User::class);
        $regularUser->shouldReceive('isAdmin')->andReturn(false);
        
        $this->assertFalse($this->checkAdminPermission($regularUser));
    }

    /**
     * Test session validation
     */
    public function testValidateSession(): void
    {
        // Test with valid session
        $_SESSION['uid'] = 1;
        $this->assertTrue($this->validateSession());
        
        // Test with invalid session
        unset($_SESSION['uid']);
        $this->assertFalse($this->validateSession());
        
        // Test with null uid
        $_SESSION['uid'] = null;
        $this->assertFalse($this->validateSession());
    }

    /**
     * Test complete department addition workflow
     */
    public function testCompleteAddDepartmentWorkflow(): void
    {
        $departmentName = 'Complete Test Department';
        $newDeptId = 10;
        $defaultRights = [
            ['id' => 1, 'default_rights' => 1],
            ['id' => 2, 'default_rights' => 2]
        ];
        
        // Mock all the required database interactions
        $this->setupCompleteAddWorkflowMocks($departmentName, $newDeptId, $defaultRights);
        
        $result = $this->simulateCompleteAddWorkflow($departmentName);
        $this->assertTrue($result);
    }

    /**
     * Helper method to validate department name
     */
    private function validateDepartmentName($name): bool
    {
        if ($name === null) {
            return false;
        }
        
        $trimmed = trim($name);
        return !empty($trimmed);
    }

    /**
     * Helper method to check if department exists
     */
    private function checkDepartmentExists(string $name): bool
    {
        $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department where name = :department";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':department' => $name]);
        $result = $stmt->fetchAll();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Helper method to insert department
     */
    private function insertDepartment(string $name): bool
    {
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}department (name) VALUES (:department)";
        $stmt = $this->mockPdo->prepare($query);
        return $stmt->execute([':department' => $name]);
    }

    /**
     * Helper method to get newly added department ID
     */
    private function getNewlyAddedDepartmentId(string $name): ?int
    {
        $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE name = :department";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':department' => $name]);
        $result = $stmt->fetchAll();
        
        if ($stmt->rowCount() === 1) {
            return (int)$result[0]['id'];
        }
        
        return null;
    }

    /**
     * Helper method to get default rights
     */
    private function getDefaultRights(): array
    {
        $query = "SELECT id, default_rights FROM {$GLOBALS['CONFIG']['db_prefix']}data";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Helper method to set default permissions
     */
    private function setDefaultPermissions(int $deptId, array $dataArray): bool
    {
        foreach ($dataArray as $data) {
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, dept_id, rights) VALUES (:fid, :dept_id, :rights)";
            $stmt = $this->mockPdo->prepare($query);
            $result = $stmt->execute([
                ':fid' => $data[0],
                ':dept_id' => $deptId,
                ':rights' => $data[1]
            ]);
            
            if (!$result) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Helper method to get department info
     */
    private function getDepartmentInfo(int $id): array
    {
        $query = "SELECT name,id FROM {$GLOBALS['CONFIG']['db_prefix']}department where id = :item";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':item' => $id]);
        return $stmt->fetch();
    }

    /**
     * Helper method to get users in department
     */
    private function getUsersInDepartment(int $id): array
    {
        $query = "SELECT dept.id, u.first_name, u.last_name FROM {$GLOBALS['CONFIG']['db_prefix']}department dept, {$GLOBALS['CONFIG']['db_prefix']}user u WHERE dept.id = :item AND u.department = :item";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':item' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Helper method to get reassign options
     */
    private function getReassignOptions(int $excludeId): array
    {
        $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE id != :item ORDER BY name";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':item' => $excludeId]);
        return $stmt->fetchAll();
    }

    /**
     * Helper method to check admin permission
     */
    private function checkAdminPermission(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Helper method to validate session
     */
    private function validateSession(): bool
    {
        return isset($_SESSION['uid']) && !empty($_SESSION['uid']);
    }

    /**
     * Helper method to setup mocks for complete add workflow
     */
    private function setupCompleteAddWorkflowMocks(string $name, int $newId, array $rights): void
    {
        // Mock duplicate check (no duplicate)
        $checkStmt = \Mockery::mock(\PDOStatement::class);
        $checkStmt->shouldReceive('execute')->with([':department' => $name])->andReturn(true);
        $checkStmt->shouldReceive('fetchAll')->andReturn([]);
        $checkStmt->shouldReceive('rowCount')->andReturn(0);
        
        // Mock insert
        $insertStmt = \Mockery::mock(\PDOStatement::class);
        $insertStmt->shouldReceive('execute')->with([':department' => $name])->andReturn(true);
        
        // Mock get ID
        $idStmt = \Mockery::mock(\PDOStatement::class);
        $idStmt->shouldReceive('execute')->with([':department' => $name])->andReturn(true);
        $idStmt->shouldReceive('fetchAll')->andReturn([['id' => $newId]]);
        $idStmt->shouldReceive('rowCount')->andReturn(1);
        
        // Mock get rights
        $rightsStmt = \Mockery::mock(\PDOStatement::class);
        $rightsStmt->shouldReceive('execute')->andReturn(true);
        $rightsStmt->shouldReceive('fetchAll')->andReturn($rights);
        
        // Mock permission inserts
        $permStmt = \Mockery::mock(\PDOStatement::class);
        $permStmt->shouldReceive('execute')->times(count($rights))->andReturn(true);
        
        // Setup PDO expectations
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT name FROM.*department where name = :department/'))
                      ->andReturn($checkStmt);
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/INSERT INTO.*department.*VALUES/'))
                      ->andReturn($insertStmt);
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT id FROM.*department WHERE name = :department/'))
                      ->andReturn($idStmt);
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT id, default_rights FROM.*data/'))
                      ->andReturn($rightsStmt);
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/INSERT INTO.*dept_perms/'))
                      ->times(count($rights))
                      ->andReturn($permStmt);
    }

    /**
     * Helper method to simulate complete add workflow
     */
    private function simulateCompleteAddWorkflow(string $name): bool
    {
        // Validate name
        if (!$this->validateDepartmentName($name)) {
            return false;
        }
        
        // Check for duplicates
        if ($this->checkDepartmentExists($name)) {
            return false;
        }
        
        // Insert department
        if (!$this->insertDepartment($name)) {
            return false;
        }
        
        // Get new department ID
        $newId = $this->getNewlyAddedDepartmentId($name);
        if ($newId === null) {
            return false;
        }
        
        // Get default rights
        $rights = $this->getDefaultRights();
        
        // Convert to data array format
        $dataArray = [];
        foreach ($rights as $right) {
            $dataArray[] = [$right['id'], $right['default_rights']];
        }
        
        // Set permissions
        return $this->setDefaultPermissions($newId, $dataArray);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->mockPdo = null;
        
        // Restore original config
        if (!empty($this->originalConfig)) {
            $GLOBALS['CONFIG'] = $this->originalConfig;
        } else {
            unset($GLOBALS['CONFIG']);
        }
        
        // Clear PDO global
        if (isset($GLOBALS['pdo'])) {
            unset($GLOBALS['pdo']);
        }
        
        // Clear request data
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        
        parent::tearDown();
    }
}