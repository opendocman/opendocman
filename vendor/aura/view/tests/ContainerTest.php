<?php
namespace Aura\View\_Config;

use Aura\Di\_Config\AbstractContainerTest;

class ContainerTest extends AbstractContainerTest
{
    protected function getConfigClasses()
    {
        return array(
            'Aura\View\_Config\Common',
        );
    }

    protected function getAutoResolve()
    {
        return false;
    }

    public function provideNewInstance()
    {
        return array(
            array('Aura\View\View'),
        );
    }
}
