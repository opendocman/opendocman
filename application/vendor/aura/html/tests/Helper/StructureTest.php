<?php
namespace Aura\Html\Helper;

class StructureTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => array(
                'span',
                array('em', 'foo>bar'),
                array('class' => 'baz')
            ),
        );

        $struct = $this->helper;
        $actual = $struct($data->tag, $data->content);
        $expect = '<div><span class="baz"><em>foo&gt;bar</em></span></div>';
        $this->assertSame($expect, $actual);
    }

    public function testWithAttribs()
    {
        $data = (object) array(
            'tag' => 'div',
            'content' => array(
                'em',
                'content'
            ),
            'attribs' => array('bar' => 'baz'),
        );

        $element = $this->helper;
        $actual = $element($data->tag, $data->content, $data->attribs);
        $expect = '<div bar="baz"><em>content</em></div>';
        $this->assertSame($expect, $actual);
    }
}
