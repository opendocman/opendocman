<?php

use PHPUnit\Framework\TestCase;

class CategoryTemplateControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetPermsJsonReturnsEmptyWhenNoPerms(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('getTemplate')->with(1)->andReturn([]);
        $result = $catPerms->getTemplate(1);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPermsJsonReturnsDeptAndUserPerms(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('getTemplate')->with(2)->andReturn([
            ['dept_id' => '3', 'user_id' => null, 'rights' => '1'],
            ['dept_id' => null, 'user_id' => '10', 'rights' => '4'],
        ]);
        $rows = $catPerms->getTemplate(2);
        $deptPerms = [];
        $userPerms = [];
        foreach ($rows as $row) {
            $rights = (int)$row['rights'];
            if ($row['dept_id'] !== null) {
                $deptPerms[(int)$row['dept_id']] = $rights;
            } elseif ($row['user_id'] !== null) {
                $userPerms[(int)$row['user_id']] = $rights;
            }
        }
        $this->assertEquals([3 => 1], $deptPerms);
        $this->assertEquals([10 => 4], $userPerms);
    }

    public function testSaveTemplateSkipsZeroRights(): void
    {
        $catPerms = \Mockery::mock(CategoryPerms::class);
        $catPerms->shouldReceive('saveTemplate')->with(1, \Mockery::on(function ($perms) {
            foreach ($perms as $p) {
                if ((int)$p['rights'] === 0) {
                    return false;
                }
            }
            return true;
        }))->once();
        $catPerms->saveTemplate(1, [['dept_id' => 3, 'user_id' => null, 'rights' => 2]]);
    }

    public function testSaveTemplateOnlyStoresNonZeroRights(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtDelete = \Mockery::mock(\PDOStatement::class);
        $stmtDelete->shouldReceive('execute')->once()->with([':cat_id' => 1])->andReturn(true);
        $stmtInsert = \Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->once()->with([
            ':cat_id' => 1, ':dept_id' => 3, ':user_id' => null, ':rights' => 2
        ])->andReturn(true);
        $pdo->shouldReceive('prepare')->twice()->andReturn($stmtDelete, $stmtInsert);

        $model = new CategoryPerms($pdo);
        $model->saveTemplate(1, [
            ['dept_id' => 3, 'user_id' => null, 'rights' => 2],  // stored
            ['dept_id' => 4, 'user_id' => null, 'rights' => 0],  // skipped
        ]);
    }
}