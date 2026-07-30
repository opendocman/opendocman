<?php

namespace Tests\Extractors;

use PHPUnit\Framework\TestCase;

class OdtExtractorTest extends TestCase
{
    public function testExtractReturnsTextFromOdt(): void
    {
        $fixture = __DIR__ . '/../fixtures/hello.odt';
        if (!file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found');
        }
        require_once __DIR__ . '/../../application/models/TextExtractor.class.php';
        require_once __DIR__ . '/../../application/models/TextExtractors/OdtExtractor.class.php';
        $extractor = new \OdtExtractor();
        $text = $extractor->extract($fixture);
        $this->assertStringContainsString('Hello World from ODT', $text);
    }
}