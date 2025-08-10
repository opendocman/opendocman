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
 * Helper to generate a snippet of nested HTML.
 *
 * @package Aura.Html
 *
 */
class StructureRaw extends AbstractElement
{
    /**
     *
     * Returns any kind of nestd HTML elements with attributes.
     *
     * @param string $tag The tag to generate.
     *
     * @param string|array $content The element content
     *
     * @param array $attr Attributes for the tag.
     *
     * @return string
     *
     */
    public function __invoke($tag, $content, array $attr = array())
    {
        if (is_array($content)) {
            $content = call_user_func_array($this, $content);
        }

        return $this->raw($tag, $content, $attr);
    }
}
