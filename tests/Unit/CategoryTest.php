<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Category class
 * 
 * This test class demonstrates unit testing patterns for the Category model,
 * including testing static methods and database interaction mocking.
 */
class CategoryTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
            'db_prefix' => 'odm_',
            'database_prefix' => 'odm_',
            'root_id' => 1
        ];
        
        // Create mock database connection and statement
        $this->mockConnection = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Set up default mock behaviors to prevent errors
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        $this->mockConnection->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
    }

    /**
     * Test that Category class exists and can be referenced
     */
    public function testCategoryClassExists(): void
    {
        $this->assertTrue(class_exists('Category'));
    }

    /**
     * Test that documents the previous bug that was fixed
     * This test verifies that the bug no longer exists after the fix
     */
    public function testBugFixPreventsUndefinedVariableIssue(): void
    {
        // This test documents that we fixed the undefined variable bug
        // Previously: When no categories existed, $categoryListArray was undefined
        // Now: $categoryListArray is properly initialized as an empty array
        
        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        // With the bug fix, this should return an empty array (not null/undefined)
        $result = Category::getAllCategories($this->mockConnection);
        
        // Verify the fix: should return empty array, not null
        $this->assertIsArray($result, 'Method should return an array after bug fix');
        $this->assertEmpty($result, 'Method should return empty array when no categories exist');
        $this->assertNotNull($result, 'Method should not return null after bug fix');
    }

    /**
     * Test getAllCategories method returns proper array structure with categories
     */
    public function testGetAllCategoriesReturnsProperArrayStructure(): void
    {
        $mockCategoryData = [
            ['id' => 1, 'name' => 'Documents'],
            ['id' => 2, 'name' => 'Images'],
            ['id' => 3, 'name' => 'Presentations']
        ];

        // Set up mock to return test data
        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Check structure of returned data
        foreach ($result as $category) {
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertIsInt($category['id']);
            $this->assertIsString($category['name']);
        }
    }

    /**
     * Test getAllCategories method with single category
     */
    public function testGetAllCategoriesWithSingleCategory(): void
    {
        $mockCategoryData = [
            ['id' => 1, 'name' => 'Single Category']
        ];

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Single Category', $result[0]['name']);
    }

    /**
     * Test getAllCategories uses correct database prefix (with data to avoid bug)
     */
    public function testGetAllCategoriesUsesCorrectDatabasePrefix(): void
    {
        // Change the database prefix to test it's being used
        $GLOBALS['CONFIG']['db_prefix'] = 'test_prefix_';
        
        $mockCategoryData = [
            ['id' => 1, 'name' => 'Test Category']
        ];
        
        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        $expectedQuery = "SELECT id, name FROM test_prefix_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        $this->assertNotEmpty($result);
    }

    /**
     * Test getAllCategories method returns data in alphabetical order by checking query
     */
    public function testGetAllCategoriesOrdersByName(): void
    {
        $mockCategoryData = [
            ['id' => 1, 'name' => 'Test Category']
        ];
        
        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        // Verify the query includes ORDER BY name
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($query) {
                return strpos($query, 'ORDER BY name') !== false;
            }))
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        $this->assertNotEmpty($result);
    }

    /**
     * Test getAllCategories method handles special characters in category names
     */
    public function testGetAllCategoriesHandlesSpecialCharacters(): void
    {
        $mockCategoryData = [
            ['id' => 1, 'name' => 'Category with "quotes"'],
            ['id' => 2, 'name' => "Category with 'apostrophes'"],
            ['id' => 3, 'name' => 'Category with & symbols']
        ];

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        
        $this->assertCount(3, $result);
        $this->assertEquals('Category with "quotes"', $result[0]['name']);
        $this->assertEquals("Category with 'apostrophes'", $result[1]['name']);
        $this->assertEquals('Category with & symbols', $result[2]['name']);
    }

    /**
     * Test getAllCategories method with large dataset
     */
    public function testGetAllCategoriesWithLargeDataset(): void
    {
        // Create a large mock dataset
        $mockCategoryData = [];
        for ($i = 1; $i <= 100; $i++) {
            $mockCategoryData[] = [
                'id' => $i,
                'name' => "Category {$i}"
            ];
        }

        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockCategoryData);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        
        $this->assertCount(100, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(100, $result[99]['id']);
    }

    /**
     * Test that verifies the bug fix works correctly
     * This test verifies that Category::getAllCategories returns an empty array when no categories exist
     */
    public function testGetAllCategoriesReturnsEmptyArrayWhenNoCategoriesExist(): void
    {
        // This test verifies the bug fix: the method should return an empty array
        // when no categories exist (not an undefined variable)
        
        $this->mockStatement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);
            
        $this->mockStatement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);

        $expectedQuery = "SELECT id, name FROM odm_category ORDER BY name";
        $this->mockConnection
            ->shouldReceive('prepare')
            ->once()
            ->with($expectedQuery)
            ->andReturn($this->mockStatement);

        $result = Category::getAllCategories($this->mockConnection);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertCount(0, $result);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->mockConnection = null;
        $this->mockStatement = null;
        
        // Reset global config
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        
        parent::tearDown();
    }
}