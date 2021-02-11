<?php
namespace Aura\Html\Helper\Input;

use Aura\Html\Helper\AbstractHelperTest;

class RadioTest extends AbstractHelperTest
{
    public function test()
    {
        $attribs = array('type' => '', 'name' => 'field', 'value' => '');

        $options = array(
            'foo' => 'bar',
            'baz' => 'dib',
            'zim' => 'gir & doom',
        );

        $radio = $this->helper;

        $actual = $radio(array(
            'name' => 'field',
            'value' => 'baz',
            'attribs' => $attribs,
            'options' => $options,
        ))->__toString();

        $expect = '<label><input type="radio" name="field" value="foo" /> bar</label>' . PHP_EOL
                . '<label><input type="radio" name="field" value="baz" checked /> dib</label>' . PHP_EOL
                . '<label><input type="radio" name="field" value="zim" /> gir &amp; doom</label>' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }
}
