<?php
namespace Aura\Html\Helper;

class LabelTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $label = $this->helper;
        $actual = $label('Foo')->__toString();
        $expect = '<label>Foo</label>';
        $this->assertSame($actual, $expect);
    }

    public function testWithAttr()
    {
        $label = $this->helper;
        $attr = array(
            'for'=>'bar',
            'class'=>'dim'
        );
        $actual = $label('Foo', $attr)->__toString();
        $expect = '<label for="bar" class="dim">Foo</label>';
        $this->assertSame($actual, $expect);
    }

    public function testBefore()
    {
        $label = $this->helper;
        $attr = array(
            'for'=>'bar',
        );
        $input = '<input type="text" name="foo" id="bar" />';
        $actual = $label('Foo: ', $attr)->before($input)->__toString();
        $expect = '<label for="bar">Foo: '
                . '<input type="text" name="foo" id="bar" />'
                . '</label>';
        $this->assertSame($actual, $expect);
    }

    public function testAfter()
    {
        $label = $this->helper;
        $attr = array(
            'for'=>'bar',
        );
        $input = '<input type="text" name="foo" id="bar" />';
        $actual = $label(' (foo)', $attr)->after($input)->__toString();
        $expect = '<label for="bar">'
                . '<input type="text" name="foo" id="bar" />'
                . ' (foo)</label>';
        $this->assertSame($actual, $expect);
    }
}
