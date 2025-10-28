<?php
namespace Aura\Html\Helper;

class VoidTagTest extends AbstractHelperTest
{
    public function test()
    {
        $tag = $this->helper;

        $actual = $tag('meta', array(
            'itemprop' => 'duration',
            'content' => 'T1M33S',
        ));

        $expect = '<meta itemprop="duration" content="T1M33S" />';

        $this->assertSame($expect, $actual);

        $actual = $tag('br');
        $expect = '<br  />';
        $this->assertSame($expect, $actual);
    }
}
