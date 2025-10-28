<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/Email.class.php';

class EmailTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal config and demo mode to avoid actual mail() calls
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['root_id'] = 1;
        $GLOBALS['CONFIG']['demo'] = 'True';

        // Provide a global PDO mock for helpers that construct User objects
        // and for User constructor queries invoked during email sending.
        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // Return our statement for any prepare() call
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        // Default behaviors for statements used by User constructor and various helpers
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('fetchAll')->andReturn([]);   // findName()/list queries
        $stmt->shouldReceive('rowCount')->andReturn(0);    // no rows found by default
        $stmt->shouldReceive('fetch')->andReturn(false);   // no row for User constructor
    }

    protected function tearDown(): void
    {
        // Clear global PDO to avoid bleed-over
        global $pdo;
        $pdo = null;

        parent::tearDown();
    }

    public function testDefaultGetFullNameReturnsFalse(): void
    {
        $email = new Email();
        $this->assertFalse($email->getFullName());
    }

    public function testSettersAndGetters(): void
    {
        $email = new Email();

        // Full name
        $this->assertFalse($email->getFullName());
        $email->setFullName('John Doe');
        $this->assertSame('John Doe', $email->getFullName());

        // From
        $this->assertNull($email->getFrom());
        $email->setFrom('noreply@example.com');
        $this->assertSame('noreply@example.com', $email->getFrom());

        // Subject
        $this->assertNull($email->getSubject());
        $email->setSubject('Hello');
        $this->assertSame('Hello', $email->getSubject());

        // Body
        $this->assertNull($email->getBody());
        $email->setBody('This is a test');
        $this->assertSame('This is a test', $email->getBody());

        // Recipients
        $this->assertNull($email->getRecipients());
        $result = $email->setRecipients('not-an-array');
        $this->assertFalse($result, 'setRecipients should return false for non-array input');

        $recipients = [1, 2, 3];
        $email->setRecipients($recipients);
        $this->assertSame($recipients, $email->getRecipients());
    }

    public function testSendEmailGeneratesHeadersWithFromAndRecipients(): void
    {
        $email = new Email();
        $email->setFrom('noreply@example.com');
        $email->setSubject('Subject');
        $email->setBody('Body');
        $email->setRecipients([1, 2]);

        $this->assertTrue($email->sendEmail(), 'sendEmail should return true');

        $expectedHeaders = 'From: noreply@example.com' . PHP_EOL .
                           'Content-Type: text/plain; charset=UTF-8' . PHP_EOL;

        $this->assertSame($expectedHeaders, $email->getHeaders(), 'Headers should be set with From and Content-Type');
    }

    public function testSendEmailDoesNotGenerateHeadersWithoutFrom(): void
    {
        $email = new Email();
        $email->setSubject('No From');
        $email->setBody('Body');
        $email->setRecipients([42]);

        $this->assertTrue($email->sendEmail(), 'sendEmail should return true even without From');
        $this->assertNull($email->getHeaders(), 'Headers should not be set if From is not provided');
    }

    public function testSendEmailReturnsTrueWithEmptyRecipientsAndDoesNothing(): void
    {
        $email = new Email();
        $email->setFrom('noreply@example.com');
        $email->setSubject('Empty Recipients');
        $email->setBody('Body');
        $email->setRecipients([]);

        $this->assertTrue($email->sendEmail(), 'sendEmail should return true with empty recipients');
        // Since recipients are empty, headers are never generated
        $this->assertNull($email->getHeaders(), 'Headers should not be set when recipients are empty');
    }
}
