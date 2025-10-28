<?php
declare(strict_types=1);

namespace OpenDocMan\Tests\Feature;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Template scan to ensure every POST form in Smarty templates includes {$csrf_token_field}.
 *
 * Rationale:
 * - All state-changing POST requests must carry a CSRF token field rendered by {$csrf_token_field}.
 * - This test parses each .tpl in application/views, finds each <form ... method="post"> block,
 *   and asserts that {$csrf_token_field} appears before the closing </form>.
 *
 * Notes:
 * - Case-insensitive matches for <form ... method=post ...>
 * - Multiple forms per template are checked independently.
 * - If a template intentionally contains a POST form that should not include a CSRF token,
 *   add its file path to the ALLOWLIST below (prefer to avoid unless absolutely necessary).
 */
final class CsrfTemplatesTest extends TestCase
{
    /**
     * If any POST forms are intentionally allowed without CSRF (discouraged), list them here.
     * Paths should be relative to project root.
     *
     * @var array<string, true>
     */
    private const ALLOWLIST = [
        // Example:
        // 'application/views/common/example.tpl' => true,
    ];

    public function testAllPostFormsIncludeCsrfTokenField(): void
    {
        $root = \dirname(__DIR__, 2);
        $viewsDir = $root . '/application/views';

        $this->assertDirectoryExists($viewsDir, 'Views directory not found: ' . $viewsDir);

        $tplFiles = $this->collectTemplateFiles($viewsDir);

        $failures = [];
        foreach ($tplFiles as $file) {
            $path = $file->getPathname();
            $relPath = $this->toRelativePath($root, $path);

            // Skip allowlisted files completely
            if (isset(self::ALLOWLIST[$relPath])) {
                continue;
            }

            $content = \file_get_contents($path);
            if ($content === false) {
                $failures[] = sprintf('%s: unable to read file contents', $relPath);
                continue;
            }

            $missing = $this->findPostFormsMissingCsrf($content);
            foreach ($missing as $miss) {
                $failures[] = sprintf(
                    '%s:%d - POST <form> missing {$csrf_token_field}%s',
                    $relPath,
                    $miss['line'],
                    $miss['snippet'] !== '' ? ' (snippet: ' . $miss['snippet'] . ')' : ''
                );
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Some POST forms are missing {\$csrf_token_field}:\n" . implode("\n", $failures)
        );
    }

    /**
     * Recursively collect .tpl files under a directory.
     *
     * @param string $dir
     * @return SplFileInfo[]
     */
    private function collectTemplateFiles(string $dir): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        $regex = new RegexIterator($it, '/^.+\.tpl$/i', \RecursiveRegexIterator::GET_MATCH);

        $files = [];
        foreach ($regex as $matches) {
            $files[] = new SplFileInfo($matches[0]);
        }
        return $files;
    }

    /**
     * Find all POST form blocks that are missing {$csrf_token_field}.
     *
     * Strategy:
     * - Regex-match each form start with method=post (case-insensitive).
     * - For each match, locate the nearest subsequent </form> tag.
     * - Search the substring for '{$csrf_token_field}'.
     *
     * @param string $content
     * @return array<int, array{line:int, snippet:string}>
     */
    private function findPostFormsMissingCsrf(string $content): array
    {
        $missing = [];

        // Normalize content to help with line calculations
        $normalized = str_replace("\r\n", "\n", $content);

        // Find matches for <form ... method="post" ...>
        $pattern = '/<form\b[^>]*\bmethod\s*=\s*([\'"]?)post\1[^>]*>/i';
        if (!\preg_match_all($pattern, $normalized, $matches, \PREG_OFFSET_CAPTURE)) {
            return $missing;
        }

        foreach ($matches[0] as $match) {
            [$formStartTag, $startPos] = $match;

            // Find the closing </form> after this start tag
            $endPos = \stripos($normalized, '</form', $startPos);
            if ($endPos === false) {
                // No explicit closing tag found. Scan a window after the start tag for the token field.
                $scanEnd = \min(\strlen($normalized), $startPos + 2000);
                $block = \substr($normalized, $startPos, $scanEnd - $startPos);
            } else {
                // Capture the block between start and closing (exclusive of the closing tag)
                $block = \substr($normalized, $startPos, $endPos - $startPos);
            }
            if ($block === false) {
                $block = '';
            }

            if (\stripos($block, '{$csrf_token_field}') === false) {
                $line = $this->lineFromOffset($normalized, $startPos);
                $missing[] = [
                    'line' => $line,
                    'snippet' => $this->makeSnippet($normalized, $startPos),
                ];
            }
        }

        return $missing;
    }

    /**
     * Convert a byte offset into a 1-based line number.
     *
     * @param string $text
     * @param int $offset
     * @return int
     */
    private function lineFromOffset(string $text, int $offset): int
    {
        $prefix = \substr($text, 0, $offset);
        if ($prefix === false) {
            return 1;
        }
        return \substr_count($prefix, "\n") + 1;
    }

    /**
     * Provide a short one-line snippet for diagnostics around an offset.
     *
     * @param string $text
     * @param int $offset
     * @param int $radius
     * @return string
     */
    private function makeSnippet(string $text, int $offset, int $radius = 80): string
    {
        $start = max(0, $offset - $radius);
        $len = $radius * 2;
        $snippet = \substr($text, $start, $len);
        if ($snippet === false) {
            return '';
        }
        $snippet = \preg_replace('/\s+/', ' ', $snippet) ?? '';
        return \trim($snippet);
    }

    /**
     * Convert absolute path to a path relative to the project root for readable messages.
     *
     * @param string $root
     * @param string $absolute
     * @return string
     */
    private function toRelativePath(string $root, string $absolute): string
    {
        $root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        if (strpos($absolute, $root) === 0) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, strlen($root)));
        }
        return str_replace(DIRECTORY_SEPARATOR, '/', $absolute);
    }
}
