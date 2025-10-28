<?php
namespace Aura\Html\Helper;

class StructureRawTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => array(
                'em',
                '<span>content</span>'
            ),
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content);
        $expect = '<div><em><span>content</span></em></div>';
        $this->assertSame($expect, $actual);
    }

    public function testWithAttribs()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => array(
                'em',
                '<span>content</span>'
            ),
            'attribs' => array('bar' => 'baz'),
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content, $data->attribs);
        $expect = '<div bar="baz"><em><span>content</span></em></div>';
        $this->assertSame($expect, $actual);
    }
}
