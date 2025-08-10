<?php
namespace Aura\Html\Helper;

use Aura\Html\EscaperFactory;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

abstract class AbstractHelperTest extends TestCase
{
    protected $helper;

    protected function set_up()
    {
        parent::set_up();
        $this->helper = $this->newHelper();
    }

    protected function newHelper()
    {
        $class = substr(get_class($this), 0, -4);
        $escaper_factory = new EscaperFactory;
        return new $class($escaper_factory->newInstance());
    }
}
