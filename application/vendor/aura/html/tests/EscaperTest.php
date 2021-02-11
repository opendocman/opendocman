<?php
namespace Aura\Html;

/**
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 */
class EscaperTest extends \PHPUnit_Framework_TestCase
{
    protected $escaper;

    public function setUp()
    {
        $escaper_factory = new EscaperFactory;
        $this->escaper = $escaper_factory->newInstance();
        Escaper::setStatic($this->escaper);
    }

    public function test__invoke()
    {
        $this->assertSame($this->escaper, $this->escaper->__invoke());
    }

    public function testGetStatic()
    {
        $this->assertSame($this->escaper, Escaper::getStatic());
    }

    public function testSetEncoding()
    {
        $this->escaper->setEncoding('macroman');
        $this->assertSame('macroman', $this->escaper->html->getEncoding());
        $this->assertSame('macroman', $this->escaper->attr->getEncoding());
        $this->assertSame('macroman', $this->escaper->css->getEncoding());
        $this->assertSame('macroman', $this->escaper->js->getEncoding());
    }

    public function testSetFlags()
    {
        $this->escaper->setFlags(ENT_NOQUOTES);
        $this->assertSame(ENT_NOQUOTES, $this->escaper->html->getFlags());
        $this->assertSame(ENT_NOQUOTES, $this->escaper->attr->getHtml()->getFlags());
    }

    public function test_a()
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
                Escaper::a($key),
                'Failed to escape: ' . $key
            );
        }
    }

    public function test_c()
    {
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
                Escaper::c($key),
                'Failed to escape: ' . $key
            );
        }
    }

    public function test_h()
    {
        $chars = array(
            '\''    => '&#039;',
            '"'     => '&quot;',
            '<'     => '&lt;',
            '>'     => '&gt;',
            '&'     => '&amp;'
        );

        foreach ($chars as $key => $val) {
            $this->assertEquals(
                $val,
                Escaper::h($key),
                'Failed to escape: ' . $key
            );
        }
    }

    public function test_j()
    {
        $chars = array(
            /* HTML special chars - escape without exception to hex */
            '<'     => '\\x3C',
            '>'     => '\\x3E',
            '\''    => '\\x27',
            '"'     => '\\x22',
            '&'     => '\\x26',
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā'     => '\\u0100',
            /* Immune chars excluded */
            ','     => ',',
            '.'     => '.',
            '_'     => '_',
            /* Basic alnums exluded */
            'a'     => 'a',
            'A'     => 'A',
            'z'     => 'z',
            'Z'     => 'Z',
            '0'     => '0',
            '9'     => '9',
            /* Basic control characters and null */
            "\r"    => '\\x0D',
            "\n"    => '\\x0A',
            "\t"    => '\\x09',
            "\0"    => '\\x00',
            /* Encode spaces for quoteless attribute protection */
            ' '     => '\\x20',
        );

        foreach ($chars as $key => $val) {
            $this->assertEquals(
                $val,
                Escaper::j($key),
                'Failed to escape: ' . $key
            );
        }
    }
}
