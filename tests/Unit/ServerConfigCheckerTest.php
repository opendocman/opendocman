<?php

use PHPUnit\Framework\TestCase;

class ServerConfigCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new ServerConfigChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckIncludesFileUploads(): void
    {
        $checker = new ServerConfigChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('file_uploads', $names);
    }

    public function testGetName(): void
    {
        $checker = new ServerConfigChecker();
        $this->assertEquals('Server Configuration', $checker->getName());
    }
}