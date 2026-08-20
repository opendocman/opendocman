<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/EmailMessage.class.php';
require_once APPLICATION_PATH . '/models/EmailInbox.class.php';

class EmailInboxTest extends TestCase
{
    public function testMessageDtoExposesFields(): void
    {
        $m = new EmailMessage('123', 'subject', 'sender@example.com');
        $m->attachments[] = ['name' => 'a.pdf', 'path' => '/tmp/a', 'mime' => 'application/pdf'];
        $this->assertSame('123', $m->id);
        $this->assertSame('sender@example.com', $m->from);
        $this->assertSame('a.pdf', $m->attachments[0]['name']);
        $this->assertSame('application/pdf', $m->attachments[0]['mime']);
    }
}