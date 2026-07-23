<?php

namespace Tests\Extractors;

use PHPUnit\Framework\TestCase;

class PdfExtractorTest extends TestCase
{
    public function testExtractReturnsTextFromPdf(): void
    {
        $fixture = __DIR__ . '/../fixtures/hello.pdf';
        if (!file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found');
        }
        require_once __DIR__ . '/../../application/models/TextExtractor.class.php';
        require_once __DIR__ . '/../../application/models/TextExtractors/PdfExtractor.class.php';
        $extractor = new \PdfExtractor();
        $text = $extractor->extract($fixture);
        $this->assertStringContainsString('Hello World from PDF', $text);
    }
}