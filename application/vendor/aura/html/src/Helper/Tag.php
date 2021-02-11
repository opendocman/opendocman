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
 * Helper to generate any tag.
 *
 * @package Aura.Html
 *
 */
class Tag extends AbstractHelper
{
    /**
     *
     * Returns any kind of tag with attributes.
     *
     * @param string $tag The tag to generate.
     *
     * @param array $attr Attributes for the tag.
     *
     * @return string
     *
     */
    public function __invoke($tag, array $attr = array())
    {
        $attr = $this->escaper->attr($attr);
        if ($attr) {
            return "<{$tag} $attr>";
        }
        return "<{$tag}>";
    }
}
