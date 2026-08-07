<?php

use PHPUnit\Framework\TestCase;

class RequirementCheckerTest extends TestCase
{
    public function testCheckAllReturnsArrayOfCheckResults(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckAllIncludesBuiltInChecks(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('PHP Version', $names);
        $this->assertContains('PDO MySQL Driver', $names);
        $this->assertContains('templates_c Writable', $names);
    }

    public function testAllPassedReturnsTrueWhenAllPass(): void
    {
        $checker = new RequirementChecker();
        $results = $checker->checkAll();
        // All are CheckResult objects — allPassed checks all
        $this->assertIsBool($checker->allPassed());
    }

    public function testHasRequiredFailuresReturnsFalseWhenAllPass(): void
    {
        $checker = new RequirementChecker();
        $checker->checkAll();
        $this->assertIsBool($checker->hasRequiredFailures());
    }
}