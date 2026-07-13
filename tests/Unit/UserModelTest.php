<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User model class
 * 
 * This test class provides comprehensive testing for the User model,
 * focusing on functionality not already covered in the existing UserTest.php.
 */
class UserModelTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var User
     */
    private $user;

    /**
     * @var PDO|\Mockery\MockInterface
     */
    private $mockConnection;

    /**
     * @var \PDOStatement|\Mockery\MockInterface
     */
    private $mockStatement;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up global configuration to prevent errors
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'database_prefix' => 'odm_',
            'db_prefix' => 'odm_',
            'base_url' => 'http://localhost/opendocman/'
        ];
        
        // Create mock database connection and statement
        $this->mockConnection = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Set up default mock behavior for User constructor query
        // User constructor expects data in array format for list() unpacking
        $userData = [
            1,              // id
            'testuser',     // username
            2,              // department
            '555-1234',     // phone
            'test@example.com', // email
            'User',         // last_name
            'Test',         // first_name
            null,           // pw_reset_code
            1,              // can_add
            1               // can_checkin
        ];
        
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn($userData)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('fetchColumn')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(1)->byDefault();
        
        $this->mockConnection->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        
        // Create User instance with mock connection
        $this->user = new User(1, $this->mockConnection);
    }

    /**
     * Test that User class can be instantiated properly
     */
    public function testUserCanBeInstantiated(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertInstanceOf('databaseData', $this->user);
    }

    /**
     * Test that User class constant is properly defined
     */
    public function testUserClassConstantIsDefined(): void
    {
        $this->assertTrue(defined('User_class'));
        $this->assertEquals('true', User_class);
    }

    /**
     * Test User constructor sets up correct configuration
     */
    public function testConstructorSetsCorrectConfiguration(): void
    {
        $this->assertEquals('username', $this->user->field_name);
        $this->assertEquals('id', $this->user->field_id);
        $this->assertEquals($this->user->TABLE_USER, $this->user->tablename);
        $this->assertEquals(1, $this->user->result_limit);
        $this->assertEquals(1, $this->user->root_id);
    }

    /**
     * Test User inherits database table constants from parent class
     */
    public function testUserInheritsTableConstants(): void
    {
        $this->assertEquals('user', $this->user->TABLE_USER);
        $this->assertEquals('admin', $this->user->TABLE_ADMIN);
        $this->assertEquals('category', $this->user->TABLE_CATEGORY);
        $this->assertEquals('data', $this->user->TABLE_DATA);
        $this->assertEquals('department', $this->user->TABLE_DEPARTMENT);
        $this->assertEquals('dept_perms', $this->user->TABLE_DEPT_PERMS);
        $this->assertEquals('dept_reviewer', $this->user->TABLE_DEPT_REVIEWER);
        $this->assertEquals('log', $this->user->TABLE_LOG);
        $this->assertEquals('rights', $this->user->TABLE_RIGHTS);
        $this->assertEquals('user_perms', $this->user->TABLE_USER_PERMS);
    }

    /**
     * Test User inherits permission constants from parent class
     */
    public function testUserInheritsPermissionConstants(): void
    {
        $this->assertEquals(-1, $this->user->FORBIDDEN_RIGHT);
        $this->assertEquals(0, $this->user->NONE_RIGHT);
        $this->assertEquals(1, $this->user->VIEW_RIGHT);
        $this->assertEquals(2, $this->user->READ_RIGHT);
        $this->assertEquals(3, $this->user->WRITE_RIGHT);
        $this->assertEquals(4, $this->user->ADMIN_RIGHT);
    }

    /**
     * Test User has required properties
     */
    public function testUserHasRequiredProperties(): void
    {
        $expectedProperties = [
            'root_id',
            'id',
            'username',
            'first_name',
            'last_name',
            'email',
            'phone',
            'department',
            'pw_reset_code',
            'can_add',
            'can_checkin'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                property_exists($this->user, $property),
                "User class should have property: {$property}"
            );
        }
    }

    /**
     * Test getDeptId method
     */
    public function testGetDeptId(): void
    {
        $this->user->department = 5;
        $result = $this->user->getDeptId();
        $this->assertEquals(5, $result);
    }

    /**
     * Test isRoot method
     */
    public function testIsRoot(): void
    {
        // Test root user
        $this->user->root_id = 1;
        $this->user->id = 1;
        $this->assertTrue($this->user->isRoot());
        
        // Test non-root user
        $this->user->id = 2;
        $this->assertFalse($this->user->isRoot());
    }

    /**
     * Test isAdmin method returns true for root user
     */
    public function testIsAdminReturnsTrueForRootUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1;
        
        $result = $this->user->isAdmin();
        $this->assertTrue($result);
    }

    /**
     * Test canAdd method for admin user (root)
     */
    public function testCanAddForRootUser(): void
    {
        // Mock isAdmin to return true
        $this->user->root_id = 1;
        $this->user->id = 1;
        
        $result = $this->user->canAdd();
        $this->assertTrue($result);
    }

    /**
     * Test canAdd method for user with can_add permission
     */
    public function testCanAddForUserWithPermission(): void
    {
        // Mock non-admin user with permission
        $this->user->root_id = 1;
        $this->user->id = 2;
        $this->user->can_add = 1;
        
        // Create mock for isAdmin check
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->andReturn(true);
        $mockStatement->shouldReceive('fetchColumn')->andReturn(0);
        $mockStatement->shouldReceive('rowCount')->andReturn(1);
        
        $mockConnection = \Mockery::mock(PDO::class);
        $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
        
        // Set connection via reflection
        $reflection = new ReflectionClass($this->user);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setValue($this->user, $mockConnection);
        
        $result = $this->user->canAdd();
        $this->assertTrue($result);
    }

    /**
     * Test canCheckIn method for admin user (root)
     */
    public function testCanCheckInForRootUser(): void
    {
        // Mock isAdmin to return true
        $this->user->root_id = 1;
        $this->user->id = 1;
        
        $result = $this->user->canCheckIn();
        $this->assertTrue($result);
    }

    /**
     * Test canCheckIn method for user with can_checkin permission
     */
    public function testCanCheckInForUserWithPermission(): void
    {
        // Mock non-admin user with permission
        $this->user->root_id = 1;
        $this->user->id = 2;
        $this->user->can_checkin = 1;
        
        // Create mock for isAdmin check
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->andReturn(true);
        $mockStatement->shouldReceive('fetchColumn')->andReturn(0);
        $mockStatement->shouldReceive('rowCount')->andReturn(1);
        
        $mockConnection = \Mockery::mock(PDO::class);
        $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
        
        // Set connection via reflection
        $reflection = new ReflectionClass($this->user);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setValue($this->user, $mockConnection);
        
        $result = $this->user->canCheckIn();
        $this->assertTrue($result);
    }

    /**
     * Test isReviewer method for admin user
     */
    public function testIsReviewerForAdminUser(): void
    {
        // Mock isAdmin to return true
        $this->user->root_id = 1;
        $this->user->id = 1;
        
        $result = $this->user->isReviewer();
        $this->assertTrue($result);
    }

    /**
     * Test getAllUsers static method
     */
    public function testGetAllUsers(): void
    {
        $expectedUsers = [
            ['id' => 1, 'username' => 'user1', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 2, 'username' => 'user2', 'first_name' => 'Jane', 'last_name' => 'Smith']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($expectedUsers);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
                ->once()
                ->with(\Mockery::pattern('/SELECT id, username, first_name, last_name FROM.*user ORDER BY username/'))
                ->andReturn($mockStatement);
        
        $result = User::getAllUsers($mockPdo);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($expectedUsers, $result);
    }

    /**
     * Test getEmailAddress method
     */
    public function testGetEmailAddress(): void
    {
        $this->user->email = 'test@example.com';
        $result = $this->user->getEmailAddress();
        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test getPhoneNumber method
     */
    public function testGetPhoneNumber(): void
    {
        $this->user->phone = '555-1234';
        $result = $this->user->getPhoneNumber();
        $this->assertEquals('555-1234', $result);
    }

    /**
     * Test getFullName method
     */
    public function testGetFullName(): void
    {
        $this->user->first_name = 'John';
        $this->user->last_name = 'Doe';
        
        $result = $this->user->getFullName();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertEquals('Doe', $result[1]);
    }

    /**
     * Test getUserName method
     */
    public function testGetUserName(): void
    {
        $this->user->username = 'testuser';
        $result = $this->user->getUserName();
        $this->assertEquals('testuser', $result);
    }

    /**
     * Test User properties can be set and retrieved
     */
    public function testUserPropertiesCanBeSetAndRetrieved(): void
    {
        $testData = [
            'username' => 'johndoe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-0123',
            'department' => 2,
            'can_add' => 1,
            'can_checkin' => 0
        ];

        foreach ($testData as $property => $value) {
            $this->user->$property = $value;
            $this->assertEquals($value, $this->user->$property, "Property {$property} should be settable");
        }
    }

    /**
     * Test User with null values
     */
    public function testUserWithNullValues(): void
    {
        $this->user->pw_reset_code = null;
        $this->user->phone = null;
        
        $this->assertNull($this->user->pw_reset_code);
        $this->assertNull($this->user->phone);
    }

    /**
     * Test User boolean properties
     */
    public function testUserBooleanProperties(): void
    {
        // Test can_add property
        $this->user->can_add = true;
        $this->assertTrue((bool)$this->user->can_add);
        
        $this->user->can_add = false;
        $this->assertFalse((bool)$this->user->can_add);
        
        // Test can_checkin property
        $this->user->can_checkin = 1;
        $this->assertEquals(1, $this->user->can_checkin);
        
        $this->user->can_checkin = 0;
        $this->assertEquals(0, $this->user->can_checkin);
    }

    /**
     * Test email validation pattern (basic format check)
     */
    public function testEmailPropertyAcceptsValidFormat(): void
    {
        $validEmails = [
            'user@example.com',
            'test.email+tag@domain.co.uk'
        ];
        
        foreach ($validEmails as $email) {
            $this->user->email = $email;
            $this->assertEquals($email, $this->user->email);
            $this->assertTrue(filter_var($this->user->email, FILTER_VALIDATE_EMAIL) !== false);
        }
        
        // Test localhost separately as it may not validate with FILTER_VALIDATE_EMAIL
        $this->user->email = 'admin@localhost';
        $this->assertEquals('admin@localhost', $this->user->email);
    }

    /**
     * Test User with realistic data set
     */
    public function testUserWithRealisticDataSet(): void
    {
        $userData = [
            'id' => 42,
            'username' => 'alice_johnson',
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice.johnson@company.com',
            'phone' => '+1-555-0199',
            'department' => 3,
            'pw_reset_code' => 'abc123def456',
            'can_add' => 1,
            'can_checkin' => 1
        ];
        
        foreach ($userData as $property => $value) {
            $this->user->$property = $value;
        }
        
        // Verify all data was set correctly
        foreach ($userData as $property => $expectedValue) {
            $this->assertEquals(
                $expectedValue, 
                $this->user->$property,
                "Property {$property} should equal {$expectedValue}"
            );
        }
    }

    /**
     * Test User constructor sets connection property
     */
    public function testConstructorSetsConnectionProperty(): void
    {
        $reflection = new ReflectionClass($this->user);
        $connectionProperty = $reflection->getProperty('connection');
        
        $this->assertSame($this->mockConnection, $connectionProperty->getValue($this->user));
    }

    /**
     * Test User constructor error handling with invalid user
     */
    public function testConstructorErrorHandlingWithInvalidUser(): void
    {
        $mockConnection = \Mockery::mock(PDO::class);
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Mock user not found scenario
        $mockStatement->shouldReceive('execute')->andReturn(true);
        $mockStatement->shouldReceive('fetch')->andReturn(false);
        $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
        
        $user = new User(999, $mockConnection);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->error);
        $this->assertStringContainsString('Failed to initialize user', $user->error);
    }

    /**
     * Test department property assignment
     */
    public function testDepartmentPropertyAssignment(): void
    {
        $departments = [1, 2, 3, 'Engineering', 'Marketing'];
        
        foreach ($departments as $dept) {
            $this->user->department = $dept;
            $this->assertEquals($dept, $this->user->department);
        }
    }

    /**
     * Test permission property values
     */
    public function testPermissionPropertyValues(): void
    {
        // Test can_add values
        $addValues = [0, 1, '0', '1', true, false];
        foreach ($addValues as $value) {
            $this->user->can_add = $value;
            $this->assertEquals($value, $this->user->can_add);
        }
        
        // Test can_checkin values
        $checkinValues = [0, 1, '0', '1', true, false];
        foreach ($checkinValues as $value) {
            $this->user->can_checkin = $value;
            $this->assertEquals($value, $this->user->can_checkin);
        }
    }

    /**
     * Test User ID assignment and retrieval
     */
    public function testUserIdAssignmentAndRetrieval(): void
    {
        $userIds = [1, 2, 42, 999, '5', '10'];
        
        foreach ($userIds as $id) {
            $this->user->id = $id;
            $this->assertEquals($id, $this->user->id);
        }
    }

    /**
     * Test getDeptName method
     */
    public function testGetDeptName(): void
    {
        $expectedDeptName = 'Engineering';
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn($expectedDeptName);
        
        $result = $this->user->getDeptName();
        $this->assertEquals($expectedDeptName, $result);
    }

    /**
     * Test getPublishedData method
     */
    public function testGetPublishedData(): void
    {
        $publishable = 1;
        $expectedData = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($expectedData);
        
        $result = $this->user->getPublishedData($publishable);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test getPublishedData with empty result
     */
    public function testGetPublishedDataEmpty(): void
    {
        $publishable = 0;
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);
        
        $result = $this->user->getPublishedData($publishable);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test isAdmin method for non-admin, non-root user
     */
    public function testIsAdminForNonAdminUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock database query to return no admin record
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->isAdmin();
        $this->assertFalse($result);
    }

    /**
     * Test isAdmin method for admin user (non-root)
     */
    public function testIsAdminForAdminUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock database query to return admin record
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn('1');
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->isAdmin();
        $this->assertTrue((bool)$result);
    }

    /**
     * Test getPassword method
     */
    public function testGetPassword(): void
    {
        $expectedPassword = 'hashed_password_123';
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn($expectedPassword);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->getPassword();
        $this->assertEquals($expectedPassword, $result);
    }

    /**
     * Test changePassword method
     */
    public function testChangePassword(): void
    {
        $newPassword = 'new_password_123';
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn(true);
        
        $result = $this->user->changePassword($newPassword);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with valid password
     */
    public function testValidatePasswordWithValidPassword(): void
    {
        $password = 'correct_password';
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->validatePassword($password);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with invalid password using old password() style
     */
    public function testValidatePasswordWithOldStylePassword(): void
    {
        $password = 'old_style_password';
        
        // First query returns 0 rows (md5 style fails)
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        // Second query returns 1 row (password() style succeeds)
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->validatePassword($password);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with invalid password
     */
    public function testValidatePasswordWithInvalidPassword(): void
    {
        $password = 'wrong_password';
        
        // Both queries return 0 rows
        $this->mockStatement->shouldReceive('rowCount')
            ->twice()
            ->andReturn(0);
        
        $result = $this->user->validatePassword($password);
        $this->assertFalse($result);
    }

    /**
     * Test changeName method
     */
    public function testChangeName(): void
    {
        $newName = 'new_username';
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn(true);
        
        $result = $this->user->changeName($newName);
        $this->assertTrue($result);
    }

    /**
     * Test isReviewer method for admin user
     */
    public function testIsReviewerForAdmin(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1; // Root user (admin)
        
        $result = $this->user->isReviewer();
        $this->assertTrue($result);
    }

    /**
     * Test isReviewer method for non-admin reviewer
     */
    public function testIsReviewerForNonAdminReviewer(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock admin check to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        // Mock reviewer check to return true
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->isReviewer();
        $this->assertTrue($result);
    }

    /**
     * Test isReviewer method for non-reviewer user
     */
    public function testIsReviewerForNonReviewer(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock admin check to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        // Mock reviewer check to return false
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->isReviewer();
        $this->assertFalse($result);
    }

    /**
     * Test isReviewerForFile method with valid file
     */
    public function testIsReviewerForFileWithValidFile(): void
    {
        $fileId = 123;
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->isReviewerForFile($fileId);
        $this->assertTrue($result);
    }

    /**
     * Test isReviewerForFile method with invalid file
     */
    public function testIsReviewerForFileWithInvalidFile(): void
    {
        $fileId = 999;
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->isReviewerForFile($fileId);
        $this->assertFalse($result);
    }

    /**
     * Test getAllRevieweeIds method for admin user
     */
    public function testGetAllRevieweeIdsForAdmin(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1; // Root user (admin)
        
        $mockData = [
            [0 => '1'],
            [0 => '2'],
            [0 => '3']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockData);
        
        $result = $this->user->getAllRevieweeIds();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['1', '2', '3'], $result);
    }

    /**
     * Test getAllRevieweeIds method for non-admin user
     */
    public function testGetAllRevieweeIdsForNonAdmin(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock admin check to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getAllRevieweeIds();
        $this->assertNull($result);
    }

    /**
     * Test getCheckedOutFiles method
     */
    public function testGetCheckedOutFiles(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1; // Root user
        
        $expectedFiles = [
            [0 => '1'],
            [0 => '2']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($expectedFiles);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        $result = $this->user->getCheckedOutFiles();
        $this->assertIsArray($result);
        $this->assertEquals(['1', '2'], $result);
    }

    /**
     * Test getAllUsers static method with mock data
     */
    public function testGetAllUsersWithMockData(): void
    {
        $expectedUsers = [
            ['id' => 1, 'username' => 'admin'],
            ['id' => 2, 'username' => 'user1'],
            ['id' => 3, 'username' => 'user2']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($expectedUsers);
        
        $result = User::getAllUsers($this->mockConnection);
        $this->assertIsArray($result);
        $this->assertEquals($expectedUsers, $result);
    }

    /**
     * Test getNumExpiredFiles method
     */
    public function testGetNumExpiredFiles(): void
    {
        $expectedCount = 5;
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn($expectedCount);
        
        $result = $this->user->getNumExpiredFiles();
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test canAdd method for user without permission
     */
    public function testCanAddForUserWithoutPermission(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        $this->user->can_add = 0; // No add permission
        
        // Mock admin check to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->canAdd();
        $this->assertFalse($result);
    }

    /**
     * Test canCheckIn method for user without permission
     */
    public function testCanCheckInForUserWithoutPermission(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        $this->user->can_checkin = 0; // No checkin permission
        
        // Mock admin check to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->canCheckIn();
        $this->assertFalse($result);
    }

    /**
     * Test getRevieweeIds method for reviewer
     */
    public function testGetRevieweeIdsForReviewer(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock isReviewer to return true
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        // Mock department query
        $deptData = [['dept_id' => 1], ['dept_id' => 2]];
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($deptData);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        // Mock files query
        $fileData = [['id' => 1], ['id' => 2]];
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($fileData);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        $result = $this->user->getRevieweeIds();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test getRevieweeIds method for non-reviewer
     */
    public function testGetRevieweeIdsForNonReviewer(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock isReviewer to return false
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        $this->mockStatement->shouldReceive('rowCount')
            ->twice()
            ->andReturn(0);
        
        $result = $this->user->getRevieweeIds();
        $this->assertNull($result);
    }

    /**
     * Test getAllRejectedFileIds method
     */
    public function testGetAllRejectedFileIds(): void
    {
        $rejectedFiles = [
            [0 => '1'],
            [0 => '2'],
            [0 => '3']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($rejectedFiles);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(3);
        
        $result = $this->user->getAllRejectedFileIds();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['1', '2', '3'], $result);
    }

    /**
     * Test getRejectedFileIds method
     */
    public function testGetRejectedFileIds(): void
    {
        $rejectedFiles = [
            [0 => '1'],
            [0 => '2']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($rejectedFiles);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        $result = $this->user->getRejectedFileIds();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(['1', '2'], $result);
    }

    /**
     * Test getExpiredFileIds method
     */
    public function testGetExpiredFileIds(): void
    {
        $expiredFiles = [
            [0 => '1'],
            [0 => '2']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($expiredFiles);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        $result = $this->user->getExpiredFileIds();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(['1', '2'], $result);
    }

    /**
     * Test getCheckedOutFiles method for root user
     */
    public function testGetCheckedOutFilesForRootUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1; // Root user
        
        $checkedOutFiles = [
            [0 => '1'],
            [0 => '2'],
            [0 => '3']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($checkedOutFiles);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(3);
        
        $result = $this->user->getCheckedOutFiles();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['1', '2', '3'], $result);
    }

    /**
     * Test getCheckedOutFiles method for non-root user
     */
    public function testGetCheckedOutFilesForNonRootUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        $result = $this->user->getCheckedOutFiles();
        $this->assertNull($result);
    }

    /**
     * Test static getAllUsers method with PDO connection
     */
    public function testGetAllUsersStaticWithPDO(): void
    {
        $users = [
            ['id' => 1, 'username' => 'admin', 'first_name' => 'Admin', 'last_name' => 'User'],
            ['id' => 2, 'username' => 'user1', 'first_name' => 'John', 'last_name' => 'Doe']
        ];
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($users);
        
        $result = User::getAllUsers($this->mockConnection);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($users, $result);
    }

    /**
     * Test getPassword method - valid case only
     * Note: Invalid case with header redirect cannot be easily tested in PHPUnit
     * due to header() and exit() calls. This test covers the successful path.
     */
    public function testGetPasswordValidCase(): void
    {
        $expectedPassword = 'valid_password_hash';
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn($expectedPassword);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1); // Valid case - exactly 1 row
        
        $result = $this->user->getPassword();
        $this->assertEquals($expectedPassword, $result);
        
        // Note: The error case (rowCount != 1) calls header() and exit()
        // which cannot be properly tested in a unit test environment.
        // This would require integration testing or refactoring the method
        // to use dependency injection for the redirect functionality.
    }

    /**
     * Test constructor with additional coverage scenarios
     */
    public function testConstructorAdditionalCoverage(): void
    {
        // Test various ID types to improve constructor coverage
        $testIds = [10, '15', 20];
        
        foreach ($testIds as $testId) {
            $mockConnection = \Mockery::mock(PDO::class);
            $mockStatement = \Mockery::mock(\PDOStatement::class);
            
            // Basic mock setup for each constructor call
            $mockStatement->shouldReceive('execute')->andReturn(true);
            $mockStatement->shouldReceive('fetch')->andReturn(false);
            $mockStatement->shouldReceive('fetchAll')->andReturn([]);
            $mockStatement->shouldReceive('rowCount')->andReturn(0);
            
            $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
            
            $user = new User($testId, $mockConnection);
            
            // Verify basic constructor behavior
            $this->assertInstanceOf(User::class, $user);
            $this->assertEquals('username', $user->field_name);
            $this->assertEquals('id', $user->field_id);
            $this->assertEquals(1, $user->result_limit);
            $this->assertEquals(1, $user->root_id);
        }
    }

    /**
     * Test additional edge cases to improve coverage
     */
    public function testAdditionalEdgeCases(): void
    {
        // Test properties with various edge case values
        $edgeCaseValues = [
            'username' => ['', null, '0', 'admin', 'test@domain.com'],
            'department' => [0, null, '0', 1, 99],
            'can_add' => [0, 1, '0', '1', true, false],
            'can_checkin' => [0, 1, '0', '1', true, false]
        ];
        
        foreach ($edgeCaseValues as $property => $values) {
            foreach ($values as $value) {
                $this->user->$property = $value;
                $this->assertEquals($value, $this->user->$property, 
                    "Failed to set property {$property} to value: " . var_export($value, true));
            }
        }
        
        // Test numeric string conversions
        $this->user->id = '123';
        $this->assertEquals('123', $this->user->id);
        
        $this->user->department = '42';
        $this->assertEquals('42', $this->user->department);
        
        // Test boolean-like values
        $this->user->can_add = '';
        $this->assertEquals('', $this->user->can_add);
        
        $this->user->can_checkin = null;
        $this->assertNull($this->user->can_checkin);
    }

    /**
     * Test edge cases for array methods with empty results
     */
    public function testArrayMethodsWithEmptyResults(): void
    {
        // Test getAllRejectedFileIds with empty result
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getAllRejectedFileIds();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        // Test getRejectedFileIds with empty result
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getRejectedFileIds();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        // Test getExpiredFileIds with empty result
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getExpiredFileIds();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getNumExpiredFiles method with count
     */
    public function testGetNumExpiredFilesWithCount(): void
    {
        $expectedCount = 3;
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn($expectedCount);
        
        $result = $this->user->getNumExpiredFiles();
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test getNumExpiredFiles method with zero count
     */
    public function testGetNumExpiredFilesWithZeroCount(): void
    {
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getNumExpiredFiles();
        $this->assertEquals(0, $result);
    }

    /**
     * Test User constructor with different scenarios
     */
    public function testConstructorWithVariousScenarios(): void
    {
        // Test with different user IDs
        $userIds = [1, 2, 999, '5'];
        
        foreach ($userIds as $userId) {
            $mockConnection = \Mockery::mock(PDO::class);
            $mockStatement = \Mockery::mock(\PDOStatement::class);
            $mockStatement->shouldReceive('execute')->andReturn(true);
            $mockStatement->shouldReceive('fetch')->andReturn(false);
            $mockStatement->shouldReceive('fetchAll')->andReturn([]);
            $mockStatement->shouldReceive('rowCount')->andReturn(0);
            $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
            
            $user = new User($userId, $mockConnection);
            $this->assertInstanceOf(User::class, $user);
        }
    }

    /**
     * Test User property assignment edge cases
     */
    public function testUserPropertyEdgeCases(): void
    {
        // Test with various data types
        $this->user->username = '';
        $this->assertEquals('', $this->user->username);
        
        $this->user->username = null;
        $this->assertNull($this->user->username);
        
        $this->user->department = 0;
        $this->assertEquals(0, $this->user->department);
        
        $this->user->can_add = '1';
        $this->assertEquals('1', $this->user->can_add);
        
        $this->user->can_checkin = false;
        $this->assertFalse($this->user->can_checkin);
    }

    /**
     * Test exists static method returns true for existing user
     */
    public function testExistsReturnsTrueForExistingUser(): void
    {
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        $mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(1);

        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern('/SELECT COUNT\(\*\) FROM.*user WHERE id = :id/'))
            ->andReturn($mockStatement);

        $result = User::exists(1, $mockPdo);
        $this->assertTrue($result);
    }

    /**
     * Test exists static method returns false for non-existing user
     */
    public function testExistsReturnsFalseForNonExistingUser(): void
    {
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        $mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(0);

        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern('/SELECT COUNT\(\*\) FROM.*user WHERE id = :id/'))
            ->andReturn($mockStatement);

        $result = User::exists(999, $mockPdo);
        $this->assertFalse($result);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->user = null;
        $this->mockConnection = null;
        $this->mockStatement = null;
        
        parent::tearDown();
    }
}