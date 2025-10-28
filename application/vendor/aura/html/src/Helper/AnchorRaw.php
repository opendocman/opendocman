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
 * Helper to generate `<a ... />` tags without escaping the $text param.
 *
 * @package Aura.Html
 *
 */
class AnchorRaw extends AbstractHelper
{
    /**
     *
     * Returns an anchor tag with text left unescaped.
     *
     * @param string $href The anchor href specification.
     *
     * @param string $text The text for the anchor.
     *
     * @param array $attr Attributes for the anchor.
     *
     * @return string
     *
     */
    public function __invoke($href, $text, array $attr = array())
    {
        $base = array(
            'href' => $href,
        );

        unset($attr['href']);

        $attr = $this->escaper->attr(array_merge($base, $attr));
        return "<a $attr>$text</a>";
    }
}
