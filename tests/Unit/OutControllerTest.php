<?php

use PHPUnit\Framework\TestCase;

class OutControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        if (isset($GLOBALS['CONFIG']['db_prefix'])) {
            unset($GLOBALS['CONFIG']['db_prefix']);
        }
        parent::tearDown();
    }

    public function testCountUnassignedUsersHelper(): void
    {
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';

        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchColumn')->once()->andReturn(2);

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::pattern('/department IS NULL/i'))->andReturn($stmt);

        $this->assertSame(2, User::countUnassignedUsers($pdo));
    }
}
