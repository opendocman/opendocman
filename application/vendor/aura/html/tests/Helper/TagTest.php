<?php
namespace Aura\Html\Helper;

class TagTest extends AbstractHelperTest
{
    public function test()
    {
        $tag = $this->helper;

        $actual = $tag('form', array(
            'action' => '/action.php',
            'method' => 'post',
        ));

        $expect = '<form action="/action.php" method="post">';

        $this->assertSame($expect, $actual);

        $actual = $tag('div');
        $expect = '<div>';
        $this->assertSame($expect, $actual);
    }
}
