<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/databaseData.class.php';

class DatabaseDataUnitTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        // Ensure a DB prefix is available for SQL construction
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';

        // Some legacy code accesses "result_limit" as a bare constant instead of $this->result_limit
        // Define it here for test environment to avoid "Undefined constant result_limit" notices.
        if (!defined('result_limit')) {
            define('result_limit', 'UNLIMITED');
        }
    }

    /**
     * A small concrete subclass to exercise the base class behavior.
     */
    private function makeDummy(int $id, PDO $pdo): DummyDatabaseData
    {
        return new DummyDatabaseData($id, $pdo);
    }

    public function testConstructorSetsConnectionAndIdAndLoadsName(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);

        // databaseData::__construct -> setId($id) -> findName() executes this
        $pdo->shouldReceive('prepare')->once()->with(\Mockery::type('string'))->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['alice']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(5, $pdo);

        // Expose connection and verify
        $this->assertSame($pdo, $dummy->getConnection());
        $this->assertSame(5, $dummy->getId());
        $this->assertSame('alice', $dummy->getName());
    }

    public function testSetTableNameUpdatesProperty(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);

        // Initial constructor findName call
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['initial']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(1, $pdo);
        $this->assertSame('user', $dummy->tablename);

        $dummy->setTableName('custom_table');
        $this->assertSame('custom_table', $dummy->tablename);
    }

    public function testFindIdReturnsIdWhenSingleRow(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindId = \Mockery::mock(\PDOStatement::class);

        // Constructor path: findName
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 10])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['loaded_name']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(10, $pdo);

        // Now set the name we want to look up by
        $dummy->name = 'alice';

        // findId path: select id where field_name = :name
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindId);
        $stmtFindId->shouldReceive('execute')->once()->with([':name' => 'alice'])->andReturn(true);
        $stmtFindId->shouldReceive('fetchAll')->once()->andReturn([[42]]);
        $stmtFindId->shouldReceive('rowCount')->once()->andReturn(1);

        $id = $dummy->findId();
        $this->assertSame(42, $id);
    }

    public function testFindIdSetsErrorAndReturnsNullWhenNoRows(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);
        $stmtFindId = \Mockery::mock(\PDOStatement::class);

        // Constructor findName
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 2])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['name2']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(2, $pdo);

        // Set the name to find
        $dummy->name = 'missing';

        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindId);
        $stmtFindId->shouldReceive('execute')->once()->with([':name' => 'missing'])->andReturn(true);
        $stmtFindId->shouldReceive('fetchAll')->once()->andReturn([]);
        $stmtFindId->shouldReceive('rowCount')->once()->andReturn(0);

        $result = @$dummy->findId();
        $this->assertNull($result);
        $this->assertSame('Error: unable to fine id in database', $dummy->getError());
    }

    public function testFindNameSetsErrorWhenTableNameNotSet(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);

        // First normal constructor call
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 3])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['valid']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(3, $pdo);
        $this->assertSame('valid', $dummy->getName());

        // Now simulate missing table name and call findName directly
        $dummy->tablename = '';
        $name = $dummy->findName();
        $this->assertSame('', $name);
        $this->assertSame('Error: table name not set', $dummy->getError());
    }

    public function testReloadDataCallsSetIdAndUpdatesName(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName1 = \Mockery::mock(\PDOStatement::class);
        $stmtFindName2 = \Mockery::mock(\PDOStatement::class);

        // First constructor-time findName
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName1);
        $stmtFindName1->shouldReceive('execute')->once()->with([':id' => 7])->andReturn(true);
        $stmtFindName1->shouldReceive('fetchAll')->once()->andReturn([['first']]);
        $stmtFindName1->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(7, $pdo);
        $this->assertSame('first', $dummy->getName());

        // Now reloadData -> setId(7) -> findName() again returns 'second'
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName2);
        $stmtFindName2->shouldReceive('execute')->once()->with([':id' => 7])->andReturn(true);
        $stmtFindName2->shouldReceive('fetchAll')->once()->andReturn([['second']]);
        $stmtFindName2->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy->reloadData();
        $this->assertSame('second', $dummy->getName());
    }

    public function testCombineArraysMergesWithoutDuplicates(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtFindName = \Mockery::mock(\PDOStatement::class);

        $pdo->shouldReceive('prepare')->once()->andReturn($stmtFindName);
        $stmtFindName->shouldReceive('execute')->once()->with([':id' => 1])->andReturn(true);
        $stmtFindName->shouldReceive('fetchAll')->once()->andReturn([['n']]);
        $stmtFindName->shouldReceive('rowCount')->once()->andReturn(1);

        $dummy = $this->makeDummy(1, $pdo);

        $high = [1, 2, 3];
        $low = [3, 4, 5];

        $merged = $dummy->combineArrays($high, $low);
        $this->assertSame([1, 2, 3, 4, 5], $merged);
    }
}

/**
 * Dummy concrete implementation of databaseData for unit testing base behavior.
 */
class DummyDatabaseData extends databaseData
{
    public function __construct($id, PDO $connection)
    {
        // Set the required fields before invoking the base constructor
        $this->tablename = 'user';
        $this->field_name = 'username';
        $this->field_id = 'id';
        parent::__construct($id, $connection);
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
