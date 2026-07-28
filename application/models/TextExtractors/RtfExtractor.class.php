<?php

if (!defined('RtfExtractor_class')) {
    define('RtfExtractor_class', 'true');

    class RtfExtractor implements TextExtractor
    {
        public function extract(string $filePath): string
        {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return '';
            }
            return $this->stripRtf($content);
        }

        private function stripRtf(string $rtf): string
        {
            $text = '';
            $len = strlen($rtf);
            $i = 0;
            $depth = 0;
            $skip = false;

            while ($i < $len) {
                $char = $rtf[$i];

                if ($char === '{') {
                    if (!$skip) {
                        $depth++;
                    }
                    $i++;
                    continue;
                }

                if ($char === '}') {
                    if (!$skip) {
                        $depth--;
                    }
                    $i++;
                    continue;
                }

                if ($char === '\\') {
                    $i++;
                    if ($i >= $len) {
                        break;
                    }
                    $next = $rtf[$i];

                    if ($next === '\'') {
                        $i += 2;
                        continue;
                    }

                    if (ctype_alpha($next)) {
                        while ($i < $len && ctype_alpha($rtf[$i])) {
                            $i++;
                        }
                        if ($i < $len && $rtf[$i] === ' ') {
                            $i++;
                        }
                        continue;
                    }

                    if ($next === '\\' || $next === '{' || $next === '}') {
                        if ($depth <= 1) {
                            $text .= $next;
                        }
                        $i++;
                        continue;
                    }

                    if ($next === '*') {
                        $skip = true;
                        $i++;
                        continue;
                    }

                    $i++;
                    continue;
                }

                if ($char === "\n" || $char === "\r") {
                    $i++;
                    continue;
                }

                if ($skip) {
                    $i++;
                    continue;
                }

                $text .= $char;
                $i++;
            }

            $text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }
    }
}