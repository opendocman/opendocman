<?php

if (!defined('XlsxExtractor_class')) {
    define('XlsxExtractor_class', 'true');

    class XlsxExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml !== false) {
                $dom = new DOMDocument();
                $dom->loadXML($ssXml);
                $items = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
                foreach ($items as $item) {
                    $sharedStrings[] = $item->textContent;
                }
            }

            $text = '';
            $worksheetIndex = 1;
            while (($wsXml = $zip->getFromName('xl/worksheets/sheet' . $worksheetIndex . '.xml')) !== false) {
                $dom = new DOMDocument();
                $dom->loadXML($wsXml);
                $rows = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
                foreach ($rows as $row) {
                    $cells = $row->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
                    $rowText = [];
                    foreach ($cells as $cell) {
                        $value = '';
                        $v = $cell->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v');
                        $isStr = $cell->getAttribute('t') === 's';
                        if ($v->length > 0) {
                            $val = $v->item(0)->textContent;
                            if ($isStr) {
                                $idx = (int) $val;
                                $value = $sharedStrings[$idx] ?? '';
                            } else {
                                $value = $val;
                            }
                        }
                        if ($value !== '') {
                            $rowText[] = trim($value);
                        }
                    }
                    if (!empty($rowText)) {
                        $text .= implode(' ', $rowText) . "\n";
                    }
                }
                $worksheetIndex++;
            }

            $zip->close();
            return trim($text);
        }
    }
}