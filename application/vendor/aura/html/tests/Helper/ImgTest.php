<?php
namespace Aura\Html\Helper;

class ImgTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $img = $this->helper;
        $src = '/images/example.gif';
        $actual = $img($src);
        $expect = '<img src="/images/example.gif" alt="example.gif" />';
        $this->assertSame($actual, $expect);
    }
}
