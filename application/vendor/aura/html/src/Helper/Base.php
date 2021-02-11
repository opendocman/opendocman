<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Helper;

/**
 *
 * Helper to generate a `<base ... />` tag.
 *
 * @package Aura.Html
 *
 */
class Base extends AbstractHelper
{
    /**
     *
     * Returns a `<base ... />` tag.
     *
     * @param string $href The base href.
     *
     * @return string The `<base ... />` tag.
     *
     */
    public function __invoke($href)
    {
        return $this->indent(1, $this->void('base', array('href' => $href)));
    }
}
