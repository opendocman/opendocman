<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/CategoryPerms.class.php';

class CategoryPermsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
    }

    private function mockPdoForFetch(string $queryContains, array $fetchResult): PDO
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn($fetchResult);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(fn($q) => str_contains($q, $queryContains)))
            ->andReturn($stmt);
        return $pdo;
    }

    public function testGetPermissionReturnsRightsForDept(): void
    {
        $pdo = $this->mockPdoForFetch('category_perms', ['rights' => 2]);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, deptId: 3);
        $this->assertSame(2, $result);
    }

    public function testGetPermissionReturnsRightsForUser(): void
    {
        $pdo = $this->mockPdoForFetch('category_perms', ['rights' => 4]);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, userId: 10);
        $this->assertSame(4, $result);
    }

    public function testGetPermissionReturnsNullWhenNoRow(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $result = $model->getPermission(catId: 5, deptId: 99);
        $this->assertNull($result);
    }

    public function testGetPermissionThrowsWhenBothUserAndDeptProvided(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $model = new CategoryPerms($pdo);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provide userId or deptId, not both');
        $model->getPermission(catId: 5, userId: 10, deptId: 3);
    }

    public function testGetTemplateReturnsAllPermsForCategory(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([
            ['dept_id' => '3', 'user_id' => null, 'rights' => '2'],
            ['dept_id' => null, 'user_id' => '10', 'rights' => '4'],
        ]);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $result = $model->getTemplate(5);
        $this->assertCount(2, $result);
        $this->assertSame(2, $result[0]['rights']);
    }

    public function testSaveTemplateReplacesAllRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->once()->with([
            ':cat_id' => 5, ':dept_id' => 3, ':user_id' => null, ':rights' => 2
        ])->andReturn(true);
        $pdo->shouldReceive('prepare')->twice()->andReturn($stmtDelete, $stmtInsert);
        $model = new CategoryPerms($pdo);
        $model->saveTemplate(5, [
            ['dept_id' => 3, 'user_id' => null, 'rights' => 2],
        ]);
    }

    public function testDeleteTemplateExecutesDelete(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(fn($q) => str_contains($q, 'DELETE FROM') && str_contains($q, 'category_perms')))
            ->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $model->deleteTemplate(5);
    }

    public function testSaveTemplateSkipsZeroRightsRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->never();
        $pdo->shouldReceive('prepare')->andReturn($stmtDelete, $stmtInsert);
        $model = new CategoryPerms($pdo);
        $model->saveTemplate(5, [
            ['dept_id' => 3, 'user_id' => null, 'rights' => 0],
        ]);
    }

    public function testSaveTemplateWithEmptyArrayOnlyDeletes(): void
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(fn($q) => str_contains($q, 'DELETE')))
            ->andReturn($stmt);
        $model = new CategoryPerms($pdo);
        $model->saveTemplate(5, []);
    }

    public function testSaveTemplateWithMixedRightsOnlyInsertsNonZero(): void
    {
        $insertCount = 0;
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 5])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')
            ->times(2)
            ->andReturnUsing(function () use (&$insertCount) {
                $insertCount++;
                return true;
            });
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtDelete, $stmtInsert);
        $model = new CategoryPerms($pdo);
        $model->saveTemplate(5, [
            ['dept_id' => 1, 'user_id' => null, 'rights' => 0],
            ['dept_id' => 2, 'user_id' => null, 'rights' => 1],
            ['dept_id' => null, 'user_id' => 10, 'rights' => 0],
            ['dept_id' => null, 'user_id' => 11, 'rights' => 2],
        ]);
        $this->assertSame(2, $insertCount);
    }
}