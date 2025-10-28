<?php
namespace Aura\Html\Escaper;

/**
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 */
class AttrEscaperTest extends AbstractEscaperTest
{
    public function set_up()
    {
        parent::set_up();
        $this->escaper = new AttrEscaper(new HtmlEscaper);
    }

    public function test__construct()
    {
        $escaper = new AttrEscaper(new HtmlEscaper, 'iso-8859-1');
        $this->assertSame('iso-8859-1', $escaper->getEncoding());
    }

    public function test__invoke()
    {
        $chars = array(
            '\''    => '&#x27;',
            '"'     => '&quot;',
            '<'     => '&lt;',
            '>'     => '&gt;',
            '&'     => '&amp;',
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā'     => '&#x0100;',
            /* Immune chars excluded */
            ','     => ',',
            '.'     => '.',
            '-'     => '-',
            '_'     => '_',
            /* Basic alnums exluded */
            'a'     => 'a',
            'A'     => 'A',
            'z'     => 'z',
            'Z'     => 'Z',
            '0'     => '0',
            '9'     => '9',
            /* Basic control characters and null */
            "\r"    => '&#x0D;',
            "\n"    => '&#x0A;',
            "\t"    => '&#x09;',
            "\0"    => '&#xFFFD;', // should use Unicode replacement char
            /* Encode chars as named entities where possible */
            '<'     => '&lt;',
            '>'     => '&gt;',
            '&'     => '&amp;',
            '"'     => '&quot;',
            /* Encode spaces for quoteless attribute protection */
            ' '     => '&#x20;',
        );

        foreach ($chars as $key => $val) {
            $this->assertEquals(
                $val,
                $this->escaper->__invoke($key),
                'Failed to escape: ' . $key
            );
        }
    }

    public function test_ranges()
    {
        $immune = array(',', '.', '-', '_'); // Exceptions to escaping ranges
        for ($chr=0; $chr < 0xFF; $chr++) {
            if ($chr >= 0x30 && $chr <= 0x39
                || $chr >= 0x41 && $chr <= 0x5A
                || $chr >= 0x61 && $chr <= 0x7A
            ) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, $this->escaper->__invoke($literal));
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune)) {
                    $this->assertEquals($literal, $this->escaper->__invoke($literal));
                } else {
                    $this->assertNotEquals(
                        $literal,
                        $this->escaper->__invoke($literal),
                        $literal . ' should be escaped!'
                    );
                }
            }
        }
    }

    public function test_array()
    {
        $expect = 'foo="bar baz dib"';
        $actual = $this->escaper->__invoke(array(
            'foo' => array(
                'bar',
                'baz',
                'dib',
            )
        ));
        $this->assertSame($expect, $actual);
    }

}
