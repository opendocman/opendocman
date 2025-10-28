<?php
namespace Aura\Html\Helper;

class ElementRawTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => '<span>content</span>',
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content);
        $expect = '<div><span>content</span></div>';
        $this->assertSame($expect, $actual);
    }

    public function testWithAttribs()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => '<span>content</span>',
            'attribs' => array('bar' => 'baz'),
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content, $data->attribs);
        $expect = '<div bar="baz"><span>content</span></div>';
        $this->assertSame($expect, $actual);
    }
}
