<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/UserPermission.class.php';

class UserPermissionInheritanceTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
    }

    public function testGetAuthorityFallsBackToCategoryWhenNoDocPerms(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtGeneric = \Mockery::mock(\PDOStatement::class);
        $stmtGeneric->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn(10);
        $mockUser->shouldReceive('getDeptId')->andReturn(3);
        $mockUser->shouldReceive('isAdmin')->andReturn(false);
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false);

        // No doc-level perms — both return -999 (no row)
        $mockUP = \Mockery::mock(User_Perms::class)->makePartial();
        $mockUP->FORBIDDEN_RIGHT = -1; $mockUP->NONE_RIGHT = 0;
        $mockUP->VIEW_RIGHT = 1; $mockUP->READ_RIGHT = 2;
        $mockUP->WRITE_RIGHT = 3; $mockUP->ADMIN_RIGHT = 4;
        $mockUP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        $mockDP = \Mockery::mock(Dept_Perms::class)->makePartial();
        $mockDP->shouldReceive('getPermission')->with(42)->andReturn(-999);

        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->with(5, 10, null)->andReturn(null);
        $mockCP->shouldReceive('getPermission')->with(5, null, 3)->andReturn(1);

        // Create a partial mock of UserPermission to bypass constructor
        $up = \Mockery::mock(UserPermission::class)->makePartial();
        $up->uid = 10;
        $up->connection = $pdo;
        $up->user_obj = $mockUser;
        $up->user_perms_obj = $mockUP;
        $up->dept_perms_obj = $mockDP;
        $up->category_perms_obj = $mockCP;
        $up->FORBIDDEN_RIGHT = -1;
        $up->NONE_RIGHT = 0;
        $up->VIEW_RIGHT = 1;
        $up->READ_RIGHT = 2;
        $up->WRITE_RIGHT = 3;
        $up->ADMIN_RIGHT = 4;

        // Mock FileData constructor to return file with category=5
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 5, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtGeneric, $stmtGeneric)->zeroOrMoreTimes();

        $this->assertSame(1, $up->getAuthority(42), 'Should fall back to category dept perms when no doc-level perms');
    }
}