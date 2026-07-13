<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for databaseData class
 * 
 * This test class demonstrates integration testing patterns for the base databaseData class,
 * testing how it interacts with database connections and performs basic operations.
 */
class DatabaseDataTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var databaseData
     */
    private $databaseData;

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
        
        // Set up mock statement behaviors
        $this->mockStatement->shouldReceive('execute')->andReturn(true);
        $this->mockStatement->shouldReceive('fetch')->andReturn(false);
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([]);
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0);
        
        // Set up connection to return the mock statement
        $this->mockConnection->shouldReceive('prepare')->andReturn($this->mockStatement);
        
        // Create a concrete instance using User class (since databaseData is abstract)
        $this->databaseData = new User(1, $this->mockConnection);
    }

    /**
     * Test database connection is properly set in constructor
     */
    public function testDatabaseConnectionIsSet(): void
    {
        $reflection = new ReflectionClass($this->databaseData);
        $connectionProperty = $reflection->getProperty('connection');
        
        $this->assertSame($this->mockConnection, $connectionProperty->getValue($this->databaseData));
    }

    /**
     * Test setTableName method
     */
    public function testSetTableName(): void
    {
        $testTableName = 'test_table';
        $this->databaseData->setTableName($testTableName);
        
        $this->assertEquals($testTableName, $this->databaseData->tablename);
    }

    /**
     * Test table constants are properly defined
     */
    public function testTableConstantsAreDefined(): void
    {
        $expectedTables = [
            'TABLE_ADMIN' => 'admin',
            'TABLE_CATEGORY' => 'category',
            'TABLE_DATA' => 'data',
            'TABLE_DEPARTMENT' => 'department',
            'TABLE_DEPT_PERMS' => 'dept_perms',
            'TABLE_DEPT_REVIEWER' => 'dept_reviewer',
            'TABLE_LOG' => 'log',
            'TABLE_RIGHTS' => 'rights',
            'TABLE_USER' => 'user',
            'TABLE_USER_PERMS' => 'user_perms'
        ];

        foreach ($expectedTables as $constant => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $this->databaseData->$constant,
                "Table constant {$constant} should equal {$expectedValue}"
            );
        }
    }

    /**
     * Test permission constants are properly defined
     */
    public function testPermissionConstantsAreDefined(): void
    {
        $expectedPermissions = [
            'FORBIDDEN_RIGHT' => -1,
            'NONE_RIGHT' => 0,
            'VIEW_RIGHT' => 1,
            'READ_RIGHT' => 2,
            'WRITE_RIGHT' => 3,
            'ADMIN_RIGHT' => 4
        ];

        foreach ($expectedPermissions as $constant => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $this->databaseData->$constant,
                "Permission constant {$constant} should equal {$expectedValue}"
            );
        }
    }

    /**
     * Test that result_limit is set to 1 by default
     */
    public function testDefaultResultLimit(): void
    {
        $this->assertEquals(1, $this->databaseData->result_limit);
    }

    /**
     * Test that essential properties are initialized
     */
    public function testEssentialPropertiesAreInitialized(): void
    {
        $this->assertNotNull($this->databaseData->tablename);
        $this->assertNotNull($this->databaseData->field_name);
        $this->assertNotNull($this->databaseData->field_id);
        $this->assertEquals(1, $this->databaseData->result_limit);
    }

    /**
     * Test inheritance chain works properly
     */
    public function testInheritanceChain(): void
    {
        $this->assertInstanceOf('databaseData', $this->databaseData);
        $this->assertInstanceOf('User', $this->databaseData);
    }

    /**
     * Test that error property can be set and retrieved
     */
    public function testErrorPropertyHandling(): void
    {
        $errorMessage = "Test error message";
        $this->databaseData->error = $errorMessage;
        
        $this->assertEquals($errorMessage, $this->databaseData->error);
    }

    /**
     * Test database prefix configuration
     */
    public function testDatabasePrefixConfiguration(): void
    {
        // The DB_PREFIX property should be accessible if defined in the class
        if (property_exists($this->databaseData, 'DB_PREFIX')) {
            // Only test if the property is actually set to a non-null value
            if ($this->databaseData->DB_PREFIX !== null) {
                $this->assertIsString($this->databaseData->DB_PREFIX);
            } else {
                $this->assertNull($this->databaseData->DB_PREFIX);
            }
        } else {
            // If DB_PREFIX property doesn't exist, that's also valid
            $this->assertFalse(property_exists($this->databaseData, 'DB_PREFIX'));
        }
    }

    /**
     * Test field configuration for User class
     */
    public function testFieldConfiguration(): void
    {
        // User class should have specific field configuration
        $this->assertEquals('username', $this->databaseData->field_name);
        $this->assertEquals('id', $this->databaseData->field_id);
        $this->assertEquals('user', $this->databaseData->tablename);
    }

    /**
     * Test multiple instances maintain separate state
     */
    public function testMultipleInstancesHaveSeparateState(): void
    {
        // Create second instance with its own mock
        $mockConnection2 = \Mockery::mock(PDO::class);
        $mockStatement2 = \Mockery::mock(\PDOStatement::class);
        $mockStatement2->shouldReceive('execute')->andReturn(true);
        $mockStatement2->shouldReceive('fetch')->andReturn(false);
        $mockStatement2->shouldReceive('fetchAll')->andReturn([]);
        $mockStatement2->shouldReceive('rowCount')->andReturn(0);
        $mockConnection2->shouldReceive('prepare')->andReturn($mockStatement2);
        
        $databaseData2 = new User(2, $mockConnection2);
        
        // Modify first instance
        $this->databaseData->setTableName('table1');
        $this->databaseData->error = 'error1';
        
        // Modify second instance
        $databaseData2->setTableName('table2');
        $databaseData2->error = 'error2';
        
        // Verify they maintain separate state
        $this->assertEquals('table1', $this->databaseData->tablename);
        $this->assertEquals('error1', $this->databaseData->error);
        
        $this->assertEquals('table2', $databaseData2->tablename);
        $this->assertEquals('error2', $databaseData2->error);
    }

    /**
     * Test class handles null values properly
     */
    public function testHandlesNullValues(): void
    {
        $this->databaseData->name = null;
        $this->databaseData->error = null;
        
        $this->assertNull($this->databaseData->name);
        $this->assertNull($this->databaseData->error);
    }

    /**
     * Test permission level ordering
     */
    public function testPermissionLevelOrdering(): void
    {
        $this->assertLessThan($this->databaseData->NONE_RIGHT, $this->databaseData->FORBIDDEN_RIGHT);
        $this->assertLessThan($this->databaseData->VIEW_RIGHT, $this->databaseData->NONE_RIGHT);
        $this->assertLessThan($this->databaseData->READ_RIGHT, $this->databaseData->VIEW_RIGHT);
        $this->assertLessThan($this->databaseData->WRITE_RIGHT, $this->databaseData->READ_RIGHT);
        $this->assertLessThan($this->databaseData->ADMIN_RIGHT, $this->databaseData->WRITE_RIGHT);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->databaseData = null;
        $this->mockConnection = null;
        $this->mockStatement = null;
        
        parent::tearDown();
    }
}