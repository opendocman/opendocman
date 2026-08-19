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

    /**
     * Build a partial UserPermission mock for getAuthority() with the given
     * per-channel permissions for $dataId.
     *
     * @param int   $uid
     * @param int   $deptId
     * @param int   $dataId
     * @param mixed $userPerm   value returned by User_Perms::getPermission
     *                          (-999 = no row; the real sentinel)
     * @param mixed $deptPerm   value returned by Dept_Perms::getPermission
     *                          (0 = no row, the real behavior)
     * @param array $catPerms   map of ['user' => int|null, 'dept' => int|null]
     * @return UserPermission
     */
    private function buildPermissionMock($uid, $deptId, $dataId, $userPerm, $deptPerm, array $catPerms)
    {
        $pdo = \Mockery::mock(PDO::class);

        // FileData constructor: findName + loadData
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => $dataId])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => $dataId])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 5, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtGeneric = \Mockery::mock(\PDOStatement::class);
        $stmtGeneric->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGeneric->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtGeneric)->zeroOrMoreTimes();

        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn($uid);
        $mockUser->shouldReceive('getDeptId')->andReturn($deptId);
        $mockUser->shouldReceive('isAdmin')->andReturn(false);
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false);

        $mockUP = \Mockery::mock(User_Perms::class)->makePartial();
        $mockUP->FORBIDDEN_RIGHT = -1;
        $mockUP->NONE_RIGHT = 0;
        $mockUP->VIEW_RIGHT = 1;
        $mockUP->READ_RIGHT = 2;
        $mockUP->WRITE_RIGHT = 3;
        $mockUP->ADMIN_RIGHT = 4;
        $mockUP->shouldReceive('getPermission')->with($dataId)->andReturn($userPerm);

        $mockDP = \Mockery::mock(Dept_Perms::class)->makePartial();
        $mockDP->shouldReceive('getPermission')->with($dataId)->andReturn($deptPerm);

        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->with(5, $uid, null)->andReturn($catPerms['user'] ?? null);
        $mockCP->shouldReceive('getPermission')->with(5, null, $deptId)->andReturn($catPerms['dept'] ?? null);

        $up = \Mockery::mock(UserPermission::class)->makePartial();
        $up->uid = $uid;
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

        return $up;
    }

    public function testGetAuthorityFallsBackToCategoryDeptWhenNoDocPerms(): void
    {
        // Real behavior: Dept_Perms::getPermission() returns 0 when there is
        // no row, and User_Perms::getPermission() returns -999 when there is
        // no row. The category fallback must still be reached.
        $up = $this->buildPermissionMock(10, 3, 42, -999, 0, ['user' => null, 'dept' => 1]);

        $this->assertSame(1, $up->getAuthority(42), 'Should fall back to category dept perms when no doc-level perms');
    }

    public function testGetAuthorityFallsBackToCategoryForLegacyDeptZeroRow(): void
    {
        // Legacy pre-#416 files store a dept_perms row with rights=0 for every
        // department. That "Unset" row must NOT block category inheritance.
        $up = $this->buildPermissionMock(10, 3, 42, -999, 0, ['user' => null, 'dept' => 2]);

        $this->assertSame(2, $up->getAuthority(42), 'Legacy dept rights=0 should fall through to category');
    }

    public function testGetAuthorityFallsBackToCategoryUserBeforeDept(): void
    {
        $up = $this->buildPermissionMock(10, 3, 42, -999, 0, ['user' => 3, 'dept' => 1]);

        $this->assertSame(3, $up->getAuthority(42), 'Category user grant should win over category dept grant');
    }

    public function testGetAuthorityReturnsZeroWhenNoGrantAnywhere(): void
    {
        $up = $this->buildPermissionMock(10, 3, 42, -999, 0, ['user' => null, 'dept' => null]);

        $this->assertSame(0, $up->getAuthority(42));
    }

    public function testGetAuthorityUserUnsetBlocksCategoryFallback(): void
    {
        // A doc-level user_perms row with rights=0 ("Unset") is an explicit
        // "no access" and must block category inheritance.
        $up = $this->buildPermissionMock(10, 3, 42, 0, 0, ['user' => 2, 'dept' => 2]);

        $this->assertSame(0, $up->getAuthority(42), 'User "Unset" row should block category fallback');
    }

    public function testGetAuthorityUserForbiddenBlocksCategoryFallback(): void
    {
        $up = $this->buildPermissionMock(10, 3, 42, -1, 0, ['user' => 2, 'dept' => 2]);

        $this->assertSame(-1, $up->getAuthority(42), 'User Forbidden row should block category fallback');
    }

    public function testGetAuthorityUserViewWinsOverHigherCategoryGrant(): void
    {
        // user_perms rights=1 (View) returns 1 — the category grant (Read=2)
        // must not elevate the user beyond the explicit doc-level grant.
        $up = $this->buildPermissionMock(10, 3, 42, 1, 0, ['user' => 2, 'dept' => 2]);

        $this->assertSame(1, $up->getAuthority(42), 'User doc-level grant should win over category');
    }

    public function testGetAuthorityDeptGrantWinsBeforeCategory(): void
    {
        $up = $this->buildPermissionMock(10, 3, 42, -999, 3, ['user' => 1, 'dept' => 1]);

        $this->assertSame(3, $up->getAuthority(42), 'Positive dept grant should win over category');
    }
}
