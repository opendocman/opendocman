<?php

use PHPUnit\Framework\TestCase;

class ComposerDependencyCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new ComposerDependencyChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testChecksVendorAutoloadExists(): void
    {
        $checker = new ComposerDependencyChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('Composer Autoloader', $names);
    }

    public function testGetName(): void
    {
        $checker = new ComposerDependencyChecker();
        $this->assertEquals('Composer Dependencies', $checker->getName());
    }
}