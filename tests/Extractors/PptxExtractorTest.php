<?php

namespace Tests\Extractors;

use PHPUnit\Framework\TestCase;

class PptxExtractorTest extends TestCase
{
    public function testExtractReturnsTextFromPptx(): void
    {
        $fixture = __DIR__ . '/../fixtures/hello.pptx';
        if (!file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found');
        }
        require_once __DIR__ . '/../../application/models/TextExtractor.class.php';
        require_once __DIR__ . '/../../application/models/TextExtractors/PptxExtractor.class.php';
        $extractor = new \PptxExtractor();
        $text = $extractor->extract($fixture);
        $this->assertStringContainsString('Hello World from PPTX', $text);
    }
}