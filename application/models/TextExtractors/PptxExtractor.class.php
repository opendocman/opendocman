<?php

if (!defined('PptxExtractor_class')) {
    define('PptxExtractor_class', 'true');

    class PptxExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            $text = '';
            $slideIndex = 1;
            while (($slideXml = $zip->getFromName('ppt/slides/slide' . $slideIndex . '.xml')) !== false) {
                $dom = new DOMDocument();
                $dom->loadXML($slideXml);
                $texts = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 't');
                $slideText = [];
                foreach ($texts as $t) {
                    $slideText[] = trim($t->textContent);
                }
                if (!empty($slideText)) {
                    $text .= implode(' ', $slideText) . "\n\n";
                }
                $slideIndex++;
            }

            $zip->close();
            return trim($text);
        }
    }
}