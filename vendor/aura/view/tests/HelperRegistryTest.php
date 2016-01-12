<?php
namespace Aura\View;

class HelperRegistryTest extends \PHPUnit_Framework_TestCase
{
    protected $helper_registry;

    protected function setUp()
    {
        $this->helper_registry = new HelperRegistry;
    }

    public function testSetHasGet()
    {
        $foo = function () {
            return "Foo!";
        };

        $this->assertFalse($this->helper_registry->has('foo'));

        $this->helper_registry->set('foo', $foo);
        $this->assertTrue($this->helper_registry->has('foo'));

        $helper = $this->helper_registry->get('foo');
        $this->assertSame($foo, $helper);

        $this->setExpectedException('Aura\View\Exception\HelperNotFound');
        $this->helper_registry->get('bar');
    }

    public function testCall()
    {
        $this->helper_registry->set('hello', function ($noun) {
            return "Hello {$noun}!";
        });

        $actual = $this->helper_registry->hello('World');
        $expect = 'Hello World!';
        $this->assertSame($expect, $actual);
    }
}
