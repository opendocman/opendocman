<?php
namespace Aura\Html\Helper;

class AnchorRawTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $data = (object) array(
            'href' => '/path/to/script.php',
            'text' => '<span>this</span>',
        );

        $anchor = $this->helper;
        $actual = $anchor($data->href, $data->text);
        $expect = '<a href="/path/to/script.php"><span>this</span></a>';
        $this->assertSame($expect, $actual);
    }

    public function testWithAttribs()
    {
        $data = (object) array(
            'href' => '/path/to/script.php',
            'text' => '<span>foo</span>',
            'attribs' => array('bar' => 'baz', 'href' => 'skip-me'),
        );

        $anchor = $this->helper;
        $actual = $anchor($data->href, $data->text, $data->attribs);
        $expect = '<a href="/path/to/script.php" bar="baz"><span>foo</span></a>';
        $this->assertSame($expect, $actual);
    }
}
