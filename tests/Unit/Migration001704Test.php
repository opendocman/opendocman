<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/installer/migrations/MigrationInterface.php';
require_once APPLICATION_PATH . '/installer/migrations/Version001704.php';

class Migration001704Test extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testUpInsertsDefaultSignupDepartmentSetting(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->once()
            ->with(\Mockery::pattern('/INSERT INTO.*settings.*default_signup_department/'))
            ->andReturn(1);

        $migration = new Version001704();
        $migration->up($pdo, 'odm_');
    }

    public function testDownDeletesTheSetting(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->once()
            ->with(\Mockery::pattern('/DELETE FROM.*settings.*default_signup_department/'))
            ->andReturn(1);

        $migration = new Version001704();
        $migration->down($pdo, 'odm_');
    }

    public function testVersionIs174(): void
    {
        $this->assertSame('1.7.4', (new Version001704())->getVersion());
    }
}
