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
 * Helper to generate an `<label ... ></label>` tag.
 * Optionally encloses an input element.
 *
 * @package Aura.Html
 *
 */
class Label extends AbstractHelper
{
    /**
     *
     * Attributes for the label.
     *
     * @var array
     *
     */
    protected $attr = array();

    /**
     *
     * The label text goes before this raw HTML.
     *
     * @var string
     *
     */
    protected $before;

    /**
     *
     * The label text goes after this raw HTML.
     *
     * @var string
     *
     */
    protected $after;

    /**
     *
     * The label text.
     *
     * @var string
     *
     */
    protected $label;

    /**
     *
     * Returns a <label ... ></label> tag optionally enclosing an input.
     *
     * @param string $label The text for the label.
     *
     * @param array $attr Additional attributes for the tag.
     *
     * @return self
     *
     */
    public function __invoke($label = null, array $attr = array())
    {
        if ($label !== null) {
            $this->label = $label;
        }

        if ($attr !== array()) {
            $this->attr = $attr;
        }

        return $this;
    }

    /**
     *
     * Place the label text before this raw HTML.
     *
     * @param string $before Place the label text before this raw HTML.
     *
     * @return self
     *
     */
    public function before($before)
    {
        $this->before = $before;
        return $this;
    }

    /**
     *
     * Place the label text after this raw HTML.
     *
     * @param string $after Place the label text after this raw HTML.
     *
     * @return self
     *
     */
    public function after($after)
    {
        $this->after = $after;
        return $this;
    }

    /**
     *
     * Returns the label string with the before/after HTML.
     *
     * @return string
     *
     */
    public function __toString()
    {
        $attr = $this->escaper->attr($this->attr);
        $html = trim("<label $attr") . ">"
              . $this->after // label goes after this html
              . $this->escaper->html($this->label)
              . $this->before // label goes before this html
              . "</label>";

        $this->attr = array();
        $this->label = null;
        $this->before = null;
        $this->after = null;

        return $html;
    }
}
