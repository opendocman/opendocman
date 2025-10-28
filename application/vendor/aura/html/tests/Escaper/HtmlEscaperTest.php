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
class HtmlEscaperTest extends AbstractEscaperTest
{
    public function set_up()
    {
        parent::set_up();
        $this->escaper = new HtmlEscaper;
    }

    public function test__construct()
    {
        $escaper = new HtmlEscaper(ENT_NOQUOTES, 'iso-8859-1');
        $this->assertSame('iso-8859-1', $escaper->getEncoding());
        $this->assertSame(ENT_NOQUOTES, $escaper->getFlags());
    }

    public function testSetAndGetFlags()
    {
        $this->escaper->setFlags(ENT_NOQUOTES);
        $this->assertSame(ENT_NOQUOTES, $this->escaper->getFlags());
    }

    public function test__invoke()
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
                $this->escaper->__invoke($key),
                'Failed to escape: ' . $key
            );
        }
    }
}
