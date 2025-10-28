<?php
namespace Aura\Html\Helper;
class MockHelper
{
    public function __invoke($noun)
    {
        return "Hello $noun";
    }
}
