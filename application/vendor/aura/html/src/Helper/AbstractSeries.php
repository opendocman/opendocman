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
 * Abstract helper for an element series with positional ordering.
 *
 * @package Aura.Html
 *
 */
abstract class AbstractSeries extends AbstractHelper
{
    /**
     *
     * The array of all elements in the series, by position.
     *
     * @var array
     *
     */
    protected $elements = array();

    /**
     *
     * Returns the helper so you can call methods on it.
     *
     * If you pass arguments to __invoke(), it will call `$this->add()` with
     * those arguments.
     *
     * @return self
     *
     */
    public function __invoke()
    {
        $args = func_get_args();
        if ($args) {
            call_user_func_array(array($this, 'add'), $args);
        }
        return $this;
    }

    /**
     *
     * Returns the elements in order as a single string and resets the elements.
     *
     * @return string The elements as a string.
     *
     */
    public function __toString()
    {
        $html = '';
        ksort($this->elements);
        foreach ($this->elements as $pos => $elements) {
            foreach ($elements as $element) {
                $html .= $this->indent . $element . PHP_EOL;
            }
        }
        $this->elements = array();
        return $html;
    }

    /**
     *
     * Adds an element at a certain position.
     *
     * @param int $pos The element position.
     *
     * @param string $element The element itself.
     *
     * @return null
     *
     */
    protected function addElement($pos, $element)
    {
        $this->elements[(int) $pos][] = $element;
    }
}
