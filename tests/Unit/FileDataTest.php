<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/FileData.class.php';

class FileDataTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['max_query'] = 100;
        $GLOBALS['CONFIG']['site_mail'] = 'admin@example.com';

        // Helpful defaults to avoid notices in code paths
        $_SESSION['uid'] = 99;
    }

    private function createBaseStmtsForConstructor(string $realname, array $dataRow)
    {
        // findName() statement
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([[ $realname ]]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        // loadData() statement
        $stmtLoad = \Mockery::mock(\PDOStatement::class);
        $stmtLoad->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtLoad->shouldReceive('fetchAll')->once()->andReturn([$dataRow]);
        $stmtLoad->shouldReceive('rowCount')->once()->andReturn(1);

        return [$stmtFindName, $stmtLoad];
    }

    public function testConstructorAndBasicGetters(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'file.txt';
        $dataRow = [
            'category' => 2,
            'owner' => 10,
            'created' => '2023-01-01 00:00:00',
            'description' => 'Desc',
            'comment' => 'Note',
            'status' => 0,
            'department' => 5,
            'default_rights' => 3,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // For constructor, expect two prepare() calls
        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad);

        $file = new FileData(123, $pdo);

        $this->assertSame(2, $file->getCategory());
        $this->assertSame(10, $file->getOwner());
        $this->assertSame('2023-01-01 00:00:00', $file->getCreatedDate());
        $this->assertSame('Desc', $file->getDescription());
        $this->assertSame('Note', $file->getComment());
        $this->assertSame(0, $file->getStatus());
        $this->assertSame(5, $file->getDepartment());
        $this->assertSame(3, $file->getDefaultRights());
        $this->assertFalse($file->isLocked());
        $this->assertSame($realname, $file->getRealName());
    }

    public function testExistsTrue(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'exists-true.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for exists() SELECT *
        $stmtExists = \Mockery::mock(\PDOStatement::class);
        $stmtExists->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtExists->shouldReceive('rowCount')->once()->andReturn(1);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtExists);

        $file = new FileData(123, $pdo);
        $this->assertTrue($file->exists());
    }

    public function testExistsFalse(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'exists-false.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => '', 'comment' => '', 'status' => 0,
            'department' => 1, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for exists() SELECT *
        $stmtExists = \Mockery::mock(\PDOStatement::class);
        $stmtExists->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtExists->shouldReceive('rowCount')->once()->andReturn(0);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtExists);

        $file = new FileData(123, $pdo);
        $this->assertFalse($file->exists());
    }

    public function testGetCategoryName(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'catname.txt';
        $dataRow = [
            'category' => 7, 'owner' => 10, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 3, 'default_rights' => 1,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for getCategoryName()
        $stmtCat = \Mockery::mock(\PDOStatement::class);
        $stmtCat->shouldReceive('execute')->once()->with([':category_id' => 7])->andReturn(true);
        $stmtCat->shouldReceive('fetch')->once()->andReturn(['name' => 'Engineering']);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtCat);

        $file = new FileData(123, $pdo);
        $this->assertSame('Engineering', $file->getCategoryName());
    }

    public function testGetDeptName(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'deptname.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for getDeptName()
        $stmtDept = \Mockery::mock(\PDOStatement::class);
        $stmtDept->shouldReceive('execute')->once()->with([':department_id' => 9])->andReturn(true);
        $stmtDept->shouldReceive('fetchColumn')->once()->andReturn('Sales');
        $stmtDept->shouldReceive('rowCount')->once()->andReturn(1);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtDept);

        $file = new FileData(123, $pdo);
        $this->assertSame('Sales', $file->getDeptName());
    }

    public function testGetModifiedDate(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'modified.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for getModifiedDate()
        $stmtLog = \Mockery::mock(\PDOStatement::class);
        $stmtLog->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtLog->shouldReceive('fetch')->once()->andReturn(['modified_on' => '2024-09-01 12:00:00']);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtLog);

        $file = new FileData(123, $pdo);
        $this->assertSame('2024-09-01 12:00:00', $file->getModifiedDate());
    }

    public function testUpdateDataAndSetStatus(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'update.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        // Statement for updateData()
        $stmtUpdate = \Mockery::mock(\PDOStatement::class);
        $stmtUpdate->shouldReceive('execute')->once()->with(\Mockery::on(function ($params) {
            // Ensure the parameter array has all expected keys/values
            return is_array($params)
                && isset($params[':category'], $params[':owner'], $params[':description'], $params[':comment'], $params[':status'], $params[':department'], $params[':default_rights'], $params[':id'])
                && $params[':category'] === 9
                && $params[':owner'] === 8
                && $params[':description'] === 'New Desc'
                && $params[':comment'] === 'New Comment'
                && $params[':department'] === 4
                && $params[':default_rights'] === 2
                && $params[':id'] === 123;
        }))->andReturn(true);

        // Statement for setStatus()
        $stmtSetStatus = \Mockery::mock(\PDOStatement::class);
        $stmtSetStatus->shouldReceive('execute')->once()->with([
            ':status' => -1,
            ':id' => 123
        ])->andReturn(true);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtUpdate, $stmtSetStatus);

        $file = new FileData(123, $pdo);

        // Change values before update
        $file->setCategory(9);
        $file->setOwner(8);
        $file->setDescription('New Desc');
        $file->setComment('New Comment');
        $file->setDepartment(4);
        $file->setDefaultRights(2);

        $file->updateData();
        $file->setStatus(-1);

        // No assertions for void methods beyond expectations - reaching here means mocks matched
        $this->assertTrue(true);
    }

    public function testPublishableUpdate(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'pub.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        $stmtPub = \Mockery::mock(\PDOStatement::class);
        $stmtPub->shouldReceive('execute')->once()->with([
            ':boolean' => true,
            ':uid' => $_SESSION['uid'],
            ':id' => 123
        ])->andReturn(true);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtPub);

        $file = new FileData(123, $pdo);
        $file->Publishable(true);

        $this->assertTrue(true);
    }

    public function testIsPublishable(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'is-pub.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        $stmtIsPub = \Mockery::mock(\PDOStatement::class);
        $stmtIsPub->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtIsPub->shouldReceive('fetchColumn')->once()->andReturn(1);
        $stmtIsPub->shouldReceive('rowCount')->once()->andReturn(1);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtIsPub);

        $file = new FileData(123, $pdo);
        $this->assertSame(1, $file->isPublishable());
    }

    public function testIsArchived(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'archived.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        $stmtIsArch = \Mockery::mock(\PDOStatement::class);
        $stmtIsArch->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);
        $stmtIsArch->shouldReceive('fetchColumn')->once()->andReturn(2); // 2 means archived
        $stmtIsArch->shouldReceive('rowCount')->once()->andReturn(1);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtIsArch);

        $file = new FileData(123, $pdo);
        $this->assertTrue($file->isArchived());
    }

    public function testTempDeleteAndUndelete(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'tmpdel.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        $stmtTempDel = \Mockery::mock(\PDOStatement::class);
        $stmtTempDel->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);

        $stmtUndelete = \Mockery::mock(\PDOStatement::class);
        $stmtUndelete->shouldReceive('execute')->once()->with([':id' => 123])->andReturn(true);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtTempDel, $stmtUndelete);

        $file = new FileData(123, $pdo);
        $file->temp_delete();
        $file->undelete();

        $this->assertTrue(true);
    }

    public function testGetDeptRights(): void
    {
        $pdo = \Mockery::mock(PDO::class);

        $realname = 'dept-rights.txt';
        $dataRow = [
            'category' => 1, 'owner' => 1, 'created' => '2023-01-01 00:00:00',
            'description' => 'd', 'comment' => 'c', 'status' => 0,
            'department' => 9, 'default_rights' => 0,
        ];

        [$stmtFindName, $stmtLoad] = $this->createBaseStmtsForConstructor($realname, $dataRow);

        $stmtRights = \Mockery::mock(\PDOStatement::class);
        $stmtRights->shouldReceive('execute')->once()->with([
            ':fid' => 123,
            ':dept_id' => 4
        ])->andReturn(true);
        $stmtRights->shouldReceive('fetchColumn')->once()->andReturn(3);

        $pdo->shouldReceive('prepare')->andReturn($stmtFindName, $stmtLoad, $stmtRights);

        $file = new FileData(123, $pdo);
        $this->assertSame(3, $file->getDeptRights(4));
    }
}
