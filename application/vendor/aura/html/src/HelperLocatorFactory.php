<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html;

use Aura\Html\Helper;
use Aura\Html\Escaper;

/**
 *
 * Factory to create a HelperLocator object with all helpers.
 *
 * @package Aura.Html
 *
 */
class HelperLocatorFactory
{
    /**
     *
     * The Escaper for the helpers.
     *
     * @var Escaper
     *
     */
    protected $escaper;

    /**
     *
     * Constructor.
     *
     * @param string $encoding The encoding for the escapers.
     *
     * @param int $flags The `htmlspecialchars()` flags for the escapers.
     *
     */
    public function __construct($encoding = null, $flags = null)
    {
        $escaper_factory = new EscaperFactory($encoding, $flags);
        $this->escaper = $escaper_factory->newInstance();
        Escaper::setStatic($this->escaper);
    }

    /**
     *
     * Returns a new HelperLocator with all helpers.
     *
     * @return HelperLocator
     *
     */
    public function newInstance(array $helpers = array(), array $input_helpers = array())
    {
        $escaper = $this->escaper;
        $input = $this->newInputInstance($input_helpers);
        return new HelperLocator(array_replace(array(
            'a'                 => function () use ($escaper) { return new Helper\Anchor($escaper); },
            'anchor'            => function () use ($escaper) { return new Helper\Anchor($escaper); },
            'aRaw'              => function () use ($escaper) { return new Helper\AnchorRaw($escaper); },
            'anchorRaw'         => function () use ($escaper) { return new Helper\AnchorRaw($escaper); },
            'base'              => function () use ($escaper) { return new Helper\Base($escaper); },
            'escape'            => function () use ($escaper) { return $escaper; },
            'element'           => function () use ($escaper) { return new Helper\Element($escaper); },
            'ele'               => function () use ($escaper) { return new Helper\Element($escaper); },
            'elementRaw'        => function () use ($escaper) { return new Helper\ElementRaw($escaper); },
            'eleRaw'            => function () use ($escaper) { return new Helper\ElementRaw($escaper); },
            'form'              => function () use ($escaper) { return new Helper\Form($escaper); },
            'img'               => function () use ($escaper) { return new Helper\Img($escaper); },
            'image'             => function () use ($escaper) { return new Helper\Img($escaper); },
            'input'             => function () use ($input)   { return $input; },
            'label'             => function () use ($escaper) { return new Helper\Label($escaper); },
            'links'             => function () use ($escaper) { return new Helper\Links($escaper); },
            'metas'             => function () use ($escaper) { return new Helper\Metas($escaper); },
            'ol'                => function () use ($escaper) { return new Helper\Ol($escaper); },
            'scripts'           => function () use ($escaper) { return new Helper\Scripts($escaper); },
            'scriptsFoot'       => function () use ($escaper) { return new Helper\Scripts($escaper); },
            'styles'            => function () use ($escaper) { return new Helper\Styles($escaper); },
            'structure'         => function () use ($escaper) { return new Helper\Structure($escaper); },
            'structureRaw'      => function () use ($escaper) { return new Helper\StructureRaw($escaper); },
            'struct'            => function () use ($escaper) { return new Helper\Structure($escaper); },
            'structRaw'         => function () use ($escaper) { return new Helper\StructureRaw($escaper); },
            'tag'               => function () use ($escaper) { return new Helper\Tag($escaper); },
            'title'             => function () use ($escaper) { return new Helper\Title($escaper); },
            'ul'                => function () use ($escaper) { return new Helper\Ul($escaper); },
            'void'              => function () use ($escaper) { return new Helper\VoidTag($escaper); }
        ), $helpers));
    }

    /**
     *
     * Returns a new Input helper locator.
     *
     * @return Helper\Input
     *
     */
    public function newInputInstance(array $helpers = array())
    {
        $escaper = $this->escaper;
        return new Helper\Input(array_replace(array(
            'button'            => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'checkbox'          => function () use ($escaper) { return new Helper\Input\Checkbox($escaper); },
            'color'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'date'              => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'datetime'          => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'datetime-local'    => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'email'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'file'              => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'hidden'            => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'image'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'month'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'number'            => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'password'          => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'radio'             => function () use ($escaper) { return new Helper\Input\Radio($escaper); },
            'range'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'reset'             => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'search'            => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'select'            => function () use ($escaper) { return new Helper\Input\Select($escaper); },
            'submit'            => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'tel'               => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'text'              => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'textarea'          => function () use ($escaper) { return new Helper\Input\Textarea($escaper); },
            'time'              => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'url'               => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
            'week'              => function () use ($escaper) { return new Helper\Input\Generic($escaper); },
        ), $helpers));
    }
}
