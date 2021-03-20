<?php
namespace Aura\Html\Helper;

class OlTest extends AbstractHelperTest
{
    public function testEscaped()
    {
        $ol = $this->helper;

        $actual = $ol(array('id' => 'test'))
                ->items(array(
                    '>foo',
                    '>bar',
                    '>baz',
                    '>dib' => array('class' => 'callout')
                ))
                ->__toString();

        $expect = '<ol id="test">' . PHP_EOL
                . '    <li>&gt;foo</li>' . PHP_EOL
                . '    <li>&gt;bar</li>' . PHP_EOL
                . '    <li>&gt;baz</li>' . PHP_EOL
                . '    <li class="callout">&gt;dib</li>' . PHP_EOL
                . '</ol>' . PHP_EOL;

        $this->assertSame($expect, $actual);

        $actual = $ol()->__toString();
        $expect = '';
        $this->assertSame($expect, $actual);
    }

    public function testRaw()
    {
        $ol = $this->helper;

        $actual = $ol()
                ->rawItems(array(
                    '>foo',
                    '>bar',
                    '>baz',
                    '>dib' => array('class' => 'callout')
                ))
                ->__toString();

        $expect = '<ol>' . PHP_EOL
                . '    <li>>foo</li>' . PHP_EOL
                . '    <li>>bar</li>' . PHP_EOL
                . '    <li>>baz</li>' . PHP_EOL
                . '    <li class="callout">>dib</li>' . PHP_EOL
                . '</ol>' . PHP_EOL;

        $this->assertSame($expect, $actual);

        $actual = $ol()->__toString();
        $expect = '';
        $this->assertSame($expect, $actual);
    }
}
