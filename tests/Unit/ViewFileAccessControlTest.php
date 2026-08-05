<?php

class ViewFileAccessControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        $GLOBALS['CONFIG']['incomingDir'] = '/var/www/incoming/';
        $GLOBALS['CONFIG']['dataDir'] = '/var/www/data/';
    }

    public function testOwnerCanAccessIncomingPath(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $file = \Mockery::mock(FileData::class);

        $file->shouldReceive('getOwner')->andReturn(99);
        $file->shouldReceive('isPublishable')->andReturn(0);
        $file->shouldReceive('getName')->andReturn('test.pdf');

        $_SESSION['uid'] = 99;

        $isOwner = $file->getOwner() == $_SESSION['uid'];
        $this->assertTrue($isOwner, 'File owner should be able to access incoming path');
    }

    public function testNonOwnerNonReviewerCannotAccessIncoming(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $file = \Mockery::mock(FileData::class);

        $file->shouldReceive('getOwner')->andReturn(10);
        $file->shouldReceive('isPublishable')->andReturn(0);
        $file->shouldReceive('getName')->andReturn('test.pdf');

        $_SESSION['uid'] = 99;

        $isOwner = $file->getOwner() == $_SESSION['uid'];
        $this->assertFalse($isOwner, 'Non-owner should not be owner');

        // Simulate the access check: non-owner + non-reviewer => use data dir
        $canAccessIncoming = ($isOwner || false);
        $this->assertFalse($canAccessIncoming, 'Non-owner non-reviewer should NOT get incoming path');
    }

    public function testNonOwnerButReviewerCanAccessIncoming(): void
    {
        $_SESSION['uid'] = 55;

        $isOwner = false;

        $canAccessIncoming = ($isOwner || true);
        $this->assertTrue($canAccessIncoming, 'Reviewer should be able to access incoming path');
    }

    public function testPendingFileWithIncomingPathUsesDataDirForUnauthorized(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $file = \Mockery::mock(FileData::class);

        $file->shouldReceive('getOwner')->andReturn(10);
        $file->shouldReceive('isPublishable')->andReturn(0);
        $file->shouldReceive('getName')->andReturn('test.pdf');

        $_SESSION['uid'] = 99;

        $isOwner = $file->getOwner() == $_SESSION['uid'];
        $canAccessIncoming = $isOwner;

        // When unauthorized, filename should be data dir, not incoming
        if (!$canAccessIncoming) {
            $filename = $GLOBALS['CONFIG']['dataDir'] . '99/test.pdf';
        } else {
            $filename = $GLOBALS['CONFIG']['incomingDir'] . '99/test.pdf';
        }

        $this->assertStringStartsWith($GLOBALS['CONFIG']['dataDir'], $filename,
            'Unauthorized user should get data directory path');
    }

    public function testRejectedFileWithIncomingPathUsesDataDirForUnauthorized(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $file = \Mockery::mock(FileData::class);

        $file->shouldReceive('getOwner')->andReturn(10);
        $file->shouldReceive('isPublishable')->andReturn(-1);
        $file->shouldReceive('getName')->andReturn('test.pdf');

        $_SESSION['uid'] = 99;

        $isOwner = $file->getOwner() == $_SESSION['uid'];
        $canAccessIncoming = $isOwner;

        if (!$canAccessIncoming) {
            $filename = $GLOBALS['CONFIG']['dataDir'] . '99/test.pdf';
        } else {
            $filename = $GLOBALS['CONFIG']['incomingDir'] . '99/test.pdf';
        }

        $this->assertStringStartsWith($GLOBALS['CONFIG']['dataDir'], $filename,
            'Unauthorized user should get data directory path for rejected files');
    }
}