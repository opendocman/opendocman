<?php
namespace Aura\View\_Config;

use Aura\Di\Config;
use Aura\Di\Container;

class Common extends Config
{
    public function define(Container $di)
    {
        $di->params['Aura\View\View'] = array(
            'view_registry'   => $di->lazyNew('Aura\View\TemplateRegistry'),
            'layout_registry' => $di->lazyNew('Aura\View\TemplateRegistry'),
            'helpers'         => $di->lazyNew('Aura\View\HelperRegistry'),
        );
    }

    public function modify(Container $di)
    {
    }
}
