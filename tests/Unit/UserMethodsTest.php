<?php

use PHPUnit\Framework\TestCase;

/**
 * Additional Unit tests for User class methods that require specific database mocking
 * 
 * This test class focuses on testing User methods that have complex database interactions
 * and need specific mocking scenarios to achieve better code coverage.
 */
class UserMethodsTest extends TestCase
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
        
        // Set up global configuration
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'database_prefix' => 'odm_',
            'db_prefix' => 'odm_',
            'base_url' => 'http://localhost/opendocman/'
        ];
        
        // Create mock database connection and statement
        $this->mockConnection = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        
        // Default mock behavior
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('fetchColumn')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        
        $this->mockConnection->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        
        // Create User instance
        $this->user = new User(1, $this->mockConnection);
    }

    /**
     * Test getDeptName method with valid department
     */
    public function testGetDeptNameWithValidDepartment(): void
    {
        $expectedDeptName = 'Human Resources';
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn($expectedDeptName);
        
        $result = $this->user->getDeptName();
        $this->assertEquals($expectedDeptName, $result);
    }

    /**
     * Test getDeptName method with no department found
     */
    public function testGetDeptNameWithNoDepartment(): void
    {
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(false);
        
        $result = $this->user->getDeptName();
        $this->assertFalse($result);
    }

    /**
     * Test getPublishedData method with publishable data
     */
    public function testGetPublishedDataWithPublishableData(): void
    {
        $publishable = 1;
        $mockData = [
            ['id' => 1],
            ['id' => 3],
            ['id' => 5]
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':publishable' => $publishable, ':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockData);
        
        $result = $this->user->getPublishedData($publishable);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($mockData, $result);
    }

    /**
     * Test getPublishedData method with non-publishable data
     */
    public function testGetPublishedDataWithNonPublishableData(): void
    {
        $publishable = 0;
        $mockData = [
            ['id' => 2],
            ['id' => 4]
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':publishable' => $publishable, ':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockData);
        
        $result = $this->user->getPublishedData($publishable);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($mockData, $result);
    }

    /**
     * Test isAdmin method with admin user (non-root)
     */
    public function testIsAdminWithAdminNonRootUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(1); // Admin flag set
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->isAdmin();
        $this->assertTrue((bool)$result);
    }

    /**
     * Test isAdmin method with non-admin user
     */
    public function testIsAdminWithNonAdminUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 3; // Non-root, non-admin user
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(0); // Admin flag not set
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0); // No admin record found
        
        $result = $this->user->isAdmin();
        $this->assertFalse($result);
    }

    /**
     * Test validatePassword method with legacy MD5 hash (lazy rehash)
     */
    public function testValidatePasswordWithLegacyMd5Hash(): void
    {
        $password = 'test_password';

        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(md5($password));

        $result = $this->user->validatePassword($password);
        $this->assertTrue($result);
    }

    /**
     * Test validatePassword method with invalid password
     */
    public function testValidatePasswordWithInvalidPassword(): void
    {
        $password = 'wrong_password';

        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(PasswordHasher::hash('something_else'));

        $result = $this->user->validatePassword($password);
        $this->assertFalse($result);
    }

    /**
     * Test changePassword method stores a bcrypt hash
     */
    public function testChangePassword(): void
    {
        $newPassword = 'new_secure_password';

        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function ($params) use ($newPassword) {
                return isset($params[':password_hash'])
                    && PasswordHasher::verify($newPassword, $params[':password_hash']);
            }))
            ->andReturn(true);

        $result = $this->user->changePassword($newPassword);
        $this->assertTrue($result);
    }

    /**
     * Test changeName method
     */
    public function testChangeName(): void
    {
        $newName = 'new_username';
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':new_name' => $newName, ':id' => $this->user->id])
            ->andReturn(true);
        
        $result = $this->user->changeName($newName);
        $this->assertTrue($result);
    }

    /**
     * Test isReviewer method for department reviewer
     */
    public function testIsReviewerForDepartmentReviewer(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 5; // Non-admin user
        
        // Mock isAdmin to return false
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(0);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        // Mock department reviewer check to return true
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        $result = $this->user->isReviewer();
        $this->assertTrue($result);
    }

    /**
     * Test isReviewerForFile method with valid file
     */
    public function testIsReviewerForFileWithValidFile(): void
    {
        $fileId = 123;
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':user_id' => $this->user->id, ':file_id' => $fileId])
            ->andReturn(true);
        
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
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':user_id' => $this->user->id, ':file_id' => $fileId])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->isReviewerForFile($fileId);
        $this->assertFalse($result);
    }

    /**
     * Test getAllRevieweeIds method for admin user
     */
    public function testGetAllRevieweeIdsForAdminUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 1; // Root user (admin)
        
        $mockData = [
            [0 => '10'],
            [0 => '20'],
            [0 => '30']
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockData);
        
        $result = $this->user->getAllRevieweeIds();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['10', '20', '30'], $result);
    }

    /**
     * Test getAllRevieweeIds method for non-admin user returns null
     */
    public function testGetAllRevieweeIdsForNonAdminUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        // Mock isAdmin to return false
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(0);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $result = $this->user->getAllRevieweeIds();
        $this->assertNull($result);
    }

    /**
     * Test getRevieweeIds method with complex department logic
     */
    public function testGetRevieweeIdsWithComplexDepartmentLogic(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 3; // Non-admin reviewer
        
        // Mock isReviewer to return true
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn(0);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);
        
        // Mock department query
        $deptData = [
            ['dept_id' => 1],
            ['dept_id' => 2]
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($deptData);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        // Mock files query
        $fileData = [
            ['id' => 100],
            ['id' => 200]
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':dept' => 2]) // Last department ID
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($fileData);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);
        
        $result = $this->user->getRevieweeIds();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals([100, 200], $result);
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
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        
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
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
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
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
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
     * Test getNumExpiredFiles method
     */
    public function testGetNumExpiredFiles(): void
    {
        $expectedCount = 5;
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $this->user->id])
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('rowCount')
            ->once()
            ->andReturn($expectedCount);
        
        $result = $this->user->getNumExpiredFiles();
        $this->assertEquals($expectedCount, $result);
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
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        
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
     * Test getCheckedOutFiles method for non-root user returns null
     */
    public function testGetCheckedOutFilesForNonRootUser(): void
    {
        $this->user->root_id = 1;
        $this->user->id = 2; // Non-root user
        
        $result = $this->user->getCheckedOutFiles();
        $this->assertNull($result);
    }

    /**
     * Test getAllUsers static method
     */
    public function testGetAllUsersStaticMethod(): void
    {
        $users = [
            ['id' => 1, 'username' => 'admin', 'first_name' => 'Admin', 'last_name' => 'User'],
            ['id' => 2, 'username' => 'jdoe', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 3, 'username' => 'asmith', 'first_name' => 'Alice', 'last_name' => 'Smith']
        ];
        
        $this->mockStatement->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        
        $this->mockStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($users);
        
        $result = User::getAllUsers($this->mockConnection);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($users, $result);
    }

    /**
     * Test edge cases with empty arrays
     */
    public function testEdgeCasesWithEmptyArrays(): void
    {
        // Test methods that return empty arrays
        $this->mockStatement->shouldReceive('execute')->andReturn(true);
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([]);
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0);
        
        $this->assertEmpty($this->user->getAllRejectedFileIds());
        $this->assertEmpty($this->user->getRejectedFileIds());
        $this->assertEmpty($this->user->getExpiredFileIds());
    }

    /**
     * Regression: changePassword() SQL must use the DB prefix in table name.
     * Avoids the bug where $this->tablename ('user') was used without {$GLOBALS['CONFIG']['db_prefix']}.
     */
    public function testChangePasswordQueryContainsDbPrefix(): void
    {
        $newPassword = 'regression_test_pass';
        $lastQuery = '';

        $this->mockConnection->shouldReceive('prepare')
            ->andReturnUsing(function ($sql) use (&$lastQuery) {
                $lastQuery = $sql;
                return $this->mockStatement;
            });

        $this->mockStatement->shouldReceive('execute')
            ->with([':non_encrypted_password' => $newPassword, ':id' => $this->user->id])
            ->andReturn(true);

        $this->user->changePassword($newPassword);

        $this->assertStringContainsString(
            $GLOBALS['CONFIG']['db_prefix'] . 'user',
            $lastQuery,
            'changePassword() SQL must contain the prefixed table name'
        );
    }

    /**
     * Regression: validatePassword() SQL must use the DB prefix in table name.
     */
    public function testValidatePasswordQueryContainsDbPrefix(): void
    {
        $password = 'regression_test_pass';
        $queries = [];

        $this->mockConnection->shouldReceive('prepare')
            ->andReturnUsing(function ($sql) use (&$queries) {
                $queries[] = $sql;
                return $this->mockStatement;
            });

        $this->mockStatement->shouldReceive('execute')
            ->with([':non_encrypted_password' => $password, ':id' => $this->user->id])
            ->andReturn(true);

        $this->mockStatement->shouldReceive('rowCount')
            ->andReturn(1);

        $this->user->validatePassword($password);

        foreach ($queries as $query) {
            if (stripos($query, 'FROM') !== false) {
                $this->assertStringContainsString(
                    $GLOBALS['CONFIG']['db_prefix'] . 'user',
                    $query,
                    'validatePassword() SQL must contain the prefixed table name'
                );
            }
        }
    }

    /**
     * Regression: changeName() SQL must use the DB prefix in table name.
     */
    public function testChangeNameQueryContainsDbPrefix(): void
    {
        $newName = 'regression_test_user';
        $lastQuery = '';

        $this->mockConnection->shouldReceive('prepare')
            ->andReturnUsing(function ($sql) use (&$lastQuery) {
                $lastQuery = $sql;
                return $this->mockStatement;
            });

        $this->mockStatement->shouldReceive('execute')
            ->with([':new_name' => $newName, ':id' => $this->user->id])
            ->andReturn(true);

        $this->user->changeName($newName);

        $this->assertStringContainsString(
            $GLOBALS['CONFIG']['db_prefix'] . 'user',
            $lastQuery,
            'changeName() SQL must contain the prefixed table name'
        );
    }

    /**
     * Test isPasswordChangeRequired returns false by default (constructor data not loaded from DB)
     */
    public function testIsPasswordChangeRequiredDefaultsToFalse(): void
    {
        $this->assertFalse($this->user->isPasswordChangeRequired());
    }

    /**
     * Regression: clearPasswordChangeRequired() SQL must use the DB prefix in table name.
     */
    public function testClearPasswordChangeRequiredQueryContainsDbPrefix(): void
    {
        $lastQuery = '';

        $this->mockConnection->shouldReceive('prepare')
            ->andReturnUsing(function ($sql) use (&$lastQuery) {
                $lastQuery = $sql;
                return $this->mockStatement;
            });

        $this->mockStatement->shouldReceive('execute')
            ->with([':id' => $this->user->id])
            ->andReturn(true);

        $this->user->clearPasswordChangeRequired();

        $this->assertStringContainsString(
            $GLOBALS['CONFIG']['db_prefix'] . 'user',
            $lastQuery,
            'clearPasswordChangeRequired() SQL must contain the prefixed table name'
        );
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