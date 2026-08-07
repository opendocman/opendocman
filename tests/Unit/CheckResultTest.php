<?php

use PHPUnit\Framework\TestCase;

class CheckResultTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $result = new CheckResult('PHP Extension: zip', 'Required for Office docs', 'Missing', false, 'required');
        $this->assertEquals('PHP Extension: zip', $result->name);
        $this->assertEquals('Required for Office docs', $result->required);
        $this->assertEquals('Missing', $result->actual);
        $this->assertFalse($result->passed);
        $this->assertEquals('required', $result->severity);
    }

    public function testDefaultsToRequiredSeverity(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true);
        $this->assertEquals('required', $result->severity);
    }

    public function testIsRequiredReturnsTrueForRequired(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true, 'required');
        $this->assertTrue($result->isRequired());
    }

    public function testIsRequiredReturnsFalseForRecommended(): void
    {
        $result = new CheckResult('Test', 'Test', 'OK', true, 'recommended');
        $this->assertFalse($result->isRequired());
    }
}