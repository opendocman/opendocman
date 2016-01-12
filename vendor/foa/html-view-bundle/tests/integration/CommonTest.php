<?php
namespace FOA\Html_View_Bundle\_Config;

use Aura\Di\ContainerBuilder;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOfHtmlHelper()
    {
        $builder = new ContainerBuilder();
        $di = $builder->newInstance(
            array(),
            array(
                'Aura\View\_Config\Common',
                'Aura\Html\_Config\Common',
                'FOA\Html_View_Bundle\_Config\Common',
            )
        );

        $view = $di->newInstance('Aura\View\View');
        $helpers = $view->getHelpers();
        $this->assertInstanceOf('Aura\Html\HelperLocator', $helpers);
    }
}
