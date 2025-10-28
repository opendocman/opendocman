<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/Reviewer.class.php';

class ReviewerTest extends TestCase
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

    public function testGetReviewersForDepartmentReturnsArrayOfUserIds(): void
    {
        $deptId = 3;

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // Prepare should return our mock statement
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                // Ensure correct table and WHERE clause are used
                return is_string($sql)
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}dept_reviewer") !== false
                    && stripos($sql, 'SELECT') !== false
                    && stripos($sql, 'user_id') !== false
                    && stripos($sql, 'WHERE') !== false
                    && stripos($sql, 'dept_id = :dept_id') !== false;
            }))
            ->andReturn($stmt);

        // Execute with the expected parameter
        $stmt->shouldReceive('execute')
            ->once()
            ->with([':dept_id' => $deptId])
            ->andReturn(true);

        // Return two reviewers
        $stmt->shouldReceive('fetchAll')
            ->once()
            ->andReturn([
                ['user_id' => 11],
                ['user_id' => 42],
            ]);

        // Row count should be >= 1 to avoid false
        $stmt->shouldReceive('rowCount')
            ->once()
            ->andReturn(2);

        $model = new Reviewer(1, $pdo);
        $result = $model->getReviewersForDepartment($deptId);

        $this->assertIsArray($result);
        $this->assertSame([11, 42], $result);
    }

    public function testGetReviewersForDepartmentReturnsFalseWhenNoRows(): void
    {
        $deptId = 999;

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::type('string'))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')
            ->once()
            ->with([':dept_id' => $deptId])
            ->andReturn(true);

        // No reviewers found
        $stmt->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);

        $stmt->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);

        $model = new Reviewer(1, $pdo);
        $result = $model->getReviewersForDepartment($deptId);

        $this->assertFalse($result, 'Expected false when no reviewers are found for department');
    }

    public function testGetReviewersForDepartmentReturnsSingleUserIdArray(): void
    {
        $deptId = 2;

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::type('string'))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')
            ->once()
            ->with([':dept_id' => $deptId])
            ->andReturn(true);

        // Single reviewer row
        $stmt->shouldReceive('fetchAll')
            ->once()
            ->andReturn([
                ['user_id' => 7],
            ]);

        $stmt->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);

        $model = new Reviewer(1, $pdo);
        $result = $model->getReviewersForDepartment($deptId);

        $this->assertIsArray($result);
        $this->assertSame([7], $result);
    }
}
