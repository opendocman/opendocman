<?php

use PHPUnit\Framework\TestCase;

class FileUtilityTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
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
        $this->assertFalse(File::mime_by_ext('unknown_ext_xyz'));
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
        $extensions = File::exts_by_mime('image/png');
        $this->assertIsArray($extensions);
        $this->assertContains('png', $extensions);

        $ext = File::ext_by_mime('image/png');
        $this->assertSame('png', $ext);
    }

    public function testExtsByMimeReturnsFalseForUnknown(): void
    {
        $this->assertFalse(File::exts_by_mime('application/x-this-does-not-exist'));
    }

    public function testMimeDetectsPngViaGetImageSize(): void
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y2CqEwAAAAASUVORK5CYII=';
        $pngBytes = base64_decode($pngBase64);

        $tmp = $this->makeTempFile('png_image_');
        file_put_contents($tmp, $pngBytes);

        $mime = File::mime($tmp, 'tiny.png');

        $this->assertSame('image/png', $mime);
    }

    public function testMimeDetectsTextViaFinfoOrFallback(): void
    {
        $tmp = $this->makeTempFile('text_');
        file_put_contents($tmp, "Hello world!\nThis is a test file.\n");

        $mime = File::mime($tmp, 'readme.txt');

        $this->assertIsString($mime);
        $this->assertStringContainsString('text/plain', $mime);
    }

    private function makeTempFile(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        $this->tmpFiles[] = $path;
        return $path;
    }
}
