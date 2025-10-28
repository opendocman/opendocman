<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Department class
 * 
 * This test class provides comprehensive testing for the Department model,
 * including constructor behavior, static methods, inheritance, and database interaction mocking.
 */
class DepartmentTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Department
     */
    private $department;

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
            'db_prefix' => 'odm_'
        ];
        
        // Create mock database connection and statement
        $this->mockConnection = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Set up default mock behavior
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        
        $this->mockConnection->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        
        // Create Department instance with mock connection
        $this->department = new Department(1, $this->mockConnection);
    }

    /**
     * Test that Department class can be instantiated properly
     */
    public function testDepartmentCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Department::class, $this->department);
        $this->assertInstanceOf('databaseData', $this->department);
    }

    /**
     * Test that Department class is properly defined
     */
    public function testDepartmentClassConstantIsDefined(): void
    {
        $this->assertTrue(defined('Department_class'));
        $this->assertEquals('true', Department_class);
    }

    /**
     * Test Department constructor sets up correct table configuration
     */
    public function testConstructorSetsCorrectTableConfiguration(): void
    {
        $this->assertEquals('name', $this->department->field_name);
        $this->assertEquals('id', $this->department->field_id);
        $this->assertEquals($this->department->TABLE_DEPARTMENT, $this->department->tablename);
        $this->assertEquals(1, $this->department->result_limit);
    }

    /**
     * Test Department constructor with different ID
     */
    public function testConstructorWithDifferentId(): void
    {
        $departmentId = 5;
        
        // Create another mock connection for this test
        $mockConnection2 = \Mockery::mock(PDO::class);
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')->andReturn(true);
        $mockStatement2->shouldReceive('fetch')->andReturn(false);
        $mockStatement2->shouldReceive('fetchAll')->andReturn([]);
        $mockStatement2->shouldReceive('rowCount')->andReturn(0);
        $mockConnection2->shouldReceive('prepare')->andReturn($mockStatement2);
        
        $department = new Department($departmentId, $mockConnection2);
        
        $this->assertInstanceOf(Department::class, $department);
        $this->assertEquals('name', $department->field_name);
        $this->assertEquals('id', $department->field_id);
        $this->assertEquals(1, $department->result_limit);
    }

    /**
     * Test Department inherits database table constants from parent class
     */
    public function testDepartmentInheritsTableConstants(): void
    {
        $this->assertEquals('user', $this->department->TABLE_USER);
        $this->assertEquals('admin', $this->department->TABLE_ADMIN);
        $this->assertEquals('category', $this->department->TABLE_CATEGORY);
        $this->assertEquals('data', $this->department->TABLE_DATA);
        $this->assertEquals('department', $this->department->TABLE_DEPARTMENT);
        $this->assertEquals('dept_perms', $this->department->TABLE_DEPT_PERMS);
        $this->assertEquals('dept_reviewer', $this->department->TABLE_DEPT_REVIEWER);
        $this->assertEquals('log', $this->department->TABLE_LOG);
        $this->assertEquals('rights', $this->department->TABLE_RIGHTS);
        $this->assertEquals('user_perms', $this->department->TABLE_USER_PERMS);
    }

    /**
     * Test Department inherits permission constants from parent class
     */
    public function testDepartmentInheritsPermissionConstants(): void
    {
        $this->assertEquals(-1, $this->department->FORBIDDEN_RIGHT);
        $this->assertEquals(0, $this->department->NONE_RIGHT);
        $this->assertEquals(1, $this->department->VIEW_RIGHT);
        $this->assertEquals(2, $this->department->READ_RIGHT);
        $this->assertEquals(3, $this->department->WRITE_RIGHT);
        $this->assertEquals(4, $this->department->ADMIN_RIGHT);
    }

    /**
     * Test getAllDepartments static method with empty result
     */
    public function testGetAllDepartmentsWithEmptyResult(): void
    {
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn([]);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
                ->once()
                ->with(\Mockery::pattern('/SELECT name, id FROM.*department ORDER by name/'))
                ->andReturn($mockStatement);
        
        $result = Department::getAllDepartments($mockPdo);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getAllDepartments static method with single department
     */
    public function testGetAllDepartmentsWithSingleDepartment(): void
    {
        $departmentData = [
            ['id' => 1, 'name' => 'Engineering']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($departmentData);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
                ->once()
                ->with(\Mockery::pattern('/SELECT name, id FROM.*department ORDER by name/'))
                ->andReturn($mockStatement);
        
        $result = Department::getAllDepartments($mockPdo);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Engineering', $result[0]['name']);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
    }

    /**
     * Test getAllDepartments static method with multiple departments
     */
    public function testGetAllDepartmentsWithMultipleDepartments(): void
    {
        $departmentData = [
            ['id' => 2, 'name' => 'Human Resources'],
            ['id' => 1, 'name' => 'Engineering'],
            ['id' => 3, 'name' => 'Marketing']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($departmentData);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
                ->once()
                ->with(\Mockery::pattern('/SELECT name, id FROM.*department ORDER by name/'))
                ->andReturn($mockStatement);
        
        $result = Department::getAllDepartments($mockPdo);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Verify structure of each department entry
        foreach ($result as $index => $dept) {
            $this->assertArrayHasKey('id', $dept);
            $this->assertArrayHasKey('name', $dept);
            $this->assertEquals($departmentData[$index]['id'], $dept['id']);
            $this->assertEquals($departmentData[$index]['name'], $dept['name']);
        }
    }

    /**
     * Test getAllDepartments uses correct SQL query
     */
    public function testGetAllDepartmentsUsesCorrectQuery(): void
    {
        $expectedQuery = "SELECT name, id FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER by name";
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn([]);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')
                ->once()
                ->with($expectedQuery)
                ->andReturn($mockStatement);
        
        Department::getAllDepartments($mockPdo);
    }

    /**
     * Test that Department has required inherited properties
     */
    public function testDepartmentHasInheritedProperties(): void
    {
        $expectedProperties = [
            'id',
            'name',
            'tablename',
            'field_name',
            'field_id',
            'result_limit',
            'error'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                property_exists($this->department, $property),
                "Department class should have inherited property: {$property}"
            );
        }
    }

    /**
     * Test Department properties can be accessed
     */
    public function testDepartmentPropertiesCanBeAccessed(): void
    {
        // Test that we can access the inherited properties
        $this->department->name = 'Test Department';
        $this->assertEquals('Test Department', $this->department->name);
        
        $this->department->id = 42;
        $this->assertEquals(42, $this->department->id);
    }

    /**
     * Test Department with realistic data
     */
    public function testDepartmentWithRealisticData(): void
    {
        $testData = [
            'id' => 5,
            'name' => 'Information Technology'
        ];
        
        $this->department->id = $testData['id'];
        $this->department->name = $testData['name'];
        
        $this->assertEquals($testData['id'], $this->department->id);
        $this->assertEquals($testData['name'], $this->department->name);
    }

    /**
     * Test Department constructor calls setId method
     */
    public function testConstructorCallsSetId(): void
    {
        $departmentId = 3;
        
        // Create a partial mock to test setId is called
        $mockConnection = \Mockery::mock(PDO::class);
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->andReturn(true);
        $mockStatement->shouldReceive('fetch')->andReturn(false);
        $mockStatement->shouldReceive('fetchAll')->andReturn([]);
        $mockStatement->shouldReceive('rowCount')->andReturn(0);
        $mockConnection->shouldReceive('prepare')->andReturn($mockStatement);
        
        // This tests that the constructor completes without error
        // The actual setId behavior would be tested in the parent class
        $department = new Department($departmentId, $mockConnection);
        $this->assertInstanceOf(Department::class, $department);
    }

    /**
     * Test getAllDepartments return format consistency
     */
    public function testGetAllDepartmentsReturnFormatConsistency(): void
    {
        $departmentData = [
            ['id' => '1', 'name' => 'Sales'],      // String ID
            ['id' => 2, 'name' => 'Support'],      // Integer ID
            ['id' => '3', 'name' => 'Development'] // String ID
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($departmentData);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStatement);
        
        $result = Department::getAllDepartments($mockPdo);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Verify consistent structure regardless of input data types
        for ($i = 0; $i < 3; $i++) {
            $this->assertArrayHasKey('id', $result[$i]);
            $this->assertArrayHasKey('name', $result[$i]);
            $this->assertEquals($departmentData[$i]['id'], $result[$i]['id']);
            $this->assertEquals($departmentData[$i]['name'], $result[$i]['name']);
        }
    }

    /**
     * Test getAllDepartments with special characters in names
     */
    public function testGetAllDepartmentsWithSpecialCharacters(): void
    {
        $departmentData = [
            ['id' => 1, 'name' => 'R&D Department'],
            ['id' => 2, 'name' => 'Finance & Accounting'],
            ['id' => 3, 'name' => 'HR/Personnel']
        ];
        
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('execute')->once()->andReturn(true);
        $mockStatement->shouldReceive('fetchAll')->once()->andReturn($departmentData);
        
        $mockPdo = \Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStatement);
        
        $result = Department::getAllDepartments($mockPdo);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        foreach ($result as $index => $dept) {
            $this->assertEquals($departmentData[$index]['name'], $dept['name']);
        }
    }

    /**
     * Test constructor with connection property
     */
    public function testConstructorSetsConnectionProperty(): void
    {
        // Use reflection to access protected property
        $reflection = new ReflectionClass($this->department);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);
        
        $this->assertSame($this->mockConnection, $connectionProperty->getValue($this->department));
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->department = null;
        $this->mockConnection = null;
        $this->mockStatement = null;
        
        parent::tearDown();
    }
}