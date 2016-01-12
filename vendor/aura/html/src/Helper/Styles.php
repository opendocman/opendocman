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
 * Helper for a series of <link rel="stylesheet" ... /> tags.
 *
 * @package Aura.Html
 *
 */
class Styles extends AbstractSeries
{
    /**
     *
     * Adds a <link rel="stylesheet" ... /> tag to the series.
     *
     * @param string $href The source href for the stylesheet.
     *
     * @param array $attr Additional attributes for the <link> tag.
     *
     * @param int $pos The stylesheet position in the series.
     *
     * @return self
     *
     */
    public function add($href, array $attr = null, $pos = 100)
    {
        $attr = $this->fixAttr($href, $attr);
        $tag = $this->void('link', $attr);
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     *
     * Adds a conditional `<!--[if ...]><link rel="stylesheet" ... /><![endif] -->`
     * tag to the stack.
     *
     * @param string $cond The conditional expression for the stylesheet.
     *
     * @param string $href The source href for the stylesheet.
     *
     * @param array $attr Additional attributes for the <link> tag.
     *
     * @param string $pos The stylesheet position in the stack.
     *
     * @return self
     *
     */
    public function addCond($cond, $href, array $attr = null, $pos = 100)
    {
        $attr = $this->fixAttr($href, $attr);
        $link = $this->void('link', $attr);
        $cond  = $this->escaper->html($cond);
        $tag  = "<!--[if $cond]>$link<![endif]-->";
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     *
     * Fixes the attributes for the stylesheet.
     *
     * @param string $href The source href for the stylesheet.
     *
     * @param array $attr Additional attributes for the <link> tag.
     *
     * @return array The fixed attributes.
     *
     */
    protected function fixAttr($href, array $attr = null)
    {
        $attr = (array) $attr;

        $base = array(
            'rel'   => 'stylesheet',
            'href'  => $href,
            'type'  => 'text/css',
            'media' => 'screen',
        );

        unset($attr['rel']);
        unset($attr['href']);

        return array_merge($base, (array) $attr);
    }
}
