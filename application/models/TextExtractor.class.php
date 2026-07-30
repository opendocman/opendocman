<?php

if (!defined('TextExtractor_class')) {
    define('TextExtractor_class', 'true');

    interface TextExtractor
    {
        public function extract(string $filePath): string;
    }
}