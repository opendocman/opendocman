<?php
namespace Aura\Html\Helper;

class ElementTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => 'content',
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content);
        $expect = '<div>content</div>';
        $this->assertSame($expect, $actual);
    }

    public function testWithAttribs()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => 'content',
            'attribs' => array('bar' => 'baz'),
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content, $data->attribs);
        $expect = '<div bar="baz">content</div>';
        $this->assertSame($expect, $actual);
    }
}
