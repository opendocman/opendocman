<?php
namespace Aura\Html\Helper;

class UlTest extends AbstractHelperTest
{
    public function testEscaped()
    {
        $ul = $this->helper;

        $actual = $ul(array('id' => 'test'))
                ->items(array(
                    '>foo',
                    '>bar',
                    '>baz',
                    '>dib' => array('class' => 'callout')
                ))
                ->__toString();

        $expect = '<ul id="test">' . PHP_EOL
                . '    <li>&gt;foo</li>' . PHP_EOL
                . '    <li>&gt;bar</li>' . PHP_EOL
                . '    <li>&gt;baz</li>' . PHP_EOL
                . '    <li class="callout">&gt;dib</li>' . PHP_EOL
                . '</ul>' . PHP_EOL;

        $this->assertSame($expect, $actual);

        $actual = $ul()->__toString();
        $expect = '';
        $this->assertSame($expect, $actual);
    }

    public function testRaw()
    {
        $ul = $this->helper;

        $actual = $ul()
                ->rawItems(array(
                    '>foo',
                    '>bar',
                    '>baz',
                    '>dib' => array('class' => 'callout')
                ))
                ->__toString();

        $expect = '<ul>' . PHP_EOL
                . '    <li>>foo</li>' . PHP_EOL
                . '    <li>>bar</li>' . PHP_EOL
                . '    <li>>baz</li>' . PHP_EOL
                . '    <li class="callout">>dib</li>' . PHP_EOL
                . '</ul>' . PHP_EOL;

        $this->assertSame($expect, $actual);

        $actual = $ul()->__toString();
        $expect = '';
        $this->assertSame($expect, $actual);
    }
}
