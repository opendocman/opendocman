<?php
namespace Aura\Html\Helper\Input;

use Aura\Html\Helper\AbstractHelperTest;

class CheckboxTest extends AbstractHelperTest
{
    public function testChecked()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'yes',
            'attribs' => array(
                'value' => 'yes',
                'label' => 'This & yes',
            )
        ))->__toString();
        $expect = '<label><input type="checkbox" value="yes" checked /> This &amp; yes</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testUnchecked()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'no',
            'attribs' => array(
                'value' => 'yes',
                'label' => 'This & yes',
            )
        ))->__toString();
        $expect = '<label><input type="checkbox" value="yes" /> This &amp; yes</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testCheckedWithUncheckedValue()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'name'=>'foo',
            'value' => 'yes',
            'attribs' => array(
                'value' => 'yes',
                'value_unchecked' => 'no',
                'label' => 'This & yes',
            ),
        ))->__toString();
        $expect = '<input type="hidden" value="no" name="foo" /><label><input type="checkbox" name="foo" value="yes" checked /> This &amp; yes</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testUncheckedWithUncheckedValue()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'no',
            'name'=>'foo',
            'attribs' => array(
                'label' => 'This & yes',
                'value' => 'yes',
                'value_unchecked' => 'no',
            ),
        ))->__toString();
        $expect = '<input type="hidden" value="no" name="foo" /><label><input type="checkbox" name="foo" value="yes" /> This &amp; yes</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testNoLabel()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'no',
            'attribs' => array(
                'value' => 'yes',
            ),
        ))->__toString();
        $expect = '<input type="checkbox" value="yes" />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testLabelWithFor()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'no',
            'attribs' => array(
                'id' => 'input-yes',
                'value' => 'yes',
                'label' => 'This & yes'
            )
        ))->__toString();

        $expect = '<label for="input-yes"><input id="input-yes" type="checkbox" value="yes" /> This &amp; yes</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testNoValueAttrib()
    {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => 'no',
        ))->__toString();
        $expect = '<input type="checkbox" />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testStrict()
    {
        $checkbox = $this->helper;
        $checkbox->strict();

        $actual = $checkbox(array(
            'value' => 1, // INTEGER
            'attribs' => array(
                'value' => '1',
            )
        ))->__toString();
        $expect = '<input type="checkbox" value="1" />' . PHP_EOL;
        $this->assertSame($expect, $actual);

        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'value' => '1', // STRING
            'attribs' => array(
                'value' => '1',
            )
        ))->__toString();
        $expect = '<input type="checkbox" value="1" checked />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testMultiCheckbox() {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'name' => 'foo',
            'value' => 'yes',
            'options' => array(
                'yes' => 'Yes',
                'no' => 'No',
                'maybe' => 'Maybe'
            ),
            'attribs' => array(
                'value' => 'yes',
                'label' => 'Is ignored',
            )
        ))->__toString();
        $expect = '<label><input type="checkbox" name="foo[]" value="yes" checked /> Yes</label>' . PHP_EOL
                . '<label><input type="checkbox" name="foo[]" value="no" /> No</label>' . PHP_EOL
                . '<label><input type="checkbox" name="foo[]" value="maybe" /> Maybe</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testMultiCheckboxWithValues() {
        $checkbox = $this->helper;
        $actual = $checkbox(array(
            'name' => 'foo',
            'value' => array('yes', 'no'),
            'options' => array(
                'yes' => 'Yes',
                'no' => 'No',
                'maybe' => 'Maybe'
            ),
            'attribs' => array(
                'value' => 'yes',
                'label' => 'Is ignored',
            )
        ))->__toString();
        $expect = '<label><input type="checkbox" name="foo[]" value="yes" checked /> Yes</label>' . PHP_EOL
            . '<label><input type="checkbox" name="foo[]" value="no" checked /> No</label>' . PHP_EOL
            . '<label><input type="checkbox" name="foo[]" value="maybe" /> Maybe</label>' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }
}
