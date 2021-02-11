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
 * Helper for `<ul>` tag with `<li>` items.
 *
 * @package Aura.Html
 *
 */
abstract class AbstractList extends AbstractHelper
{
    /**
     *
     * Attributes for the ul tag.
     *
     * @var array
     *
     */
    protected $attr = array();

    /**
     *
     * The stack of HTML elements.
     *
     * @var array
     *
     */
    protected $stack = array();

    /**
     *
     * The generated HTML.
     *
     * @var string
     *
     */
    protected $html = '';

    /**
     *
     * Initializes and returns the UL object.
     *
     * @param array $attr Attributes for the UL tag.
     *
     * @return self
     *
     */
    public function __invoke(array $attr = null)
    {
        if ($attr !== null) {
            $this->attr = $attr;
        }
        return $this;
    }

    /**
     *
     * Generates and returns the HTML for the list.
     *
     * @return string
     *
     */
    public function __toString()
    {
        // if there is no stack of items, **do not** return an empty
        // <ul></ul> tag set.
        if (! $this->stack) {
            return '';
        }

        $tag = $this->getTag();
        $attr = $this->escaper->attr($this->attr);
        if ($attr) {
            $this->html = $this->indent(0, "<{$tag} {$attr}>");
        } else {
            $this->html = $this->indent(0, "<{$tag}>");
        }

        foreach ($this->stack as $item) {
            $this->buildItem($item);
        }

        $html = $this->html . $this->indent(0, "</{$tag}>");

        $this->attr  = array();
        $this->stack = array();
        $this->html  = '';

        return $html;
    }

    /**
     *
     * Adds a single item to the stack; the text will be escaped.
     *
     * @param string $text The list item text.
     *
     * @param array $attr Attributes for the list item tag.
     *
     * @return self
     *
     */
    public function item($text, array $attr = array())
    {
        $this->stack[] = array(
            $this->escaper->html($text),
            $this->escaper->attr($attr),
        );
        return $this;
    }

    /**
     *
     * Adds multiple items to the stack; the text will be escaped.
     *
     * @param array $items An array of text, or text => attribs, for the list
     * items.
     *
     * @return self
     *
     */
    public function items(array $items)
    {
        foreach ($items as $key => $val) {
            if (is_int($key)) {
                $this->item($val);
            } else {
                $this->item($key, $val);
            }
        }
        return $this;
    }

    /**
     *
     * Adds a single raw item to the stack; the text will **not** be escaped.
     *
     * @param string $text The list item text.
     *
     * @param array $attr Attributes for the list item tag.
     *
     * @return self
     *
     */
    public function rawItem($text, array $attr = array())
    {
        $this->stack[] = array(
            $text,
            $this->escaper->attr($attr),
        );
        return $this;
    }

    /**
     *
     * Adds multiple raw items to the stack; the text will **not** be escaped.
     *
     * @param array $items An array of text, or text => attribs, for the list
     * items.
     *
     * @return self
     *
     */
    public function rawItems(array $items)
    {
        foreach ($items as $key => $val) {
            if (is_int($key)) {
                $this->rawItem($val);
            } else {
                $this->rawItem($key, $val);
            }
        }
        return $this;
    }

    /**
     *
     * Builds the HTML for a single list item.
     *
     * @param string $item The item HTML.
     *
     * @return null
     *
     */
    protected function buildItem($item)
    {
        list($html, $attr) = $item;
        if ($attr) {
            $this->html .= $this->indent(1, "<li {$attr}>$html</li>");
        } else {
            $this->html .= $this->indent(1, "<li>$html</li>");
        }
    }

    /**
     *
     * Returns the tag name.
     *
     * @return string
     *
     */
    abstract protected function getTag();
}
