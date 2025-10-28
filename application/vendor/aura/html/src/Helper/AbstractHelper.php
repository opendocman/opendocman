<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Helper;

use Aura\Html\Escaper;

/**
 *
 * Abstract helper base class.
 *
 * @package Aura.Html
 *
 */
abstract class AbstractHelper
{
    /**
     *
     * Use this as one level of indentation for output.
     *
     * @var string
     *
     */
    protected $indent = '    ';

    /**
     *
     * The current indent level.
     *
     * @var int
     *
     */
    protected $indent_level = 0;

    /**
     *
     * An escaper object.
     *
     * @var Escaper
     *
     */
    protected $escaper;

    /**
     *
     * Constructor.
     *
     * @param Escaper $escaper The escaper object.
     *
     * @return null
     *
     */
    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     *
     * Sets the string to use for one level of indentation.
     *
     * @param string $indent The indent string.
     *
     * @return null
     *
     */
    public function setIndent($indent)
    {
        $this->indent = $indent;
    }

    /**
     *
     * Sets the indent level.
     *
     * @param int $indent_level The indent level.
     *
     * @return self
     *
     */
    public function setIndentLevel($indent_level)
    {
        $this->indent_level = (int) $indent_level;
        return $this;
    }

    /**
     *
     * Returns a "void" tag (i.e., one with no body and no closing tag).
     *
     * @param string $tag The tag name.
     *
     * @param array $attr The attributes for the tag.
     *
     * @return string
     *
     */
    protected function void($tag, array $attr = array())
    {
        $attr = $this->escaper->attr($attr);
        $html = "<{$tag} {$attr} />";
        return $html;
    }

    /**
     *
     * Returns an indented string.
     *
     * @param int $level Indent to this level past the current level.
     *
     * @param string $text The string to indent.
     *
     * @return string The indented string.
     *
     */
    protected function indent($level, $text)
    {
        $level += $this->indent_level;
        return str_repeat($this->indent, $level) . $text . PHP_EOL;
    }
}
