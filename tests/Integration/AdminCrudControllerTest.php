<?php

use PHPUnit\Framework\TestCase;

class AdminCrudControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['CONFIG'] = [
            'root_id' => 1,
            'db_prefix' => 'odm_',
            'authen' => 'mysql',
            'demo' => 'False',
        ];
        $this->mockPdo = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);
        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('fetchColumn')->andReturn(0)->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('99')->byDefault();
        $GLOBALS['pdo'] = $this->mockPdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['uid'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testListUsersEndpointReturnsCorrectStructure(): void
    {
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => '1', 'username' => 'admin', 'last_name' => 'Admin', 'first_name' => 'User', 'Email' => 'a@b.com', 'phone' => '', 'department' => '1', 'can_add' => '1', 'can_checkin' => '1', 'department_name' => 'Engineering', 'is_admin' => '1'],
        ]);
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT COUNT\(\*\) FROM.*user/'))
            ->once()
            ->andReturn($countStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT.*FROM.*user.*LEFT JOIN.*department.*LEFT JOIN.*admin/'))
            ->once()
            ->andReturn($dataStmt);

        ob_start();
        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25];
        $_REQUEST = $_GET;
        require dirname(__DIR__, 2) . '/application/controllers/admin_crud_ajax.php';
        $output = ob_get_clean();
        $result = json_decode($output, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('last_row', $result);
    }

    public function testNonAdminGetsForbidden(): void
    {
        $_SESSION['uid'] = 2;
        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25];
        $_REQUEST = $_GET;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $findStmt = \Mockery::mock(\PDOStatement::class);
        $findStmt->shouldReceive('execute')->once()->andReturn(true);
        $findStmt->shouldReceive('fetchAll')->once()->andReturn([['nonadmin']]);
        $findStmt->shouldReceive('rowCount')->once()->andReturn(1);

        $userStmt = \Mockery::mock(\PDOStatement::class);
        $userStmt->shouldReceive('execute')->once()->andReturn(true);
        $userStmt->shouldReceive('fetch')->once()->andReturn(['2', 'nonadmin', '1', '', 'e@m.com', 'Doe', 'John', null, '0', '0', '0']);

        $adminStmt = \Mockery::mock(\PDOStatement::class);
        $adminStmt->shouldReceive('execute')->once()->andReturn(true);
        $adminStmt->shouldReceive('fetchColumn')->once()->andReturn(false);
        $adminStmt->shouldReceive('rowCount')->once()->andReturn(0);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT\s+username\s+FROM/si'))
            ->once()
            ->andReturn($findStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT\s+id\s*,\s*username/si'))
            ->once()
            ->andReturn($userStmt);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT\s+admin\s+FROM/si'))
            ->once()
            ->andReturn($adminStmt);

        ob_start();
        require dirname(__DIR__, 2) . '/application/controllers/admin_crud_ajax.php';
        $output = ob_get_clean();
        $result = json_decode($output, true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Forbidden', $result['error']);
        $this->assertEquals(403, http_response_code());
    }

    public function testListUsersWithUnassignedFilterAddsWhereClause(): void
    {
        $dataStmt = \Mockery::mock(\PDOStatement::class);
        $dataStmt->shouldReceive('execute')->once()->andReturn(true);
        $dataStmt->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => '5', 'username' => 'newbie', 'last_name' => 'New', 'first_name' => 'User', 'Email' => 'n@e.com', 'phone' => '', 'department' => null, 'can_add' => '0', 'can_checkin' => '0', 'department_name' => null, 'is_admin' => '0'],
        ]);
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/department IS NULL/'))
            ->twice()
            ->andReturnUsing(function ($sql) use ($dataStmt, $countStmt) {
                return strpos($sql, 'SELECT COUNT(*)') === 0 ? $countStmt : $dataStmt;
            });

        ob_start();
        $_GET = ['entity' => 'users', 'action' => 'list', 'page' => 1, 'size' => 25, 'filter' => 'unassigned'];
        $_REQUEST = $_GET;
        require dirname(__DIR__, 2) . '/application/controllers/admin_crud_ajax.php';
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertSame(1, $json['last_row']);
    }

    protected function tearDown(): void
    {
        $this->mockPdo = null;
        $this->mockStatement = null;
        unset($GLOBALS['pdo']);
        unset($GLOBALS['CONFIG']);
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }
}