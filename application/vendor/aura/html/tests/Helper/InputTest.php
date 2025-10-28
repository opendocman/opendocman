<?php
namespace Aura\Html\Helper;

use Aura\Html\HelperFactory;
use Aura\Html\HelperLocator;
use Aura\Html\EscaperFactory;

class InputTest extends AbstractHelperTest
{
    protected function newHelper()
    {
        $escaper_factory = new EscaperFactory;
        $escaper = $escaper_factory->newInstance();

        return new Input(array(
            'button'            => function () use ($escaper) { return new Input\Generic($escaper); },
            'checkbox'          => function () use ($escaper) { return new Input\Checkbox($escaper); },
            'color'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'date'              => function () use ($escaper) { return new Input\Generic($escaper); },
            'datetime'          => function () use ($escaper) { return new Input\Generic($escaper); },
            'datetime-local'    => function () use ($escaper) { return new Input\Generic($escaper); },
            'email'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'file'              => function () use ($escaper) { return new Input\Generic($escaper); },
            'hidden'            => function () use ($escaper) { return new Input\Generic($escaper); },
            'image'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'month'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'number'            => function () use ($escaper) { return new Input\Generic($escaper); },
            'password'          => function () use ($escaper) { return new Input\Generic($escaper); },
            'radio'             => function () use ($escaper) { return new Input\Radio($escaper); },
            'range'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'reset'             => function () use ($escaper) { return new Input\Generic($escaper); },
            'search'            => function () use ($escaper) { return new Input\Generic($escaper); },
            'select'            => function () use ($escaper) { return new Input\Select($escaper); },
            'submit'            => function () use ($escaper) { return new Input\Generic($escaper); },
            'tel'               => function () use ($escaper) { return new Input\Generic($escaper); },
            'text'              => function () use ($escaper) { return new Input\Generic($escaper); },
            'textarea'          => function () use ($escaper) { return new Input\Textarea($escaper); },
            'time'              => function () use ($escaper) { return new Input\Generic($escaper); },
            'url'               => function () use ($escaper) { return new Input\Generic($escaper); },
            'week'              => function () use ($escaper) { return new Input\Generic($escaper); },
        ));
    }

    public function test__invoke()
    {
        $this->assertSame($this->helper, $this->helper->__invoke());
    }

    public function testCheckbox()
    {
        $spec = array(
            'type' => 'checkbox',
            'name' => 'field_name',
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
                'value' => 'foo',
                'label' => 'DOOM',
            ),
            'value' => 'foo',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();
        $expect = '<label><input type="checkbox" name="field_name" value="foo" checked /> DOOM</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testInput()
    {
        $spec = array(
            'type' => 'text',
            'name' => 'field_name',
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
            ),
            'options' => array(),
            'value' => 'foo',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();
        $expect = '<input type="text" name="field_name" value="foo" />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testNoType()
    {
        $spec = array(
            'name' => 'field_name',
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
            ),
            'options' => array(),
            'value' => 'foo',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();
        $expect = '<input type="text" name="field_name" value="foo" />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testRadio()
    {
        $spec = array(
            'type' => 'radio',
            'name' => 'field_name',
            'label' => null,
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
                'foo' => 'bar',
            ),
            'options' => array('opt1' => 'Label 1', 'opt2' => 'Label 2', 'opt3' => 'Label 3'),
            'value' => 'opt2',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();
        $expect = '<label><input type="radio" name="field_name" foo="bar" value="opt1" /> Label 1</label>' . PHP_EOL
                . '<label><input type="radio" name="field_name" foo="bar" value="opt2" checked /> Label 2</label>' . PHP_EOL
                . '<label><input type="radio" name="field_name" foo="bar" value="opt3" /> Label 3</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testSelect()
    {
        $spec = array(
            'type' => 'select',
            'name' => 'field_name',
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
                'foo' => 'bar',
            ),
            'options' => array(
                'opt1' => 'Label 1',
                'opt2' => 'Label 2',
                'opt3' => 'Label 3',
                'Group A' => array(
                    'opt4' => 'Label 4',
                    'opt5' => 'Label 5',
                    'opt6' => 'Label 6',
                ),
                'Group B' => array(
                    'opt7' => 'Label 7',
                    'opt8' => 'Label 8',
                    'opt9' => 'Label 9',
                ),
            ),
            'value' => 'opt5',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();

        $expect = '<select name="field_name" foo="bar">' . PHP_EOL
                . '    <option value="opt1">Label 1</option>' . PHP_EOL
                . '    <option value="opt2">Label 2</option>' . PHP_EOL
                . '    <option value="opt3">Label 3</option>' . PHP_EOL
                . '    <optgroup label="Group A">' . PHP_EOL
                . '        <option value="opt4">Label 4</option>' . PHP_EOL
                . '        <option value="opt5" selected>Label 5</option>' . PHP_EOL
                . '        <option value="opt6">Label 6</option>' . PHP_EOL
                . '    </optgroup>' . PHP_EOL
                . '    <optgroup label="Group B">' . PHP_EOL
                . '        <option value="opt7">Label 7</option>' . PHP_EOL
                . '        <option value="opt8">Label 8</option>' . PHP_EOL
                . '        <option value="opt9">Label 9</option>' . PHP_EOL
                . '    </optgroup>' . PHP_EOL
                . '</select>' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }

    public function testTextarea()
    {
        $spec = array(
            'type' => 'textarea',
            'name' => 'field_name',
            'label' => null,
            'attribs' => array(
                'id' => null,
                'type' => null,
                'name' => null,
                'foo' => 'bar',
            ),
            'options' => array('baz' => 'dib'),
            'value' => 'Text in the textarea.',
        );

        $input = $this->helper;
        $actual = $input($spec)->__toString();
        $expect = '<textarea name="field_name" foo="bar">Text in the textarea.</textarea>';
        $this->assertSame($expect, $actual);
    }
}
