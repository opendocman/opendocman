<?php
require_once __DIR__ . '/../bootstrap.php';

class FilePathHelperTest extends TestCase
{
    public function testSanitizeStripsPathTraversal()
    {
        $this->assertEquals('file.txt', sanitizeFilename('../file.txt'));
        $this->assertEquals('file.txt', sanitizeFilename('..\\file.txt'));
    }

    public function testSanitizeStripsNullBytes()
    {
        $this->assertEquals('file.txt', sanitizeFilename("file\x00.txt"));
    }

    public function testSanitizeStripsAllSlashes()
    {
        $this->assertEquals('etcpasswd', sanitizeFilename('/etc/passwd'));
        $this->assertEquals('etcpasswd', sanitizeFilename('\\etc\\passwd'));
    }

    public function testSanitizeFallsBackOnEmpty()
    {
        $this->assertEquals('untitled', sanitizeFilename('.'));
        $this->assertEquals('untitled', sanitizeFilename(''));
    }

    public function testSanitizePreservesSpacesAndSpecialChars()
    {
        $this->assertEquals('my report (v2).docx', sanitizeFilename('my report (v2).docx'));
    }
}
