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
     * Temporary storage or params passed to caputre functions
     *
     * @var mixed
     *
     * @access private
     */
    private $capture;

    /**
     *
     * Adds a <script> tag to the stack.
     *
     * @param string $src The source href for the script.
     *
     * @param int $pos The script position in the stack.
     *
     * @param array $attr The additional attributes
     *
     * @return self
     *
     */
    public function add($src, $pos = 100, array $attr = array())
    {
        $attr = $this->attr($src, $attr);
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
     * @param array $attr The additional attributes
     *
     * @return self
     *
     */
    public function addCond($cond, $src, $pos = 100, array $attr = array())
    {
        $cond = $this->escaper->html($cond);
        $attr = $this->attr($src, $attr);
        $tag = "<!--[if $cond]><script $attr></script><![endif]-->";
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     * Adds internal script
     *
     * @param mixed $script The script
     * @param int   $pos    The script position in the stack.
     * @param array $attr The additional attributes
     *
     * @return self
     *
     * @access public
     */
    public function addInternal($script, $pos = 100, array $attr = array())
    {
        $attr = $this->attr(null, $attr);
        $tag = "<script $attr>$script</script>";
        $this->addElement($pos, $tag);
        return $this;
    }

    /**
     * Add Conditional internal script
     *
     * @param mixed $cond   The conditional expression for the script.
     * @param mixed $script The script
     * @param int   $pos    The script position in the stack.
     * @param array $attr   The additional attributes
     *
     * @return self
     *
     * @access public
     */
    public function addCondInternal($cond, $script, $pos = 100, array $attr = array())
    {
        $cond = $this->escaper->html($cond);
        $attr = $this->attr(null, $attr);
        $tag = "<!--[if $cond]><script $attr>$script</script><![endif]-->";
        $this->addElement($pos, $tag);

        return $this;
    }

    /**
     * Adds internal script
     *
     * @param int   $pos  The script position in the stack.
     * @param array $attr The additional attributes
     *
     *
     * @return null
     *
     * @access public
     */
    public function beginInternal($pos = 100, array $attr = array())
    {
        $this->capture[] = array($pos, $attr);
         ob_start();
    }

    /**
     * Begin Conditional Internal Capture
     *
     * @param mixed $cond condition
     * @param int   $pos  position
     * @param array $attr The additional attributes
     *
     * @return null
     *
     * @access public
     */
    public function beginCondInternal($cond, $pos = 100, array $attr = array())
    {
        $this->capture[] = array($cond, $pos, $attr);
        ob_start();
    }

    /**
     * End internal script capture
     *
     * @return mixed
     *
     * @access public
     */
    public function endInternal()
    {
        $script = ob_get_clean();
        $params = array_pop($this->capture);
        if (count($params) > 2) {
            return $this->addCondInternal(
                $params[0],
                $script,
                $params[1],
                $params[2]
            );
        }
        return $this->addInternal($script, $params[0], $params[1]);
    }

    /**
     * Fix and escape script attributes
     *
     * @param mixed $src  script source
     * @param array $attr additional attributes
     *
     * @return string
     *
     * @access protected
     */
    protected function attr($src = null, array $attr = array())
    {
        if (null !== $src) {
            $attr['src'] = $src;
        }
        $attr['type'] = 'text/javascript';
        return $this->escaper->attr($attr);
    }
}
