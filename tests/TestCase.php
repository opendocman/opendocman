<?php

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Base TestCase class for OpenDocMan tests
 * 
 * This class provides common functionality and utilities for all test classes.
 * It extends PHPUnit's TestCase and includes Mockery integration for mocking.
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PDO|null Mock database connection
     */
    protected $mockConnection;

    /**
     * Set up method called before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock PDO connection for tests that need database interaction
        $this->mockConnection = $this->createMockPDO();
    }

    /**
     * Tear down method called after each test
     */
    protected function tearDown(): void
    {
        // Clean up any test data or mocks
        $this->mockConnection = null;
        
        parent::tearDown();
    }

    /**
     * Create a mock PDO connection for testing
     * 
     * @return PDO|\Mockery\MockInterface
     */
    protected function createMockPDO()
    {
        return \Mockery::mock(PDO::class);
    }

    /**
     * Create a mock PDOStatement for testing database queries
     * 
     * @param array $returnData Data to return when fetch() is called
     * @return \PDOStatement|\Mockery\MockInterface
     */
    protected function createMockPDOStatement(array $returnData = [])
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        
        if (!empty($returnData)) {
            $stmt->shouldReceive('fetch')
                 ->andReturn($returnData, false); // Return data first, then false to end loop
            
            $stmt->shouldReceive('fetchAll')
                 ->andReturn([$returnData]);
        }
        
        $stmt->shouldReceive('execute')
             ->andReturn(true);
             
        $stmt->shouldReceive('rowCount')
             ->andReturn(count($returnData) > 0 ? 1 : 0);
             
        return $stmt;
    }



    /**
     * Assert that an array contains expected keys
     * 
     * @param array $expectedKeys
     * @param array $actual
     * @param string $message
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $actual, string $message = ''): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $actual, $message . " - Missing key: {$key}");
        }
    }

    /**
     * Assert that a class has expected properties
     * 
     * @param array $expectedProperties
     * @param object $object
     * @param string $message
     */
    protected function assertObjectHasProperties(array $expectedProperties, object $object, string $message = ''): void
    {
        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                property_exists($object, $property),
                $message . " - Missing property: {$property}"
            );
        }
    }

    /**
     * Create test data for database operations
     * 
     * @param string $type Type of test data to create (user, file, category, etc.)
     * @return array
     */
    protected function createTestData(string $type): array
    {
        switch ($type) {
            case 'user':
                return [
                    'id' => 1,
                    'username' => 'testuser',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'phone' => '555-1234',
                    'department' => 1,
                    'pw_reset_code' => null,
                    'can_add' => 1,
                    'can_checkin' => 1
                ];
                
            case 'category':
                return [
                    'id' => 1,
                    'name' => 'Test Category',
                    'description' => 'A test category'
                ];
                
            case 'department':
                return [
                    'id' => 1,
                    'name' => 'Test Department'
                ];
                
            case 'file':
                return [
                    'id' => 1,
                    'real_name' => 'test_document.pdf',
                    'category' => 1,
                    'owner' => 1,
                    'created' => date('Y-m-d H:i:s'),
                    'description' => 'Test document',
                    'comment' => 'Test comment',
                    'status' => 0
                ];
                
            default:
                return [];
        }
    }

    /**
     * Set up global configuration for tests
     * 
     * @param array $config Additional configuration to merge
     */
    protected function setUpGlobalConfig(array $config = []): void
    {
        $defaultConfig = [
            'root_id' => 1,
            'database_host' => 'localhost',
            'database_name' => 'opendocman_test',
            'database_user' => 'test_user',
            'database_pass' => 'test_pass',
            'database_prefix' => 'odm_',
        ];
        
        $GLOBALS['CONFIG'] = array_merge($defaultConfig, $config);
    }
}