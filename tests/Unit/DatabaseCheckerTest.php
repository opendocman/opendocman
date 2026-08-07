<?php

use PHPUnit\Framework\TestCase;

class DatabaseCheckerTest extends TestCase
{
    public function testCheckAcceptsPdoAndReturnsArray(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('8.0.35');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testPassesOnMySQL80(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('8.0.35');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertTrue($results[0]->passed);
    }

    public function testPassesOnMariaDB106(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('10.6.18-MariaDB');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertTrue($results[0]->passed);
    }

    public function testFailsOnMySQL55(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_SERVER_VERSION)->andReturn('5.5.62');
        $checker = new DatabaseChecker($pdo);
        $results = $checker->check();
        $this->assertFalse($results[0]->passed);
    }

    public function testGetName(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $checker = new DatabaseChecker($pdo);
        $this->assertEquals('Database Server', $checker->getName());
    }
}