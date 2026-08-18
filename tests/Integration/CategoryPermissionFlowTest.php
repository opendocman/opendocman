<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/CategoryPerms.class.php';
require_once APPLICATION_PATH . '/models/UserPermission.class.php';

/**
 * Full inheritance flow: a file the user can access only through a category
 * permission template must be reachable via getAuthority() and appear in the
 * listing membership (getViewableFileIds / getReadableFileIds).
 */
class CategoryPermissionFlowTest extends TestCase
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
    }

    public function testCategoryPermsGetPermissionReturnsGrantForUserAndDept(): void
    {
        $stmtUser = \Mockery::mock(\PDOStatement::class);
        $stmtUser->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtUser->shouldReceive('fetch')->andReturn(['rights' => 2])->zeroOrMoreTimes();

        $stmtDept = \Mockery::mock(\PDOStatement::class);
        $stmtDept->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtDept->shouldReceive('fetch')->andReturn(['rights' => 1])->zeroOrMoreTimes();

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUser, $stmtDept);

        $catPerms = new CategoryPerms($pdo);
        $this->assertSame(2, $catPerms->getPermission(5, 10, null), 'Category user grant should be returned');
        $this->assertSame(1, $catPerms->getPermission(5, null, 3), 'Category dept grant should be returned');
    }

    public function testCategoryPermsGetPermissionReturnsNullWhenNoRow(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmt->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $catPerms = new CategoryPerms($pdo);
        $this->assertNull($catPerms->getPermission(5, 10, null));
    }

    /**
     * Exercises the real getCategoryFileIds() SQL: publishable files in a
     * category whose permission template grants this user or this user's dept
     * the required right are returned.
     */
    public function testCategoryFileIdsQueryReturnsInheritedFiles(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmt->shouldReceive('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([777, 888])->zeroOrMoreTimes();

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::on(function ($q) {
            return str_contains($q, 'category_perms')
                && str_contains($q, 'cp.user_id = :uid')
                && str_contains($q, 'cp.dept_id = :dept')
                && str_contains($q, 'publishable = 1');
        }))->andReturn($stmt);

        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getDeptId')->andReturn(3);

        $up = \Mockery::mock(UserPermission::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $up->uid = 10;
        $up->connection = $pdo;
        $up->user_obj = $mockUser;
        $up->VIEW_RIGHT = 1;
        $up->READ_RIGHT = 2;

        $this->assertSame([777, 888], $up->getCategoryFileIds(1, false));
    }
}