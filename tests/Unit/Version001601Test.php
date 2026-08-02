<?php

require_once APPLICATION_PATH . '/installer/migrations/MigrationInterface.php';
require_once APPLICATION_PATH . '/installer/migrations/Version001601.php';

class Version001601Test extends TestCase
{
    private $pdo;
    private $stmt;
    private $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = \Mockery::mock(PDO::class);
        $this->stmt = \Mockery::mock(\PDOStatement::class);

        $this->migration = new Version001601();
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersionReturns161(): void
    {
        $this->assertSame('1.6.1', $this->migration->getVersion());
    }

    public function testGetDescriptionReturnsExpectedString(): void
    {
        $this->assertSame(
            'Add incomingDir config setting for incoming revision staging',
            $this->migration->getDescription()
        );
    }

    public function testUpQueriesDataDirAndInsertsIncomingDir(): void
    {
        $dataDirVal = '/var/www/custom_repo/';
        $expectedIncomingDir = $dataDirVal . 'incoming/';

        $dataDirStmt = \Mockery::mock(\PDOStatement::class);
        $dataDirStmt->shouldReceive('execute')->once()->with([':name' => 'dataDir'])->andReturn(true);
        $dataDirStmt->shouldReceive('fetchColumn')->once()->andReturn($dataDirVal);

        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(0);

        $insertStmt = \Mockery::mock(\PDOStatement::class);
        $insertStmt->shouldReceive('execute')->once()->with(\Mockery::on(function ($params) use ($expectedIncomingDir) {
            return $params[':name'] === 'incomingDir'
                && $params[':value'] === $expectedIncomingDir
                && $params[':description'] === 'Location for incoming file revisions that have not yet been approved. Default is inside dataDir.'
                && $params[':validation'] === 'maxsize=255';
        }))->andReturn(true);

        $this->pdo->shouldReceive('prepare')
            ->with('SELECT `value` FROM `odm_settings` WHERE `name` = :name')
            ->once()
            ->ordered()
            ->andReturn($dataDirStmt);

        $this->pdo->shouldReceive('prepare')
            ->with("SELECT COUNT(*) FROM `odm_settings` WHERE name = 'incomingDir'")
            ->once()
            ->ordered()
            ->andReturn($countStmt);

        $this->pdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/INSERT INTO/'))
            ->once()
            ->ordered()
            ->andReturn($insertStmt);

        $this->migration->up($this->pdo, 'odm_');
    }

    public function testUpSkipsInsertIfAlreadyExists(): void
    {
        $countStmt = \Mockery::mock(\PDOStatement::class);
        $countStmt->shouldReceive('execute')->once()->andReturn(true);
        $countStmt->shouldReceive('fetchColumn')->once()->andReturn(1);

        $dataDirStmt = \Mockery::mock(\PDOStatement::class);
        $dataDirStmt->shouldReceive('execute')->once()->with([':name' => 'dataDir'])->andReturn(true);
        $dataDirStmt->shouldReceive('fetchColumn')->once()->andReturn('/var/www/repo/');

        $this->pdo->shouldReceive('prepare')
            ->andReturn($dataDirStmt, $countStmt);

        $this->pdo->shouldNotReceive('exec');

        $this->migration->up($this->pdo, 'odm_');
    }

    public function testDownRemovesSetting(): void
    {
        $this->pdo->shouldReceive('exec')
            ->with("DELETE FROM `odm_settings` WHERE name = 'incomingDir'")
            ->once()
            ->andReturn(1);

        $this->migration->down($this->pdo, 'odm_');
    }
}