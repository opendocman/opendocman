<?php
namespace Aura\Html;

class FakePhp
{
    static public $function_exists = array();

    static public function function_exists($name)
    {
        return self::$function_exists[$name];
    }
}
