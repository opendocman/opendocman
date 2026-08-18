<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/User_Perms.class.php';
require_once APPLICATION_PATH . '/models/Dept_Perms.class.php';

class UserPermsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['max_query'] = 100;
        $GLOBALS['CONFIG']['root_id'] = 1;
    }

    private function makeUserMock(array $methods): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(User::class);

        // Reasonable defaults
        $mock->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $mock->shouldReceive('isReviewer')->andReturn(false)->byDefault();
        $mock->shouldReceive('getId')->andReturn(2)->byDefault();      // non-root by default
        $mock->shouldReceive('getDeptId')->andReturn(5)->byDefault();

        // Apply overrides
        foreach ($methods as $method => $return) {
            $mock->shouldReceive($method)->andReturn($return)->byDefault();
        }

        return $mock;
    }

    public function testLoadDataUserPermAdminReturnsAllPublishedIds(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // User is admin
        $user = $this->makeUserMock([
            'isAdmin' => true,
            'getDeptId' => 7,
        ]);

        // Admin branch query (SELECT d.id FROM data WHERE publishable = 1 LIMIT ...)
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([[10], [20], [30]]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(3);

        $model = new User_Perms(2, $pdo, $user);
        $result = $model->loadData_UserPerm($model->VIEW_RIGHT, true);

        $this->assertSame([10, 20, 30], $result);
    }

    public function testLoadDataUserPermReviewerReturnsIds(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $reviewerStmt = \Mockery::mock(PDOStatement::class);
        $userPermStmt = \Mockery::mock(PDOStatement::class);

        // User is reviewer (but not admin)
        $user = $this->makeUserMock([
            'isAdmin' => false,
            'isReviewer' => true,
            'getId' => 9,
            'getDeptId' => 4,
        ]);

        // Reviewer branch query (files in departments they review)
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($reviewerStmt);
        $reviewerStmt->shouldReceive('execute')->once()->with([':id' => 9])->andReturn(true);
        $reviewerStmt->shouldReceive('fetchAll')->once()->andReturn([[5], [6]]);

        // User-perm branch query (no direct grants in this case)
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($userPermStmt);
        $userPermStmt->shouldReceive('execute')->once()->with([
            ':right' => 1,
            ':id' => 9
        ])->andReturn(true);
        $userPermStmt->shouldReceive('fetchAll')->once()->andReturn([]);

        $model = new User_Perms(9, $pdo, $user);
        $result = $model->loadData_UserPerm($model->VIEW_RIGHT, true);

        $this->assertSame([5, 6], $result);
    }

    public function testLoadDataUserPermReviewerIncludesDirectUserPermGrants(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $reviewerStmt = \Mockery::mock(PDOStatement::class);
        $userPermStmt = \Mockery::mock(PDOStatement::class);

        // User is a reviewer for a department (but not an admin)
        $user = $this->makeUserMock([
            'isAdmin' => false,
            'isReviewer' => true,
            'getId' => 9,
            'getDeptId' => 4,
        ]);

        // First query: reviewer branch -> files in departments they review
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($reviewerStmt);
        $reviewerStmt->shouldReceive('execute')->once()->with([':id' => 9])->andReturn(true);
        $reviewerStmt->shouldReceive('fetchAll')->once()->andReturn([[5], [6]]);

        // Second query: normal user_perms branch -> files with a direct per-file grant
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($userPermStmt);
        $userPermStmt->shouldReceive('execute')->once()->with([
            ':right' => 2,
            ':id' => 9
        ])->andReturn(true);
        $userPermStmt->shouldReceive('fetchAll')->once()->andReturn([[7]]);

        $model = new User_Perms(9, $pdo, $user);
        $result = $model->loadData_UserPerm($model->READ_RIGHT, true);

        // A reviewer must see files they are directly granted, in addition to
        // files in the departments they review (regression: ODM issue #321).
        $this->assertContains(7, $result);
        $this->assertContains(5, $result);
        $this->assertContains(6, $result);
    }

    public function testLoadDataUserPermRegularUserReturnsIdsWithRight(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // Regular user (not admin, not reviewer)
        $user = $this->makeUserMock([
            'isAdmin' => false,
            'isReviewer' => false,
            'getId' => 12,
            'getDeptId' => 3,
        ]);

        // User branch query selects up.fid with rights >= :right and publishable=1
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':right' => 2,     // e.g., READ_RIGHT
            ':id' => 12
        ])->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([[7], [8]]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(2);

        $model = new User_Perms(12, $pdo, $user);
        $result = $model->loadData_UserPerm($model->READ_RIGHT, true);

        $this->assertSame([7, 8], $result);
    }

    public function testGetPermissionReturns4ForRootUser(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Root user: CONFIG['root_id'] == getId()
        $GLOBALS['CONFIG']['root_id'] = 42;
        $user = $this->makeUserMock([
            'getId' => 42,
            'getDeptId' => 1,
        ]);

        $model = new User_Perms(42, $pdo, $user);
        $this->assertSame(4, $model->getPermission(1001));
    }

    public function testGetPermissionReturnsDbValueWhenRowExists(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $GLOBALS['CONFIG']['root_id'] = 1; // ensure user is not root
        $user = $this->makeUserMock([
            'getId' => 10,
            'getDeptId' => 2,
        ]);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 555,
            ':id' => 10
        ])->andReturn(true);
        $stmt->shouldReceive('fetchColumn')->once()->andReturn(2);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $model = new User_Perms(10, $pdo, $user);
        $this->assertSame(2, $model->getPermission(555));
    }

    public function testGetPermissionReturnsMinus999WhenNoRow(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $GLOBALS['CONFIG']['root_id'] = 1; // ensure user is not root
        $user = $this->makeUserMock([
            'getId' => 15,
            'getDeptId' => 3,
        ]);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 777,
            ':id' => 15
        ])->andReturn(true);
        $stmt->shouldReceive('fetchColumn')->once()->andReturn(null);
        $stmt->shouldReceive('rowCount')->twice()->andReturn(0);

        $model = new User_Perms(15, $pdo, $user);
        $this->assertSame(-999, $model->getPermission(777));
    }
}
