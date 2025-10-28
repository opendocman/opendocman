<?php
namespace Aura\Html\Helper\Input;

use Aura\Html\Helper\AbstractHelperTest;

class SelectTest extends AbstractHelperTest
{
    public function testAutomatic()
    {
        $select = $this->helper;

        $actual = $select(array(
            'name' => 'foo[bar]',
            'value' => 'value5',
            'attribs' => array(
                'placeholder' => 'Please pick one',
                'strict' => true,
            ),
            'options' => array(
                'value1' => 'First Label',
                'value2' => 'Second Label',
                'value5' => 'Fifth Label',
                'value3' => 'Th&rd Label',
            ),
        ))->__toString();

        $expect = '<select name="foo[bar]">' . PHP_EOL
                . '    <option disabled value="">Please pick one</option>' . PHP_EOL
                . '    <option value="value1">First Label</option>' . PHP_EOL
                . '    <option value="value2">Second Label</option>' . PHP_EOL
                . '    <option value="value5" selected>Fifth Label</option>' . PHP_EOL
                . '    <option value="value3">Th&amp;rd Label</option>' . PHP_EOL
                . '</select>' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }

    public function testManual()
    {
        $select = $this->helper;

        $actual = $select()
            ->attribs(array(
                'name' => 'foo[bar]',
                'multiple' => 'multiple',
            ))
            ->optgroup('Group A')
            ->options(array(
               'value1' => 'First Label',
               'value2' => 'Second Label',
            ))
            ->optgroup('Group B')
            ->options(array(
               'value5' => 'Fifth Label',
               'value3' => 'Th&rd Label',
            ))
            ->option(
               'counting',
               'Three sir!',
               array('disabled' => true)
            )
            ->selected(array('value2', 'value3'))
            ->__toString();

        $expect = '<select name="foo[bar][]" multiple="multiple">' . PHP_EOL
                . '    <optgroup label="Group A">' . PHP_EOL
                . '        <option value="value1">First Label</option>' . PHP_EOL
                . '        <option value="value2" selected>Second Label</option>' . PHP_EOL
                . '    </optgroup>' . PHP_EOL
                . '    <optgroup label="Group B">' . PHP_EOL
                . '        <option value="value5">Fifth Label</option>' . PHP_EOL
                . '        <option value="value3" selected>Th&amp;rd Label</option>' . PHP_EOL
                . '        <option disabled value="counting">Three sir!</option>' . PHP_EOL
                . '    </optgroup>' . PHP_EOL
                . '</select>' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }
}
