<?php

namespace Tests\Extractors;

use PHPUnit\Framework\TestCase;

class DocxExtractorTest extends TestCase
{
    public function testExtractReturnsTextFromDocx(): void
    {
        $fixture = __DIR__ . '/../fixtures/hello.docx';
        if (!file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found');
        }
        require_once __DIR__ . '/../../application/models/TextExtractor.class.php';
        require_once __DIR__ . '/../../application/models/TextExtractors/DocxExtractor.class.php';
        $extractor = new \DocxExtractor();
        $text = $extractor->extract($fixture);
        $this->assertStringContainsString('Hello World from DOCX', $text);
    }
}