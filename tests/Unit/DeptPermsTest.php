<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/Dept_Perms.class.php';



class DeptPermsTest extends TestCase
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

    public function testGetCurrentViewOnlyReturnsFileIds(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':id' => 5,
            ':right' => 1, // VIEW_RIGHT
        ])->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->with(PDO::FETCH_COLUMN)->andReturn([10, 20, 30]);

        $model = new Dept_Perms(5, $pdo);
        $result = $model->getCurrentViewOnly(true);

        $this->assertSame([10, 20, 30], $result);
    }

    public function testIsForbiddenReturnsTrueWhenRightsEqualForbidden(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 123,
            ':id' => 7,
        ])->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(['rights' => -1]); // FORBIDDEN_RIGHT
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $model = new Dept_Perms(7, $pdo);
        $this->assertTrue($model->isForbidden(123));
    }

    public function testIsForbiddenReturnsFalseWhenRightsAreNotForbidden(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 124,
            ':id' => 7,
        ])->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(['rights' => 0]); // not forbidden
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $model = new Dept_Perms(7, $pdo);
        $this->assertFalse($model->isForbidden(124));
    }

    public function testIsForbiddenReturnsZeroWhenNoRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 125,
            ':id' => 7,
        ])->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        $stmt->shouldReceive('rowCount')->once()->andReturn(0);

        $model = new Dept_Perms(7, $pdo);
        $this->assertSame(0, $model->isForbidden(125));
    }

    public function testCanDeptReturnsTrueWhenRowExists(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 500,
            ':right' => 2, // READ_RIGHT
            ':id' => 4,
        ])->andReturn(true);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $model = new Dept_Perms(4, $pdo);
        $this->assertTrue($model->canDept(500, $model->READ_RIGHT));
    }

    public function testCanDeptReturnsFalseWhenNoRow(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 501,
            ':right' => 3, // WRITE_RIGHT
            ':id' => 4,
        ])->andReturn(true);
        $stmt->shouldReceive('rowCount')->once()->andReturn(0);

        $model = new Dept_Perms(4, $pdo);
        $this->assertFalse($model->canDept(501, $model->WRITE_RIGHT));
    }

    public function testGetPermissionReturnsRightsWhenExactlyOneRow(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 600,
            ':id' => 9,
        ])->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(['rights' => 4]);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $model = new Dept_Perms(9, $pdo);
        $this->assertSame(4, $model->getPermission(600));
    }

    public function testGetPermissionReturnsZeroWhenNoRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->with([
            ':data_id' => 601,
            ':id' => 9,
        ])->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        $stmt->shouldReceive('rowCount')->once()->andReturn(0);

        $model = new Dept_Perms(9, $pdo);
        $this->assertSame(0, $model->getPermission(601));
    }

    public function testCanViewReturnsTrueWhenNotForbiddenAndPublishableAndHasRight(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // FileData constructor: findName()
        $stmtFindName = \Mockery::mock(PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 777])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData::loadData()
        $stmtLoad = \Mockery::mock(PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 777])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Dept_Perms::isForbidden()
        $stmtForbidden = \Mockery::mock(PDOStatement::class);
        $stmtForbidden->shouldReceive('execute')->once()->with([
            ':data_id' => 777,
            ':id' => 3,
        ])->andReturn(true);
        $stmtForbidden->shouldReceive('fetch')->once()->andReturn(['rights' => 0]);
        $stmtForbidden->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData::isPublishable()
        $stmtIsPub = \Mockery::mock(PDOStatement::class);
        $stmtIsPub->shouldReceive('execute')->once()->with([':id' => 777])->andReturn(true);
        $stmtIsPub->shouldReceive('fetchColumn')->once()->andReturn(1);
        $stmtIsPub->shouldReceive('rowCount')->once()->andReturn(1);

        // Dept_Perms::canDept()
        $stmtCanDept = \Mockery::mock(PDOStatement::class);
        $stmtCanDept->shouldReceive('execute')->once()->with([
            ':data_id' => 777,
            ':right' => 1, // VIEW_RIGHT
            ':id' => 3,
        ])->andReturn(true);
        $stmtCanDept->shouldReceive('rowCount')->once()->andReturn(1);

        // Chain all prepares in the expected order
        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtForbidden, $stmtIsPub, $stmtCanDept);

        $model = new Dept_Perms(3, $pdo);
        $this->assertTrue($model->canView(777));
    }

    public function testCanViewReturnsFalseWhenPublishableFalse(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // FileData constructor: findName()
        $stmtFindName = \Mockery::mock(PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 778])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData::loadData()
        $stmtLoad = \Mockery::mock(PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 778])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Dept_Perms::isForbidden()
        $stmtForbidden = \Mockery::mock(PDOStatement::class);
        $stmtForbidden->shouldReceive('execute')->once()->with([
            ':data_id' => 778,
            ':id' => 3,
        ])->andReturn(true);
        $stmtForbidden->shouldReceive('fetch')->once()->andReturn(['rights' => 0]);
        $stmtForbidden->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData::isPublishable() returns false
        $stmtIsPub = \Mockery::mock(PDOStatement::class);
        $stmtIsPub->shouldReceive('execute')->once()->with([':id' => 778])->andReturn(true);
        $stmtIsPub->shouldReceive('fetchColumn')->once()->andReturn(0);
        $stmtIsPub->shouldReceive('rowCount')->once()->andReturn(1);

        // Chain prepares (no canDept call expected)
        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtForbidden, $stmtIsPub);

        $model = new Dept_Perms(3, $pdo);
        $this->assertFalse($model->canView(778));
    }

    public function testCanViewReturnsFalseWhenForbidden(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        // FileData constructor: findName()
        $stmtFindName = \Mockery::mock(PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 779])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['dummy.txt']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        // FileData::loadData()
        $stmtLoad = \Mockery::mock(PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 779])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([[
            'category' => 1, 'owner' => 1, 'created' => '2020-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ]]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        // Dept_Perms::isForbidden() returns forbidden
        $stmtForbidden = \Mockery::mock(PDOStatement::class);
        $stmtForbidden->shouldReceive('execute')->once()->with([
            ':data_id' => 779,
            ':id' => 3,
        ])->andReturn(true);
        $stmtForbidden->shouldReceive('fetch')->once()->andReturn(['rights' => -1]);
        $stmtForbidden->shouldReceive('rowCount')->once()->andReturn(1);

        // Chain prepares (no isPublishable or canDept expected after forbidden)
        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtForbidden);

        $model = new Dept_Perms(3, $pdo);
        $this->assertFalse($model->canView(779));
    }
}
