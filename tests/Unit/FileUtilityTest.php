<?php

use PHPUnit\Framework\TestCase;

class FileUtilityTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $origMimetypes;
    private $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Snapshot original mimetypes
        $this->origMimetypes = $GLOBALS['mimetypes'] ?? null;

        // Provide a minimal, deterministic mimetypes map for these tests
        $GLOBALS['mimetypes'] = [
            'txt'  => ['text/plain'],
            'png'  => ['image/png'],
            'html' => ['text/html'],
            'bin'  => ['application/octet-stream'],
            'cstm' => ['application/x-custom'],
        ];
    }

    protected function tearDown(): void
    {
        // Restore original mimetypes
        if ($this->origMimetypes !== null) {
            $GLOBALS['mimetypes'] = $this->origMimetypes;
        } else {
            unset($GLOBALS['mimetypes']);
        }

        // Cleanup any temp files created by this test
        foreach ($this->tmpFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testMimeByExtReturnsConfiguredValue(): void
    {
        $this->assertSame('image/png', File::mime_by_ext('png'));
        $this->assertSame('text/plain', File::mime_by_ext('txt'));
        $this->assertFalse(File::mime_by_ext('unknown_ext'));
    }

    public function testMimesByExtReturnsArray(): void
    {
        $pngMimes = File::mimes_by_ext('png');
        $this->assertIsArray($pngMimes);
        $this->assertContains('image/png', $pngMimes);

        $unknown = File::mimes_by_ext('nope');
        $this->assertIsArray($unknown);
        $this->assertCount(0, $unknown);
    }

    public function testExtsAndExtByMimeMapping(): void
    {
        // Use a custom MIME so we control the mapping for this process
        $extensions = File::exts_by_mime('application/x-custom');
        $this->assertIsArray($extensions);
        $this->assertContains('cstm', $extensions);

        $ext = File::ext_by_mime('application/x-custom');
        $this->assertSame('cstm', $ext);
    }

    public function testMimeDetectsPngViaGetImageSize(): void
    {
        // 1x1 transparent PNG
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y2CqEwAAAAASUVORK5CYII=';
        $pngBytes = base64_decode($pngBase64);

        $tmp = $this->makeTempFile('png_image_');
        file_put_contents($tmp, $pngBytes);

        $mime = File::mime($tmp, 'tiny.png');

        // Should detect via getimagesize first, which returns 'image/png'
        $this->assertSame('image/png', $mime);
    }

    public function testMimeDetectsTextViaFinfoOrFallback(): void
    {
        $tmp = $this->makeTempFile('text_');
        file_put_contents($tmp, "Hello world!\nThis is a test file.\n");

        $mime = File::mime($tmp, 'readme.txt');

        // Allow multiple branches: finfo or mime_content_type often returns text/plain or text/plain; charset=...
        // If both unavailable, it should fall back to our $GLOBALS['mimetypes'] based on extension.
        $this->assertIsString($mime);
        $this->assertStringContainsString('text/plain', $mime);
    }

    public function testSplitAndJoinRoundTrip(): void
    {
        // Create a file ~50KB in size with deterministic content
        $data = str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ\n", 1024); // ~38 bytes per line * 1024 ~ 38KB
        $data .= str_repeat("abcdef\n", 2048); // add ~14KB more

        $tmp = $this->makeTempFile('splitjoin_');
        file_put_contents($tmp, $data);

        // Split into ~1KB pieces to ensure multiple chunks
        $pieces = File::split($tmp, 0.001);
        $this->assertGreaterThan(1, $pieces, 'Expected more than one piece to be created by File::split');

        // Join back into the original filename (it will overwrite the existing file)
        $joined = File::join($tmp);
        $this->assertSame($pieces, $joined, 'Join should return the same number of pieces as split');

        $roundTrip = file_get_contents($tmp);
        $this->assertSame($data, $roundTrip, 'Joined file content must equal original content');

        // Cleanup piece files
        for ($i = 1; $i <= $pieces; $i++) {
            $piece = sprintf('%s.%s', $tmp, str_pad((string)$i, 3, '0', STR_PAD_LEFT));
            if (is_file($piece)) {
                @unlink($piece);
            }
        }
    }

    private function makeTempFile(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        $this->tmpFiles[] = $path;
        return $path;
    }
}
