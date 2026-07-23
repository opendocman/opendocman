<?php

if (!defined('DocxExtractor_class')) {
    define('DocxExtractor_class', 'true');

    class DocxExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $text = '';
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            $content = $zip->getFromName('word/document.xml');
            if ($content !== false) {
                $text .= $this->parseXmlText($content) . "\n";
            }

            for ($i = 1; $i <= 3; $i++) {
                $content = $zip->getFromName('word/header' . $i . '.xml');
                if ($content !== false) {
                    $text .= $this->parseXmlText($content) . "\n";
                }
            }

            for ($i = 1; $i <= 3; $i++) {
                $content = $zip->getFromName('word/footer' . $i . '.xml');
                if ($content !== false) {
                    $text .= $this->parseXmlText($content) . "\n";
                }
            }

            $zip->close();
            return trim($text);
        }

        private function parseXmlText(string $xml): string
        {
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            $text = '';
            $paragraphs = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
            foreach ($paragraphs as $p) {
                $texts = $p->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
                $line = '';
                foreach ($texts as $t) {
                    $line .= $t->textContent;
                }
                $text .= trim($line) . "\n";
            }
            return $text;
        }
    }
}