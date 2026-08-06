<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/UserPermission.class.php';
require_once APPLICATION_PATH . '/models/CategoryPerms.class.php';

class UserPermissionOrchestratorTest extends TestCase
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

    private function baseRights(): array
    {
        return [
            'FORBIDDEN_RIGHT' => -1,
            'NONE_RIGHT' => 0,
            'VIEW_RIGHT' => 1,
            'READ_RIGHT' => 2,
            'WRITE_RIGHT' => 3,
            'ADMIN_RIGHT' => 4,
        ];
    }

    private function setupOverloadedUser(array $overrides = [])
    {
        $userOver = \Mockery::mock(User::class);

        // Reasonable defaults for any User instance created via "new User(...)"
        $userOver->shouldReceive('getId')->andReturn(10)->byDefault();
        $userOver->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $userOver->shouldReceive('getPublishedData')->andReturn([100, 200])->byDefault();
        $userOver->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $userOver->shouldReceive('isReviewer')->andReturn(false)->byDefault();
        $userOver->shouldReceive('isReviewerForFile')->andReturn(false)->byDefault();

        // Apply overrides e.g. ['isAdmin' => true]
        foreach ($overrides as $method => $return) {
            $userOver->shouldReceive($method)->andReturn($return)->byDefault();
        }

        return $userOver;
    }

    private function setupOverloadedUserPerms(array $listOverrides = [])
    {
        $rights = $this->baseRights();

        $upOver = \Mockery::mock(User_Perms::class)->makePartial();

        // Set the rights constants as properties for direct access
        $upOver->FORBIDDEN_RIGHT = $rights['FORBIDDEN_RIGHT'];
        $upOver->NONE_RIGHT = $rights['NONE_RIGHT'];
        $upOver->VIEW_RIGHT = $rights['VIEW_RIGHT'];
        $upOver->READ_RIGHT = $rights['READ_RIGHT'];
        $upOver->WRITE_RIGHT = $rights['WRITE_RIGHT'];
        $upOver->ADMIN_RIGHT = $rights['ADMIN_RIGHT'];

        // Defaults for list-returning methods
        $upOver->shouldReceive('getCurrentViewOnly')->andReturn([10, 11, 12])->byDefault();
        $upOver->shouldReceive('getCurrentReadRight')->andReturn([300])->byDefault();
        $upOver->shouldReceive('getCurrentWriteRight')->andReturn([400])->byDefault();
        $upOver->shouldReceive('getCurrentAdminRight')->andReturn([500])->byDefault();

        // Default for getPermission (overridden in specific tests)
        $upOver->shouldReceive('getPermission')->andReturn($rights['NONE_RIGHT'])->byDefault();

        // Apply list overrides like ['getCurrentReadRight' => [1,2]]
        foreach ($listOverrides as $method => $value) {
            $upOver->shouldReceive($method)->andReturn($value)->byDefault();
        }

        return $upOver;
    }

    private function setupOverloadedDeptPerms(array $listOverrides = [])
    {
        $dpOver = \Mockery::mock(Dept_Perms::class)->makePartial();

        // Defaults for list-returning methods
        $dpOver->shouldReceive('getCurrentViewOnly')->andReturn([12, 13, 14])->byDefault();
        $dpOver->shouldReceive('getCurrentReadRight')->andReturn([201, 300])->byDefault();
        $dpOver->shouldReceive('getCurrentWriteRight')->andReturn([202, 400])->byDefault();
        $dpOver->shouldReceive('getCurrentAdminRight')->andReturn([203, 500])->byDefault();

        // Default for getPermission (overridden in specific tests)
        $dpOver->shouldReceive('getPermission')->andReturn(0)->byDefault();

        // Apply list overrides
        foreach ($listOverrides as $method => $value) {
            $dpOver->shouldReceive($method)->andReturn($value)->byDefault();
        }

        return $dpOver;
    }

    public function testReadableWritableAdminAggregations(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtUserRow = \Mockery::mock(\PDOStatement::class);
        $stmtUserRow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        // id, username, department, phone, email, last_name, first_name, pw_reset_code, can_add, can_checkin
        $stmtUserRow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtUserRow, $stmtGenericStub)->zeroOrMoreTimes();

        // Override user_obj directly to avoid deep DB calls for publishable/admin/reviewer checks
        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn(10)->byDefault();
        $mockUser->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $mockUser->shouldReceive('getPublishedData')->andReturn([100, 200])->byDefault();
        $mockUser->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewer')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false)->byDefault();
        $mockDP = $this->setupOverloadedDeptPerms([
            'getCurrentReadRight' => [201, 300],     // includes overlap with user perms
            'getCurrentWriteRight' => [202, 400],
            'getCurrentAdminRight' => [203, 500],
        ]);
        $mockUP = $this->setupOverloadedUserPerms([
            'getCurrentReadRight' => [300],
            'getCurrentWriteRight' => [400],
            'getCurrentAdminRight' => [500],
        ]);

        $up = new UserPermission(10, $pdo);
        $up->user_obj = $mockUser;
        $up->dept_perms_obj = $mockDP;
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;
        $up->user_perms_obj = $mockUP;

        // READ = published [100,200] + user [300] + dept [201,300] -> unique [100,200,300,201]
        $this->assertSame([100, 200, 300, 201], $up->getReadableFileIds(true));

        // WRITE = published [100,200] + user [400] + dept [202,400] -> unique [100,200,400,202]
        $this->assertSame([100, 200, 400, 202], $up->getWritableFileIds(true));

        // ADMIN = published [100,200] + user [500] + dept [203,500] -> unique [100,200,500,203]
        $this->assertSame([100, 200, 500, 203], $up->getAdminableFileIds(true));
    }

    public function testViewableFileIdsAppliesExclusionsFromLowRightsAndUserOverlap(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtUserRow = \Mockery::mock(\PDOStatement::class);
        $stmtUserRow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUserRow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Statement for the query in getViewableFileIds that filters out low rights
        $stmtViewableFilter = \Mockery::mock(\PDOStatement::class);
        $stmtViewableFilter->shouldReceive('execute')->once()->with([':uid' => 10, ':view_right' => 1])->andReturn(true);
        $stmtViewableFilter->shouldReceive('fetchAll')->once()->with(PDO::FETCH_COLUMN)->andReturn([13]);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtUserRow, $stmtViewableFilter, $stmtGenericStub)->zeroOrMoreTimes();

        // Override user_obj to avoid deeper DB and supply publishable list/admin/reviewer behavior
        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn(10)->byDefault();
        $mockUser->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $mockUser->shouldReceive('getPublishedData')->andReturn([100, 200])->byDefault();
        $mockUser->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewer')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false)->byDefault();
        $mockDP = $this->setupOverloadedDeptPerms([
            'getCurrentViewOnly' => [12, 13, 14], // dept list
        ]);
        $mockUP = $this->setupOverloadedUserPerms([
            'getCurrentViewOnly' => [10, 11, 12], // user list (12 overlaps with dept)
        ]);

        $up = new UserPermission(10, $pdo);
        $up->user_obj = $mockUser;
        $up->dept_perms_obj = $mockDP;
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;
        $up->user_perms_obj = $mockUP;

        // Expected: user [10,11,12] + (dept [12,13,14] - [13] - userOverlap[10,11,12]) => [10,11,12,14]
        $this->assertSame([10, 11, 12, 14], $up->getViewableFileIds(true));
    }

    public function testGetAuthorityReturnsAdminForAdminUser(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtUFind = \Mockery::mock(\PDOStatement::class);
        $stmtUFind->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUFind->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtUFind->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtURow = \Mockery::mock(\PDOStatement::class);
        $stmtURow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtURow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Admin check -> isAdmin() => true
        $stmtAdmin = \Mockery::mock(\PDOStatement::class);
        $stmtAdmin->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtAdmin->shouldReceive('fetchColumn')->once()->andReturn(1);
        $stmtAdmin->shouldReceive('rowCount')->once()->andReturn(1);

        // Prepare PDO for FileData constructor (findName + loadData)
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 999])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);
        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 999])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        // FileData constructor (findName + loadData) occurs before admin check in getAuthority
        $pdo->shouldReceive('prepare')->andReturn($stmtUFind, $stmtURow, $stmtFindName, $stmtLoad, $stmtAdmin, $stmtGenericStub)->zeroOrMoreTimes();

        $this->setupOverloadedUser(['isAdmin' => true]);
        $upOver = $this->setupOverloadedUserPerms();
        $this->setupOverloadedDeptPerms();

        $up = new UserPermission(10, $pdo);
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;

        // From base rights (copied from user perms): ADMIN_RIGHT=4
        $this->assertSame(4, $up->getAuthority(999));
    }

    public function testGetAuthorityReturnsAdminForReviewerOfFile(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtUFind = \Mockery::mock(\PDOStatement::class);
        $stmtUFind->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUFind->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtUFind->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtURow = \Mockery::mock(\PDOStatement::class);
        $stmtURow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtURow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Admin check -> isAdmin() => false
        $stmtAdmin = \Mockery::mock(\PDOStatement::class);
        $stmtAdmin->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtAdmin->shouldReceive('fetchColumn')->once()->andReturn(0);
        $stmtAdmin->shouldReceive('rowCount')->once()->andReturn(1);

        // Prepare PDO for FileData constructor (findName + loadData)
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 12345])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);
        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 12345])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData constructor (findName + loadData) occurs before admin check in getAuthority
        // Also mock isReviewerForFile to return true for this test
        $stmtIsReviewerForFile = \Mockery::mock(\PDOStatement::class);
        $stmtIsReviewerForFile->shouldReceive('execute')->once()->with([':user_id' => 10, ':file_id' => 12345])->andReturn(true);
        $stmtIsReviewerForFile->shouldReceive('rowCount')->once()->andReturn(1);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtUFind, $stmtURow, $stmtFindName, $stmtLoad, $stmtAdmin, $stmtIsReviewerForFile, $stmtGenericStub)->zeroOrMoreTimes();

        $this->setupOverloadedUser([
            'isAdmin' => false,
            'isReviewerForFile' => true,
        ]);
        $upOver = $this->setupOverloadedUserPerms();
        $this->setupOverloadedDeptPerms();

        $up = new UserPermission(10, $pdo);
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;

        $this->assertSame(4, $up->getAuthority(12345));
    }

    public function testGetAuthorityReturnsWriteWhenOwnerAndLocked(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtUFind = \Mockery::mock(\PDOStatement::class);
        $stmtUFind->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUFind->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtUFind->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtURow = \Mockery::mock(\PDOStatement::class);
        $stmtURow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtURow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Prepare PDO for FileData constructor (findName + loadData),
        // with owner matching UID and status -1 to simulate locked.
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);
        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 42])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 10, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => -1,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtUFind, $stmtURow, $stmtFindName, $stmtLoad, $stmtGenericStub)->zeroOrMoreTimes();

        $up = new UserPermission(10, $pdo);
        
        // Override user_obj to avoid deeper DB calls
        $mockUser = \Mockery::mock(User::class);
        $mockUser->shouldReceive('getId')->andReturn(10)->byDefault();
        $mockUser->shouldReceive('getDeptId')->andReturn(3)->byDefault();
        $mockUser->shouldReceive('isAdmin')->andReturn(false)->byDefault();
        $mockUser->shouldReceive('isReviewerForFile')->andReturn(false)->byDefault();
        
        $upOver = $this->setupOverloadedUserPerms();
        $dpOver = $this->setupOverloadedDeptPerms();
        
        // Inject the mocks to bypass internal DB calls
        $up->user_obj = $mockUser;
        $up->user_perms_obj = $upOver;
        $up->dept_perms_obj = $dpOver;
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;

        // WRITE_RIGHT=3 from base rights
        $this->assertSame(3, $up->getAuthority(42));
    }

    public function testGetAuthorityPrefersUserPermissionWithinBounds(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // Mock User constructor DB interactions (findName + user row)
        $stmtUFind = \Mockery::mock(\PDOStatement::class);
        $stmtUFind->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtUFind->shouldReceive('fetchAll')->once()->andReturn([['u10']]);
        $stmtUFind->shouldReceive('rowCount')->once()->andReturn(1);

        $stmtURow = \Mockery::mock(\PDOStatement::class);
        $stmtURow->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtURow->shouldReceive('fetch')->once()->andReturn([10, 'u10', 3, '555', 'u10@example.com', 'Last', 'First', null, 0, 1, 1]);

        // Admin check -> isAdmin() => false
        $stmtAdmin = \Mockery::mock(\PDOStatement::class);
        $stmtAdmin->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtAdmin->shouldReceive('fetchColumn')->once()->andReturn(0);
        $stmtAdmin->shouldReceive('rowCount')->once()->andReturn(1);

        // Prepare PDO for FileData constructor (findName + loadData)
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 777])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);
        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 777])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData constructor (findName + loadData) occurs before admin check in getAuthority
        // Ensure isReviewerForFile() returns false in this path as well
        $stmtIsReviewerForFile777 = \Mockery::mock(\PDOStatement::class);
        $stmtIsReviewerForFile777->shouldReceive('execute')->once()->with([':user_id' => 10, ':file_id' => 777])->andReturn(true);
        $stmtIsReviewerForFile777->shouldReceive('rowCount')->once()->andReturn(0);

        // Add comprehensive PDO mocking for User_Perms and Dept_Perms constructors
        $stmtGenericStub = \Mockery::mock(\PDOStatement::class);
        $stmtGenericStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtGenericStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo->shouldReceive('prepare')->andReturn($stmtUFind, $stmtURow, $stmtFindName, $stmtLoad, $stmtAdmin, $stmtIsReviewerForFile777, $stmtGenericStub)->zeroOrMoreTimes();

        $this->setupOverloadedUser([
            'isAdmin' => false,
            'isReviewerForFile' => false,
        ]);

        // User perms returns READ (2), dept perms would be different but should be ignored
        $upOver = $this->setupOverloadedUserPerms();
        $upOver->shouldReceive('getPermission')->once()->with(777)->andReturn(2);

        $dpOver = $this->setupOverloadedDeptPerms();
        $dpOver->shouldReceive('getPermission')->once()->andReturn(0);

        $up = new UserPermission(10, $pdo);
        // Inject mocks to avoid DB getPermission query
        $up->user_perms_obj = $upOver;
        $up->dept_perms_obj = $dpOver;
        $mockCP = \Mockery::mock(CategoryPerms::class);
        $mockCP->shouldReceive('getPermission')->andReturn(null)->byDefault();
        $up->category_perms_obj = $mockCP;

        $this->assertSame(2, $up->getAuthority(777));
    }
}
