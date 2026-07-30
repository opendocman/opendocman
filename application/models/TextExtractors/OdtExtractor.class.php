<?php

if (!defined('OdtExtractor_class')) {
    define('OdtExtractor_class', 'true');

    class OdtExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            $content = $zip->getFromName('content.xml');
            $zip->close();

            if ($content === false) {
                return '';
            }

            $dom = new DOMDocument();
            $dom->loadXML($content);

            $text = '';
            $paragraphs = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'p');
            foreach ($paragraphs as $p) {
                $text .= trim($p->textContent) . "\n";
            }

            $headings = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'h');
            foreach ($headings as $h) {
                $text .= trim($h->textContent) . "\n";
            }

            return trim($text);
        }
    }
}