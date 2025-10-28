<?php
namespace Aura\Html\Helper\Input;

use Aura\Html\Helper\AbstractHelperTest;

class TextareaTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $textarea = $this->helper;

        $actual = $textarea(array(
            'name' => 'field',
            'value' => "the quick & brown fox",
        ))->__toString();

        $expect = '<textarea name="field">the quick &amp; brown fox</textarea>';

        $this->assertSame($expect, $actual);
    }
}
