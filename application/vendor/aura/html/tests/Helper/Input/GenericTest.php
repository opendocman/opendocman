<?php
namespace Aura\Html\Helper\Input;

use Aura\Html\Helper\AbstractHelperTest;

class GenericTest extends AbstractHelperTest
{
    /**
     * @dataProvider provideTypes
     */
    public function test($type)
    {
        $input = $this->helper;

        // value should override attribute
        $actual = $input(array(
            'type'    => $type,
            'name'    => 'foo',
            'value'   => 'bar',
            'attribs' => array(
                // should get overridden
                'value' => 'baz',
            ),
        ))->__toString();

        $expect = "<input type=\"$type\" name=\"foo\" value=\"bar\" />" . PHP_EOL;
        $this->assertSame($expect, $actual);

        // no value given so attribute should still be there
        $actual = $input(array(
            'type'     => $type,
            'name'     => 'foo',
            'attribs'  => array(
                // should remain in place
                'value' => 'baz',
            ),
        ))->__toString();

        $expect = "<input type=\"$type\" name=\"foo\" value=\"baz\" />" . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function provideTypes()
    {
        return array(
            array('button'),
            array('color'),
            array('date'),
            array('datetime'),
            array('datetime-local'),
            array('email'),
            array('file'),
            array('hidden'),
            array('image'),
            array('month'),
            array('number'),
            array('password'),
            array('range'),
            array('reset'),
            array('search'),
            array('submit'),
            array('tel'),
            array('text'),
            array('time'),
            array('url'),
            array('week'),
        );
    }
}
