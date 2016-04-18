<?php
namespace Aura\Html;

class HelperLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $helper_locator;

    protected function setUp()
    {
        $factory = new HelperLocatorFactory;
        $this->helper_locator = $factory->newInstance();
    }

    public function test()
    {
        $this->helper_locator->set('mockHelper', function () {
            return new Helper\MockHelper;
        });

        $expect = 'Aura\Html\Helper\MockHelper';
        $actual = $this->helper_locator->get('mockHelper');
        $this->assertInstanceOf($expect, $actual);

        $expect = 'Hello World';
        $actual = $this->helper_locator->mockHelper('World');
        $this->assertSame($expect, $actual);

        $this->setExpectedException('Aura\Html\Exception\HelperNotFound');
        $this->helper_locator->get('noSuchHelper');
    }
}
