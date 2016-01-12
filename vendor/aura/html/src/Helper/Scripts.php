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
 * Helper for a stack of <script> tags.
 *
 * @package Aura.Html
 *
 */
class Scripts extends AbstractSeries
{
    /**
     *
     * Adds a <script> tag to the stack.
     *
     * @param string $src The source href for the script.
     *
     * @param int $pos The script position in the stack.
     *
     * @return null
     *
     */
    public function add($src, $pos = 100)
    {
        $attr = $this->escaper->attr(array(
            'src' => $src,
            'type' => 'text/javascript',
        ));
        $tag = "<script $attr></script>";
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     *
     * Adds a conditional `<!--[if ...]><script><![endif] -->` tag to the
     * stack.
     *
     * @param string $cond The conditional expression for the script.
     *
     * @param string $src The source href for the script.
     *
     * @param string $pos The script position in the stack.
     *
     * @return null
     *
     */
    public function addCond($cond, $src, $pos = 100)
    {
        $cond = $this->escaper->html($cond);
        $attr = $this->escaper->attr(array(
            'src' => $src,
            'type' => 'text/javascript',
        ));
        $tag = "<!--[if $cond]><script $attr></script><![endif]-->";
        $this->addElement($pos, $tag);

        return $this;
    }
}
