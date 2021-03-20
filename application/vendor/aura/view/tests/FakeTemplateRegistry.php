<?php
namespace Aura\View;

class FakeTemplateRegistry extends TemplateRegistry
{
    // a fake file system
    public $fakefs = array();

    // read from the fake file system
    protected function isReadable($file)
    {
        // use parent for coverage
        parent::isReadable($file);
        // now use the fake
        return isset($this->fakefs[$file]);
    }

    // do not wrap in closure
    protected function enclose($__FILE__)
    {
        return $__FILE__;
    }
}
