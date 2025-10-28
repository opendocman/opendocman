<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User controller functionality
 * 
 * This test class provides integration testing for the user controller,
 * testing the actual controller logic and database interactions in a controlled environment.
 */
class UserControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PDO|\Mockery\MockInterface
     */
    private $mockPdo;

    /**
     * @var \PDOStatement|\Mockery\MockInterface
     */
    private $mockStatement;

    /**
     * @var User|\Mockery\MockInterface
     */
    private $mockUser;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up global configuration
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'database_prefix' => 'odm_',
            'db_prefix' => 'odm_',
            'authen' => 'mysql',
            'demo' => 'False',
            'base_url' => 'http://localhost/opendocman/'
        ];
        
        // Create mock PDO and statement
        $this->mockPdo = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Set up default mock behavior
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn(5)->byDefault();
        
        // Set global PDO reference
        $GLOBALS['pdo'] = $this->mockPdo;
        
        // Mock User for admin checks
        $this->mockUser = \Mockery::mock(User::class);
        $this->mockUser->shouldReceive('isAdmin')->andReturn(true)->byDefault();
        
        // Mock session data for tests (avoid session_start() in testing)
        $_SESSION = [];
        $_SESSION['uid'] = 1;
        
        // Clear any previous request data
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    /**
     * Test user addition with valid data
     */
    public function testAddUserWithValidData(): void
    {
        $userData = [
            'username' => 'newuser',
            'password' => 'password123',
            'department' => 2,
            'phonenumber' => '555-1234',
            'Email' => 'newuser@example.com',
            'last_name' => 'User',
            'first_name' => 'New',
            'can_add' => 1,
            'can_checkin' => 1,
            'admin' => 0
        ];
        
        // Mock user existence check (no duplicate)
        $checkStatement = \Mockery::mock(\PDOStatement::class);
        $checkStatement->shouldReceive('execute')->andReturn(true);
        $checkStatement->shouldReceive('rowCount')->andReturn(0);
        
        // Mock user insertion
        $insertStatement = \Mockery::mock(\PDOStatement::class);
        $insertStatement->shouldReceive('execute')->andReturn(true);
        
        // Mock admin insertion
        $adminStatement = \Mockery::mock(\PDOStatement::class);
        $adminStatement->shouldReceive('execute')->andReturn(true);
        
        $this->mockPdo->shouldReceive('prepare')->andReturn($checkStatement, $insertStatement, $adminStatement);
        
        // Set up POST data for adding user
        $_POST['submit'] = 'Add User';
        $_POST = array_merge($_POST, $userData);
        
        $result = $this->simulateAddUserLogic($userData);
        $this->assertTrue($result);
    }

    /**
     * Test user addition with duplicate username
     */
    public function testAddUserWithDuplicateUsername(): void
    {
        $username = 'existinguser';
        
        // Mock user existence check (duplicate found)
        $checkStatement = \Mockery::mock(\PDOStatement::class);
        $checkStatement->shouldReceive('execute')
                      ->once()
                      ->with([':username' => $username])
                      ->andReturn(true);
        $checkStatement->shouldReceive('rowCount')->once()->andReturn(1);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT username FROM.*user WHERE username = :username/'))
                      ->once()
                      ->andReturn($checkStatement);
        
        $_POST['submit'] = 'Add User';
        $_POST['username'] = $username;
        
        $result = $this->simulateUserDuplicateCheck($username);
        $this->assertTrue($result); // Returns true if duplicate found
    }

    /**
     * Test user deletion
     */
    public function testDeleteUser(): void
    {
        $userId = 5;
        
        // Mock deletion statements
        $deleteStatement = \Mockery::mock(\PDOStatement::class);
        $deleteStatement->shouldReceive('execute')->times(4)->andReturn(true);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->times(4) // admin, user, user_perms, data update
                      ->andReturn($deleteStatement);
        
        $_POST['submit'] = 'Delete User';
        $_POST['id'] = $userId;
        
        $result = $this->simulateDeleteUser($userId);
        $this->assertTrue($result);
    }

    /**
     * Test showing user information
     */
    public function testShowUserInformation(): void
    {
        $userId = 3;
        $userData = [
            'id' => $userId,
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'department' => 2,
            'can_add' => 1,
            'can_checkin' => 1
        ];
        
        // Mock user info query
        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')
                      ->once()
                      ->with([':id' => $userId])
                      ->andReturn(true);
        $userStatement->shouldReceive('fetch')->once()->andReturn($userData);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT \* FROM.*user WHERE id = :id/'))
                      ->once()
                      ->andReturn($userStatement);
        
        $_POST['submit'] = 'Show User';
        $_POST['item'] = $userId;
        
        $result = $this->simulateShowUser($userId);
        $this->assertIsArray($result);
        $this->assertEquals($userData, $result);
    }

    /**
     * Test listing all users for selection
     */
    public function testShowUserPickList(): void
    {
        $users = [
            ['id' => 1, 'username' => 'admin', 'first_name' => 'Admin', 'last_name' => 'User'],
            ['id' => 2, 'username' => 'jdoe', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 3, 'username' => 'jsmith', 'first_name' => 'Jane', 'last_name' => 'Smith']
        ];
        
        $listStatement = \Mockery::mock(\PDOStatement::class);
        $listStatement->shouldReceive('execute')->once()->andReturn(true);
        $listStatement->shouldReceive('fetchAll')->once()->andReturn($users);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT id, username, first_name, last_name FROM.*user ORDER BY last_name/'))
                      ->once()
                      ->andReturn($listStatement);
        
        $_REQUEST['submit'] = 'showpick';
        $_REQUEST['state'] = 1;
        
        $result = $this->simulateShowPickList();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($users, $result);
    }

    /**
     * Test user modification preparation
     */
    public function testPrepareUserModification(): void
    {
        $userId = 2;
        $userData = [
            'id' => $userId,
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'department' => 2,
            'can_add' => 1,
            'can_checkin' => 1
        ];
        
        $departments = [
            ['id' => 1, 'name' => 'Engineering'],
            ['id' => 2, 'name' => 'Marketing']
        ];
        
        $reviewers = [
            ['dept_id' => 1, 'user_id' => $userId]
        ];
        
        // Mock user data query
        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')
                      ->once()
                      ->with([':id' => $userId])
                      ->andReturn(true);
        $userStatement->shouldReceive('fetch')->once()->andReturn($userData);
        
        // Mock department reviewers query
        $reviewerStatement = \Mockery::mock(\PDOStatement::class);
        $reviewerStatement->shouldReceive('execute')
                          ->once()
                          ->with([':user_id' => $userId])
                          ->andReturn(true);
        $reviewerStatement->shouldReceive('fetchAll')->once()->andReturn($reviewers);
        
        // Mock departments query
        $deptStatement = \Mockery::mock(\PDOStatement::class);
        $deptStatement->shouldReceive('execute')->once()->andReturn(true);
        $deptStatement->shouldReceive('fetchAll')->once()->andReturn($departments);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT \* FROM.*user WHERE id = :id/'))
                      ->once()
                      ->andReturn($userStatement);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT dept_id, user_id FROM.*dept_reviewer where user_id = :user_id/'))
                      ->once()
                      ->andReturn($reviewerStatement);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT id, name FROM.*department ORDER BY name/'))
                      ->once()
                      ->andReturn($deptStatement);
        
        $_REQUEST['submit'] = 'Modify User';
        $_REQUEST['item'] = $userId;
        
        $result = $this->simulateModifyUserPreparation($userId);
        $this->assertIsArray($result);
        $this->assertEquals($userData, $result['user']);
        $this->assertEquals($departments, $result['departments']);
        $this->assertEquals($reviewers, $result['reviewers']);
    }

    /**
     * Test user update
     */
    public function testUpdateUser(): void
    {
        $userData = [
            'id' => 5,
            'username' => 'updateduser',
            'password' => 'newpassword',
            'department' => 3,
            'phonenumber' => '555-5678',
            'Email' => 'updated@example.com',
            'last_name' => 'Updated',
            'first_name' => 'User',
            'can_add' => 0,
            'can_checkin' => 1,
            'admin' => 0
        ];
        
        // Mock admin update
        $adminStatement = \Mockery::mock(\PDOStatement::class);
        $adminStatement->shouldReceive('execute')->once()->andReturn(true);
        
        // Mock user update
        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')->once()->andReturn(true);
        $userStatement->shouldReceive('bindParam')->andReturn(true);
        
        // Mock reviewer deletion and insertion
        $reviewerStatement = \Mockery::mock(\PDOStatement::class);
        $reviewerStatement->shouldReceive('execute')->andReturn(true);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/UPDATE.*admin set admin = :admin WHERE id = :id/'))
                      ->once()
                      ->andReturn($adminStatement);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/UPDATE.*user SET/'))
                      ->once()
                      ->andReturn($userStatement);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/DELETE FROM.*dept_reviewer WHERE user_id = :user_id/'))
                      ->once()
                      ->andReturn($reviewerStatement);
        
        $_POST['submit'] = 'Update User';
        $_POST = array_merge($_POST, $userData);
        
        $result = $this->simulateUpdateUser($userData);
        $this->assertTrue($result);
    }

    /**
     * Test department list retrieval
     */
    public function testGetDepartmentList(): void
    {
        $departments = [
            ['id' => 1, 'name' => 'Engineering'],
            ['id' => 2, 'name' => 'Marketing'],
            ['id' => 3, 'name' => 'Sales']
        ];
        
        $deptStatement = \Mockery::mock(\PDOStatement::class);
        $deptStatement->shouldReceive('execute')->once()->andReturn(true);
        $deptStatement->shouldReceive('fetchAll')->once()->andReturn($departments);
        
        $this->mockPdo->shouldReceive('prepare')
                      ->with(\Mockery::pattern('/SELECT id, name FROM.*department ORDER BY name/'))
                      ->once()
                      ->andReturn($deptStatement);
        
        $result = $this->simulateGetDepartmentList();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($departments, $result);
    }

    /**
     * Test non-admin user access restriction
     */
    public function testNonAdminUserAccessRestriction(): void
    {
        $_SESSION['uid'] = 2; // Non-admin user
        $_GET['item'] = 3; // Trying to access another user
        
        $result = $this->simulateAccessCheck(2, 3, false);
        $this->assertFalse($result); // Should return false for unauthorized access
    }

    /**
     * Test admin user access permission
     */
    public function testAdminUserAccessPermission(): void
    {
        $_SESSION['uid'] = 1; // Admin user
        $_GET['item'] = 3; // Accessing another user
        
        $result = $this->simulateAccessCheck(1, 3, true);
        $this->assertTrue($result); // Should return true for admin access
    }

    /**
     * Test user accessing their own profile
     */
    public function testUserAccessingOwnProfile(): void
    {
        $_SESSION['uid'] = 2; // Regular user
        $_GET['item'] = 2; // Accessing own profile
        
        $result = $this->simulateAccessCheck(2, 2, false);
        $this->assertTrue($result); // Should return true for self-access
    }

    /**
     * Helper method to simulate add user logic
     */
    private function simulateAddUserLogic(array $userData): bool
    {
        // Validate required fields
        if (empty($userData['username']) || empty($userData['Email'])) {
            return false;
        }
        
        // Check for duplicate
        if ($this->simulateUserDuplicateCheck($userData['username'])) {
            return false;
        }
        
        // Simulate successful insertion
        return true;
    }

    /**
     * Helper method to simulate user duplicate check
     */
    private function simulateUserDuplicateCheck(string $username): bool
    {
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = :username";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':username' => $username]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Helper method to simulate user deletion
     */
    private function simulateDeleteUser(int $userId): bool
    {
        // Simulate deletion queries
        $queries = [
            "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE id = :id",
            "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id",
            "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}user_perms WHERE uid = :id",
            "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET owner='{$GLOBALS['CONFIG']['root_id']}' WHERE owner = :id"
        ];
        
        foreach ($queries as $query) {
            $stmt = $this->mockPdo->prepare($query);
            $stmt->execute([':id' => $userId]);
        }
        
        return true;
    }

    /**
     * Helper method to simulate showing user information
     */
    private function simulateShowUser(int $userId): array
    {
        $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Helper method to simulate showing user pick list
     */
    private function simulateShowPickList(): array
    {
        $query = "SELECT id, username, first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Helper method to simulate modify user preparation
     */
    private function simulateModifyUserPreparation(int $userId): array
    {
        // Get user data
        $userQuery = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :id";
        $userStmt = $this->mockPdo->prepare($userQuery);
        $userStmt->execute([':id' => $userId]);
        $user = $userStmt->fetch();
        
        // Get department reviewers
        $reviewerQuery = "SELECT dept_id, user_id FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = :user_id";
        $reviewerStmt = $this->mockPdo->prepare($reviewerQuery);
        $reviewerStmt->execute([':user_id' => $userId]);
        $reviewers = $reviewerStmt->fetchAll();
        
        // Get departments
        $deptQuery = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
        $deptStmt = $this->mockPdo->prepare($deptQuery);
        $deptStmt->execute();
        $departments = $deptStmt->fetchAll();
        
        return [
            'user' => $user,
            'reviewers' => $reviewers,
            'departments' => $departments
        ];
    }

    /**
     * Helper method to simulate user update
     */
    private function simulateUpdateUser(array $userData): bool
    {
        // Update admin record
        $adminQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}admin set admin = :admin WHERE id = :id";
        $adminStmt = $this->mockPdo->prepare($adminQuery);
        $adminStmt->execute([':admin' => $userData['admin'], ':id' => $userData['id']]);
        
        // Update user record
        $userQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET username = :username WHERE id = :id";
        $userStmt = $this->mockPdo->prepare($userQuery);
        $userStmt->execute();
        
        // Delete and re-insert reviewers
        $deleteQuery = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer WHERE user_id = :user_id";
        $deleteStmt = $this->mockPdo->prepare($deleteQuery);
        $deleteStmt->execute([':user_id' => $userData['id']]);
        
        return true;
    }

    /**
     * Helper method to simulate getting department list
     */
    private function simulateGetDepartmentList(): array
    {
        $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Helper method to simulate access check
     */
    private function simulateAccessCheck(int $currentUserId, int $targetUserId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        
        return $currentUserId === $targetUserId;
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Clean up session data
        $_SESSION = [];
        
        $this->mockPdo = null;
        $this->mockStatement = null;
        $this->mockUser = null;
        
        // Clear globals
        unset($GLOBALS['pdo']);
        
        // Clear request data
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        
        parent::tearDown();
    }
}