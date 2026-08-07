<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/CategoryPerms.class.php';

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
    }

    public function testFullFlowWithMockedPdo(): void
    {
        $stmtStub = \Mockery::mock(\PDOStatement::class);
        $stmtStub->shouldReceive('execute')->andReturn(true)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchAll')->andReturn([])->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetch')->andReturn(false)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('rowCount')->andReturn(0)->zeroOrMoreTimes();
        $stmtStub->shouldReceive('fetchColumn')->andReturn(0)->zeroOrMoreTimes();

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtStub)->zeroOrMoreTimes();

        $catPerms = new CategoryPerms($pdo);
        $template = $catPerms->getTemplate(1);
        $this->assertIsArray($template);
    }
}