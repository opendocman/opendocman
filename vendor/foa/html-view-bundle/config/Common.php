<?php
namespace FOA\Html_View_Bundle\_Config;

use Aura\Di\Container;
use Aura\Di\Config;

class Common extends Config
{
    public function define(Container $di)
    {
        $di->params['Aura\View\View']['helpers'] = $di->lazyGet('aura/html:helper');
    }

    public function modify(Container $di)
    {
    }
}
