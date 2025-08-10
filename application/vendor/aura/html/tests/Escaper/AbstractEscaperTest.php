<?php
namespace Aura\Html\Escaper;

use Aura\Html\FakePhp;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// trick PHP into using this function instead of the native function
function function_exists($name)
{
    return FakePhp::function_exists($name);
}

/**
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 */
abstract class AbstractEscaperTest extends TestCase
{
    protected $escaper;

    abstract public function test__construct();

    protected function set_up()
    {
        FakePhp::$function_exists['iconv'] = \function_exists('iconv');
        FakePhp::$function_exists['mb_convert_encoding'] = \function_exists('mb_convert_encoding');
    }

    public function testMissingIconvExtension()
    {
        FakePhp::$function_exists['iconv'] = false;
        FakePhp::$function_exists['mb_convert_encoding'] = true;
        $this->escaper->setEncoding('iso-8859-1');
        $actual = $this->escaper->toUtf8('x');
        $this->assertSame('x', $actual);
    }

    public function testMissingMbstringExtension()
    {
        FakePhp::$function_exists['iconv'] = true;
        FakePhp::$function_exists['mb_convert_encoding'] = false;
        $this->escaper->setEncoding('iso-8859-1');
        $actual = $this->escaper->toUtf8('x');
        $this->assertSame('x', $actual);
    }

    public function testMissingBothExtensions()
    {
        FakePhp::$function_exists['iconv'] = false;
        FakePhp::$function_exists['mb_convert_encoding'] = false;
        $this->escaper->setEncoding('iso-8859-1');
        $this->expectException('Aura\Html\Exception\ExtensionNotInstalled');
        $this->escaper->toUtf8('x');
    }

    public function testSetAndGetEncoding()
    {
        $this->escaper->setEncoding('macroman');
        $this->assertEquals('macroman', $this->escaper->getEncoding());

        $this->expectException('Aura\Html\Exception\EncodingNotSupported');
        $this->escaper->setEncoding('invalid-encoding');
    }

    public function testToUtf8()
    {
        $this->escaper->setEncoding('iso-8859-1');
        $this->assertSame('', $this->escaper->toUtf8(''));
        $this->assertSame('foo', $this->escaper->toUtf8('foo'));
    }

    public function testToUtf8_invalid()
    {
        // http://stackoverflow.com/questions/11709410/regex-to-detect-invalid-utf-8-string
        $this->expectException('Aura\Html\Exception\InvalidUtf8');
        $this->escaper->toUtf8(chr(0xC0) . chr(0x80));
    }

    public function testFromUtf8()
    {
        $this->escaper->setEncoding('iso-8859-1');
        $this->assertSame('', $this->escaper->fromUtf8(''));
        $this->assertSame('foo', $this->escaper->fromUtf8('foo'));
    }

    /**
     * Only testing the first few 2 ranges on this prot. function as that's all these
     * other range tests require
     */
    public function testUnicodeCodepointConversionToUtf8()
    {
        $expected = " ~ޙ";
        $codepoints = array(0x20, 0x7e, 0x799);
        $result = '';
        foreach ($codepoints as $value) {
            $result .= $this->codepointToUtf8($value);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     *
     * Convert a Unicode Codepoint to a literal UTF-8 character.
     *
     * @param int Unicode codepoint in hex notation
     *
     * @return string UTF-8 literal string
     *
     */
    protected function codepointToUtf8($codepoint)
    {
        if ($codepoint < 0x80) {
            return chr($codepoint);
        }
        if ($codepoint < 0x800) {
            return chr($codepoint >> 6 & 0x3f | 0xc0)
                 . chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x10000) {
            return chr($codepoint >> 12 & 0x0f | 0xe0)
                 . chr($codepoint >> 6 & 0x3f | 0x80)
                 . chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x110000) {
            return chr($codepoint >> 18 & 0x07 | 0xf0)
                 . chr($codepoint >> 12 & 0x3f | 0x80)
                 . chr($codepoint >> 6 & 0x3f | 0x80)
                 . chr($codepoint & 0x3f | 0x80);
        }
        throw new \Exception('Codepoint requested outside of Unicode range');
    }

}
