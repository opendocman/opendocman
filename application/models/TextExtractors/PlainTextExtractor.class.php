<?php

if (!defined('PlainTextExtractor_class')) {
    define('PlainTextExtractor_class', 'true');

    class PlainTextExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return '';
            }
            $text = fread($handle, 52428800);
            fclose($handle);
            if ($text === false) {
                return '';
            }
            return trim($text);
        }
    }
}