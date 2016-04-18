<?php
namespace Aura\Html\Helper;

use Aura\Html\EscaperFactory;

abstract class AbstractHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp()
    {
        parent::setUp();
        $this->helper = $this->newHelper();
    }

    protected function newHelper()
    {
        $class = substr(get_class($this), 0, -4);
        $escaper_factory = new EscaperFactory;
        return new $class($escaper_factory->newInstance());
    }
}
