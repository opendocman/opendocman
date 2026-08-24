<?php

use PHPUnit\Framework\TestCase;

class PhpExtensionCheckerTest extends TestCase
{
    public function testCheckReturnsArrayOfCheckResult(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(CheckResult::class, $result);
        }
    }

    public function testCheckIncludesAllExpectedExtensions(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        $names = array_map(fn($r) => $r->name, $results);
        $this->assertContains('PHP Extension: zip', $names);
        $this->assertContains('PHP Extension: dom', $names);
        $this->assertContains('PHP Extension: xml', $names);
        $this->assertContains('PHP Extension: mbstring', $names);
        $this->assertContains('PHP Extension: fileinfo', $names);
        $this->assertContains('PHP Extension: gd', $names);
        $this->assertContains('PHP Extension: imap', $names);
    }

    public function testZipExtensionIsRequired(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        foreach ($results as $result) {
            if ($result->name === 'PHP Extension: zip') {
                $this->assertEquals('required', $result->severity);
                return;
            }
        }
        $this->fail('zip extension check not found');
    }

    public function testGdExtensionIsRecommended(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        foreach ($results as $result) {
            if ($result->name === 'PHP Extension: gd') {
                $this->assertEquals('recommended', $result->severity);
                return;
            }
        }
        $this->fail('gd extension check not found');
    }

    public function testGetName(): void
    {
        $checker = new PhpExtensionChecker();
        $this->assertEquals('PHP Extensions', $checker->getName());
    }

    public function testImapExtensionIsRecommended(): void
    {
        $checker = new PhpExtensionChecker();
        $results = $checker->check();
        foreach ($results as $result) {
            if ($result->name === 'PHP Extension: imap') {
                $this->assertEquals('recommended', $result->severity);
                return;
            }
        }
        $this->fail('imap extension check not found');
    }
}