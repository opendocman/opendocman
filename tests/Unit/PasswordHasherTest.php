<?php

use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function testHashProducesBcryptHash(): void
    {
        $hash = PasswordHasher::hash('secret123');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertNotSame('secret123', $hash);
    }

    public function testHashIsSalted(): void
    {
        $this->assertNotSame(
            PasswordHasher::hash('secret123'),
            PasswordHasher::hash('secret123')
        );
    }

    public function testVerifyMatchesBcryptHash(): void
    {
        $hash = PasswordHasher::hash('secret123');
        $this->assertTrue(PasswordHasher::verify('secret123', $hash));
        $this->assertFalse(PasswordHasher::verify('wrong', $hash));
    }

    public function testVerifyMatchesLegacyMd5Hash(): void
    {
        $this->assertTrue(PasswordHasher::verify('secret123', md5('secret123')));
        $this->assertFalse(PasswordHasher::verify('wrong', md5('secret123')));
    }

    public function testNeedsRehashForMd5(): void
    {
        $this->assertTrue(PasswordHasher::needsRehash(md5('secret123')));
    }

    public function testNeedsRehashForCurrentBcrypt(): void
    {
        $this->assertFalse(PasswordHasher::needsRehash(PasswordHasher::hash('secret123')));
    }
}
