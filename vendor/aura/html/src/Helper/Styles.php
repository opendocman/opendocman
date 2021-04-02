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
     * Temporary storage or params passed to caputre functions
     *
     * @var mixed
     *
     * @access private
     */
    private $capture;

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
     * Returns a "style" tag
     *
     * @param mixed $css  The source CSS
     * @param array $attr The attributes for the tag
     *
     * @return string
     *
     * @access protected
     */
    protected function style($css, array $attr = null)
    {
        $attr = $this->fixInternalAttr($attr);
        $attr = $this->escaper->attr($attr);
        return "<style $attr>$css</style>";
    }

    /**
     * addInternal
     *
     * @param mixed $css  The source CSS
     * @param array $attr Additional attributes for the <style> tag
     * @param int   $pos  The position in the stack.
     *
     * @return self
     *
     * @access public
     */
    public function addInternal($css, array $attr = null, $pos = 100)
    {
        $style = $this->style($css, $attr);
        $this->addElement($pos, $style);
        return $this;
    }

    /**
     *
     * Adds a conditional `<!--[if ...]><style ... /><![endif] -->`
     * tag to the stack.
     *
     * @param string $cond The conditional expression for the css.
     *
     * @param string $css The source css.
     *
     * @param array $attr Additional attributes for the <style> tag.
     *
     * @param string $pos The position in the stack.
     *
     * @return self
     *
     */
    public function addCondInternal($cond, $css, array $attr = null, $pos = 100)
    {
        $style = $this->style($css, $attr);
        $cond  = $this->escaper->html($cond);
        $tag  = "<!--[if $cond]>$style<![endif]-->";
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     * Begins output buffering for a conditional style tag
     *
     * @param array $attr Additional attributes for the <style> tag.
     * @param int   $pos  The style position in the stack
     *
     * @return void
     *
     * @access public
     */
    public function beginInternal(array $attr = null, $pos = 100)
    {
        $this->capture[] = array(
            'attr' => $attr,
            'pos' => $pos
        );

         ob_start();
    }

    /**
     * Begins output buffering for a conditional style tag
     *
     * @param mixed $cond The conditional expression for the css
     * @param array $attr Additional attributes for the <style> tag.
     * @param int   $pos  The style position in the stack
     *
     * @return void
     *
     * @access public
     */
    public function beginCondInternal($cond, array $attr = null, $pos = 100)
    {
        $this->capture[] = array(
            'attr' => $attr,
            'pos' => $pos,
            'cond' => $cond
        );

         ob_start();
    }

    /**
     * Ends buffering and retains output for the most-recent internal.
     *
     * @return self
     *
     * @access public
     */
    public function endInternal()
    {
        $params = array_pop($this->capture);
        $css = ob_get_clean();

        if (isset($params['cond'])) {
            return $this->addCondInternal(
                $params['cond'],
                $css,
                $params['attr'],
                $params['pos']
            );
        }

        return $this->addInternal(
            $css,
            $params['attr'],
            $params['pos']
        );
    }

    /**
     * Fixes the attributes for the internal stylesheet.
     *
     * @param array $attr Additional attributes for the <style> tag.
     *
     * @return array
     *
     * @access protected
     */
    protected function fixInternalAttr(array $attr = null)
    {
        $attr = (array) $attr;

        $base = array(
            'type' => 'text/css',
            'media' => 'screen',
        );

        unset($attr['rel']);
        unset($attr['href']);

        return array_merge($base, (array) $attr);
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
