<?php

if (!defined('PdfExtractor_class')) {
    define('PdfExtractor_class', 'true');

    class PdfExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return trim($pdf->getText());
            } catch (\Throwable $e) {
                error_log('PdfExtractor: ' . $e->getMessage());
                return '';
            }
        }
    }
}