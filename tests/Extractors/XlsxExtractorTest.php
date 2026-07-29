<?php

namespace Tests\Extractors;

use PHPUnit\Framework\TestCase;

class XlsxExtractorTest extends TestCase
{
    public function testExtractReturnsTextFromXlsx(): void
    {
        $fixture = __DIR__ . '/../fixtures/hello.xlsx';
        if (!file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found');
        }
        require_once __DIR__ . '/../../application/models/TextExtractor.class.php';
        require_once __DIR__ . '/../../application/models/TextExtractors/XlsxExtractor.class.php';
        $extractor = new \XlsxExtractor();
        $text = $extractor->extract($fixture);
        $this->assertStringContainsString('Hello World from XLSX', $text);
    }
}