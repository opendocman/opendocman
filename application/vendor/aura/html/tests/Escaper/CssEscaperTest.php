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
class CssEscaperTest extends AbstractEscaperTest
{
    public function set_up()
    {
        parent::set_up();
        $this->escaper = new CssEscaper;
    }

    public function test__construct()
    {
        $escaper = new CssEscaper('iso-8859-1');
        $this->assertSame('iso-8859-1', $escaper->getEncoding());
    }

    public function test__invoke()
    {
        $this->assertEquals('', $this->escaper->__invoke(''));
        $this->assertEquals('123', $this->escaper->__invoke('123'));

        $chars = array(
            /* HTML special chars - escape without exception to hex */
            '<'     => '\\3C ',
            '>'     => '\\3E ',
            '\''    => '\\27 ',
            '"'     => '\\22 ',
            '&'     => '\\26 ',
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā'     => '\\100 ',
            /* Immune chars excluded */
            ','     => '\\2C ',
            '.'     => '\\2E ',
            '_'     => '\\5F ',
            /* Basic alnums exluded */
            'a'     => 'a',
            'A'     => 'A',
            'z'     => 'z',
            'Z'     => 'Z',
            '0'     => '0',
            '9'     => '9',
            /* Basic control characters and null */
            "\r"    => '\\D ',
            "\n"    => '\\A ',
            "\t"    => '\\9 ',
            "\0"    => '\\0 ',
            /* Encode spaces for quoteless attribute protection */
            ' '     => '\\20 ',
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
        $immune = array(); // CSS has no exceptions to escaping ranges
        for ($chr=0; $chr < 0xFF; $chr++) {
            if ($chr >= 0x30 && $chr <= 0x39
                || $chr >= 0x41 && $chr <= 0x5A
                || $chr >= 0x61 && $chr <= 0x7A
            ) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, $this->escaper->__invoke($literal));
            } else {
                $literal = $this->codepointToUtf8($chr);
                $this->assertNotEquals(
                    $literal,
                    $this->escaper->__invoke($literal),
                    $literal . ' should be escaped!'
                );
            }
        }
    }

}
