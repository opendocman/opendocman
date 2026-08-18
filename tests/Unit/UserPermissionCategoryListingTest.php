<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/UserPermission.class.php';

/**
 * Verifies that category-inherited file IDs are included in the listing
 * membership helpers (getViewableFileIds / getReadableFileIds).
 */
class UserPermissionCategoryListingTest extends TestCase
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

    private function buildPartialMock(array $listOverrides = [], array $categoryIds = [])
    {
        $pdo = \Mockery::mock(PDO::class);

        $stmtFilter = \Mockery::mock(\PDOStatement::class);
        $stmtFilter->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtFilter->shouldReceive('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn($listOverrides['blocked'] ?? [])->zeroOrMoreTimes();
        $pdo->shouldReceive('prepare')->andReturn($stmtFilter)->byDefault();

        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $mockUser->shouldReceive('getPublishedData')->andReturn($listOverrides['published'] ?? [100, 200])->byDefault();

        $mockUP = \Mockery::mock(User_Perms::class)->makePartial();
        $mockUP->shouldReceive('getCurrentViewOnly')->andReturn($listOverrides['userView'] ?? [10, 11])->byDefault();
        $mockUP->shouldReceive('getCurrentReadRight')->andReturn($listOverrides['userRead'] ?? [300])->byDefault();

        $mockDP = \Mockery::mock(Dept_Perms::class)->makePartial();
        $mockDP->shouldReceive('getCurrentViewOnly')->andReturn($listOverrides['deptView'] ?? [12, 13])->byDefault();
        $mockDP->shouldReceive('getCurrentReadRight')->andReturn($listOverrides['deptRead'] ?? [201])->byDefault();

        $up = \Mockery::mock(UserPermission::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $up->shouldReceive('getCategoryFileIds')->andReturn($categoryIds)->byDefault();
        $up->uid = 10;
        $up->connection = $pdo;
        $up->user_obj = $mockUser;
        $up->user_perms_obj = $mockUP;
        $up->dept_perms_obj = $mockDP;
        $up->VIEW_RIGHT = 1;
        $up->READ_RIGHT = 2;

        return $up;
    }

    public function testGetViewableFileIdsIncludesCategoryFiles(): void
    {
        $up = $this->buildPartialMock([], [900, 901]);

        // user [10,11] + dept [12,13] (blocked [] removed) + category [900,901]
        $this->assertSame([10, 11, 12, 13, 900, 901], $up->getViewableFileIds(false));
    }

    public function testGetViewableFileIdsExcludesUserBlockedCategoryFiles(): void
    {
        // File 900 has a doc-level user_perms row with rights < VIEW, so it is
        // excluded from the dept list AND from the category-inherited set.
        $up = $this->buildPartialMock(['blocked' => [900]], [900, 901]);

        $this->assertSame([10, 11, 12, 13, 901], $up->getViewableFileIds(false));
    }

    public function testGetReadableFileIdsIncludesCategoryFiles(): void
    {
        $up = $this->buildPartialMock([], [900]);

        // published [100] + user read [300] + dept read [201] + category [900]
        $this->assertSame([100, 200, 300, 201, 900], $up->getReadableFileIds(false));
    }

    public function testGetViewableFileIdsPassesViewRightAndLimitToHelper(): void
    {
        $up = $this->buildPartialMock([], []);
        $up->shouldReceive('getCategoryFileIds')->with(1, false)->andReturn([])->once();

        $up->getViewableFileIds(false);
    }

    public function testGetReadableFileIdsPassesReadRightAndLimitToHelper(): void
    {
        $up = $this->buildPartialMock([], []);
        $up->shouldReceive('getCategoryFileIds')->with(2, false)->andReturn([])->once();

        $up->getReadableFileIds(false);
    }
}
