<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/MailToken.class.php';
require_once APPLICATION_PATH . '/models/User.class.php';

class MailTokenTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGenerateReturnsOdmPrefixedToken(): void
    {
        $token = MailToken::generate();
        $this->assertSame(0, strpos($token, 'odm-'));
        $this->assertGreaterThan(8, strlen($token));
    }

    public function testGenerateIsUnique(): void
    {
        $this->assertNotSame(MailToken::generate(), MailToken::generate());
    }
}