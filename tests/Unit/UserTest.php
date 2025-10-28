<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User class
 * 
 * This test class demonstrates basic unit testing patterns for the User model,
 * including property initialization, method testing, and database interaction mocking.
 */
class UserTest extends TestCase
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
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up global configuration to prevent errors
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'database_prefix' => 'odm_',
            'db_prefix' => 'odm_'
        ];
        
        // Create mock database connection
        $this->mockConnection = \Mockery::mock(PDO::class);
        
        // Create mock statement that prevents database errors
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->andReturn(true);
        $mockStatement->shouldReceive('fetch')->andReturn(false);
        $mockStatement->shouldReceive('fetchAll')->andReturn([]);
        $mockStatement->shouldReceive('rowCount')->andReturn(0);
        
        // Set up connection to return the mock statement
        $this->mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
        
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
     * Test that User class has required properties
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
     * Test User constructor sets up correct table configuration
     */
    public function testConstructorSetsCorrectTableConfiguration(): void
    {
        $this->assertEquals('username', $this->user->field_name);
        $this->assertEquals('id', $this->user->field_id);
        $this->assertEquals($this->user->TABLE_USER, $this->user->tablename);
        $this->assertEquals(1, $this->user->result_limit);
        $this->assertEquals(1, $this->user->root_id);
    }

    /**
     * Test User constructor with different ID
     */
    public function testConstructorWithDifferentId(): void
    {
        $userId = 5;
        
        // Create another mock connection for this test
        $mockConnection2 = \Mockery::mock(PDO::class);
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')->andReturn(true);
        $mockStatement2->shouldReceive('fetch')->andReturn(false);
        $mockStatement2->shouldReceive('fetchAll')->andReturn([]);
        $mockStatement2->shouldReceive('rowCount')->andReturn(0);
        $mockConnection2->shouldReceive('prepare')->andReturn($mockStatement2);
        
        $user = new User($userId, $mockConnection2);
        
        $this->assertInstanceOf(User::class, $user);
        // The actual ID setting behavior would depend on the parent class implementation
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
     * Test that User properties can be set and retrieved
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
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->user = null;
        $this->mockConnection = null;
        
        parent::tearDown();
    }
}