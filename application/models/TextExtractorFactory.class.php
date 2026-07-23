<?php

if (!defined('TextExtractorFactory_class')) {
    define('TextExtractorFactory_class', 'true');

    class TextExtractorFactory
    {
        private static $extractorMap = [
            'application/msword' => 'DocxExtractor',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DocxExtractor',
            'application/vnd.ms-excel' => 'XlsxExtractor',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XlsxExtractor',
            'application/vnd.ms-powerpoint' => 'PptxExtractor',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PptxExtractor',
            'application/vnd.oasis.opendocument.text' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.text-template' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.text-master' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.text-web' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.spreadsheet' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.spreadsheet-template' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.presentation' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.presentation-template' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.graphics' => 'OdtExtractor',
            'application/vnd.oasis.opendocument.graphics-template' => 'OdtExtractor',
            'application/pdf' => 'PdfExtractor',
            'application/x-pdf' => 'PdfExtractor',
        ];

        public static function create(string $mimeType): ?TextExtractor
        {
            $class = self::$extractorMap[$mimeType] ?? null;
            if ($class === null) {
                return null;
            }
            $filename = __DIR__ . '/TextExtractors/' . $class . '.class.php';
            if (!file_exists($filename)) {
                return null;
            }
            require_once $filename;
            return new $class();
        }

        public static function isExtractable(string $mimeType): bool
        {
            return isset(self::$extractorMap[$mimeType]);
        }
    }
}