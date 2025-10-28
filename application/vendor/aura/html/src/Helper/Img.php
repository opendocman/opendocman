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
 * Helper to generate an `<img ... />` tag.
 *
 * @package Aura.Html
 *
 */
class Img extends AbstractHelper
{
    /**
     *
     * Returns an <img ... /> tag.
     *
     * If an "alt" attribute is not specified, will add it from the
     * image basename.
     *
     * @param string $src The href to the image source.
     *
     * @param array $attr Additional attributes for the tag.
     *
     * @return string An <img ... /> tag.
     *
     * @todo Add automated height/width calculation?
     *
     */
    public function __invoke($src, array $attr = array())
    {
        $base = array(
            'src' => $src,
            'alt' => basename($src),
        );

        unset($attr['src']);

        $attr = array_merge($base, $attr);

        return $this->void('img', $attr);
    }
}
