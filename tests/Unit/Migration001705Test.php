<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/installer/migrations/MigrationInterface.php';
require_once APPLICATION_PATH . '/installer/migrations/Version001705.php';

class Migration001705Test extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testVersionIs175(): void
    {
        $this->assertSame('1.7.5', (new Version001705())->getVersion());
    }

    public function testUpAddsMailTokenColumnToUser(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/ALTER TABLE.*user.*ADD COLUMN.*mail_token/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

    public function testUpCreatesEmailAuditTable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/CREATE TABLE.*email_audit/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

public function testUpInsertsMailSettings(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/INSERT INTO.*settings.*incoming_mail_enabled/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/INSERT INTO.*settings.*incoming_mail_host/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')->andReturn(1);
        $migration = new Version001705();
        $migration->up($pdo, 'odm_');
    }

    public function testDownDropsEmailAuditTable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->with(\Mockery::pattern('/DROP TABLE.*email_audit/'))
            ->once()
            ->andReturn(1);
        $pdo->shouldReceive('exec')->andReturn(1);
        $migration = new Version001705();
        $migration->down($pdo, 'odm_');
    }
}