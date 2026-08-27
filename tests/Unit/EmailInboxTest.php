<?php

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once APPLICATION_PATH . '/models/EmailMessage.class.php';
require_once APPLICATION_PATH . '/models/EmailInboxException.class.php';
require_once APPLICATION_PATH . '/models/EmailInbox.class.php';

class EmailInboxTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMessageDtoExposesFields(): void
    {
        $m = new EmailMessage('123', 'subject', 'sender@example.com');
        $m->attachments[] = ['name' => 'a.pdf', 'path' => '/tmp/a', 'mime' => 'application/pdf'];
        $this->assertSame('123', $m->id);
        $this->assertSame('sender@example.com', $m->from);
        $this->assertSame('a.pdf', $m->attachments[0]['name']);
        $this->assertSame('application/pdf', $m->attachments[0]['mime']);
    }

    public function testFetchMessagesTracksTempFilesAndCleanupRemovesThem(): void
    {
        $attachment = \Mockery::mock('\Webklex\PHPIMAP\Attachment');
        $attachment->shouldReceive('getName')->andReturn('a.pdf');
        $attachment->shouldReceive('getContent')->andReturn('%PDF-1.4 fake attachment bytes');
        $attachment->shouldReceive('getMimeType')->andReturn('application/pdf');
        $attachment->shouldReceive('getContentType')->andReturn('application/pdf');
        $attachment->shouldReceive('getType')->andReturn('application');

        $message = \Mockery::mock('\Webklex\PHPIMAP\Message');
        $message->shouldReceive('getUid')->andReturn('42');
        $message->shouldReceive('getSubject')->andReturn('Hello from sender');
        $message->shouldReceive('getFrom')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('body text');
        $message->shouldReceive('getHTMLBody')->andReturn('');
        $message->shouldReceive('getAttachments')->andReturn(new \Webklex\PHPIMAP\Support\AttachmentCollection([$attachment]));

        $query = \Mockery::mock('\Webklex\PHPIMAP\Query\WhereQuery');
        $query->shouldReceive('unseen')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new \Webklex\PHPIMAP\Support\MessageCollection([$message]));

        $folder = \Mockery::mock('\Webklex\PHPIMAP\Folder');
        $folder->shouldReceive('messages')->andReturn($query);

        $client = \Mockery::mock('\Webklex\PHPIMAP\Client');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);

        $inbox = new EmailInbox([
            'host' => 'localhost',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'user' => 'u',
            'pass' => 'p',
        ]);
        $this->injectClient($inbox, $client);

        $messages = $inbox->fetchMessages();

        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages[0]->attachments);
        $this->assertSame('42', $messages[0]->id);
        $this->assertSame('a.pdf', $messages[0]->attachments[0]['name']);
        $this->assertSame('application/pdf', $messages[0]->attachments[0]['mime']);

        $path = $messages[0]->attachments[0]['path'];
        $this->assertTrue(is_file($path), 'attachment temp file should exist on disk');
        $this->assertTrue(
            strncmp(basename($path), 'odm_att_', strlen('odm_att_')) === 0,
            'temp file should live under the adapter-private odm_att_ prefix'
        );

        $inbox->cleanup();
        $this->assertFalse(is_file($path), 'cleanup() should delete the temp file');
    }

    public function testCleanupIsIdempotent(): void
    {
        $attachment = \Mockery::mock('\Webklex\PHPIMAP\Attachment');
        $attachment->shouldReceive('getName')->andReturn('a.pdf');
        $attachment->shouldReceive('getContent')->andReturn('bytes');
        $attachment->shouldReceive('getMimeType')->andReturn('application/pdf');
        $attachment->shouldReceive('getContentType')->andReturn('application/pdf');
        $attachment->shouldReceive('getType')->andReturn('application');

        $message = \Mockery::mock('\Webklex\PHPIMAP\Message');
        $message->shouldReceive('getUid')->andReturn('7');
        $message->shouldReceive('getSubject')->andReturn('s');
        $message->shouldReceive('getFrom')->andReturn(null);
        $message->shouldReceive('getTextBody')->andReturn('b');
        $message->shouldReceive('getHTMLBody')->andReturn('');
        $message->shouldReceive('getAttachments')->andReturn(new \Webklex\PHPIMAP\Support\AttachmentCollection([$attachment]));

        $query = \Mockery::mock('\Webklex\PHPIMAP\Query\WhereQuery');
        $query->shouldReceive('unseen')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new \Webklex\PHPIMAP\Support\MessageCollection([$message]));

        $folder = \Mockery::mock('\Webklex\PHPIMAP\Folder');
        $folder->shouldReceive('messages')->andReturn($query);

        $client = \Mockery::mock('\Webklex\PHPIMAP\Client');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);

        $inbox = new EmailInbox([
            'host' => 'localhost',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'user' => 'u',
            'pass' => 'p',
        ]);
        $this->injectClient($inbox, $client);

        $inbox->fetchMessages();
        $inbox->cleanup();
        // Second cleanup is a no-op and must not error.
        $inbox->cleanup();
        $this->assertTrue(true);
    }

    public function testFetchThrowsEmailInboxExceptionWhenFolderIsMissing(): void
    {
        $client = \Mockery::mock('\Webklex\PHPIMAP\Client');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn(null);

        $inbox = new EmailInbox([
            'host' => 'localhost',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'user' => 'u',
            'pass' => 'p',
        ]);
        $this->injectClient($inbox, $client);

        $this->expectException(EmailInboxException::class);
        $this->expectExceptionMessage('INBOX');
        $inbox->fetchMessages();
    }

    public function testMarkReadThrowsWhenFolderIsMissing(): void
    {
        $client = \Mockery::mock('\Webklex\PHPIMAP\Client');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn(null);

        $inbox = new EmailInbox([
            'host' => 'localhost',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'user' => 'u',
            'pass' => 'p',
        ]);
        $this->injectClient($inbox, $client);

        $this->expectException(EmailInboxException::class);
        $this->expectExceptionMessage('INBOX');
        $inbox->markRead('42');
    }

    public function testWithoutWarningsPromotesWarningToException(): void
    {
        $inbox = new EmailInbox([
            'host' => 'localhost', 'port' => 993, 'protocol' => 'imap',
            'encryption' => 'ssl', 'user' => 'u', 'pass' => 'p',
        ]);

        $method = new \ReflectionMethod(EmailInbox::class, 'withoutWarnings');
        @$method->setAccessible(true);

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('simulated network warning');
        $method->invoke($inbox, function () {
            trigger_error('simulated network warning', E_USER_WARNING);
        });
    }

public function testWithoutWarningsReturnsResultAndRestoresHandler(): void
    {
        $inbox = new EmailInbox([
            'host' => 'localhost', 'port' => 993, 'protocol' => 'imap',
            'encryption' => 'ssl', 'user' => 'u', 'pass' => 'p',
        ]);

        $method = new \ReflectionMethod(EmailInbox::class, 'withoutWarnings');
        @$method->setAccessible(true);

        $result = $method->invoke($inbox, function () {
            return 'ok';
        });
        $this->assertSame('ok', $result);

        // A second call must still convert a warning to an exception: the
        // previous handler was restored (PHPUnit's converters), but our own
        // set_error_handler inside withoutWarnings is the active one again.
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('network warning 2');
        $method->invoke($inbox, function () {
            trigger_error('network warning 2', E_USER_WARNING);
        });
    }

    /**
     * Inject a mocked library Client into the private $client property so the
     * adapter's fetch/markRead/delete paths can be exercised offline.
     */
    private function injectClient(EmailInbox $inbox, $client): void
    {
        $ref = new \ReflectionProperty(EmailInbox::class, 'client');
        @$ref->setAccessible(true);
        $ref->setValue($inbox, $client);
    }
}