<?php
namespace Aura\Html;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class HelperLocatorTest extends TestCase
{
    protected $helper_locator;

    protected function set_up()
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

        $this->expectException('Aura\Html\Exception\HelperNotFound');
        $this->helper_locator->get('noSuchHelper');
    }

    public function testFactoryAddHelpers()
    {
        $factory = new HelperLocatorFactory;
        $override = array('a' => function () {
            return new Helper\MockHelper;
        });
        $helpers = $factory->newInstance($override);
        $expect = 'Aura\Html\Helper\MockHelper';
        $actual = $helpers->get('a');
        $this->assertInstanceOf($expect, $actual);


        $additional = array('mockHelper' => function () {
            return new Helper\MockHelper;
        });
        $helpers = $factory->newInstance($additional);
        $expect = 'Aura\Html\Helper\MockHelper';
        $actual = $helpers->get('mockHelper');
        $this->assertInstanceOf($expect, $actual);
    }
}
