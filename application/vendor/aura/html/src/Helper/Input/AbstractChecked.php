<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Helper\Input;

/**
 *
 * Abstact helper for inputs that can be checked (e.g. radio or checkbox).
 *
 * @package Aura.Html
 *
 */
abstract class AbstractChecked extends AbstractInput
{
    /**
     *
     * The label for the input, if any.
     *
     * @var string
     *
     */
    protected $label;

    /**
     *
     * Use strict equality when setting the checked value?
     *
     * @var bool
     *
     */
    protected $strict = false;

    /**
     *
     * Use strict equality when setting the checked value?
     *
     * @param bool $strict True for strict equality, false for loose equality.
     *
     * @return self
     *
     */
    public function strict($strict = true)
    {
        $this->strict = (bool) $strict;
        return $this;
    }

    /**
     *
     * Returns the HTML for the "checked" part of the input.
     *
     * @return string
     *
     */
    protected function htmlChecked()
    {
        $this->setLabel();
        $this->setChecked();
        return $this->void('input', $this->attribs);
    }

    /**
     *
     * Extracts and retains the "label" pseudo-attribute.
     *
     * @return null
     *
     */
    protected function setLabel()
    {
        $this->label = null;
        if (! isset($this->attribs['label'])) {
            return;
        }

        $this->label = $this->attribs['label'];
        unset($this->attribs['label']);
    }

    /**
     *
     * Sets the "checked" attribute appropriately.
     *
     * @return null
     *
     */
    protected function setChecked()
    {
        $this->attribs['checked'] = false;

        if (! array_key_exists('value', $this->attribs)) {
            return;
        }

        if ($this->strict) {
            $this->attribs['checked'] = in_array($this->attribs['value'], (array)$this->value, true);
            return;
        }

        $this->attribs['checked'] = in_array($this->attribs['value'], (array)$this->value, false);
    }

    /**
     *
     * Returns the HTML for a "label" (if any) wrapped around the input.
     *
     * @param string $input The input to be wrapped by the label.
     *
     * @return string
     *
     */
    protected function htmlLabel($input)
    {
        if (! $this->label) {
            return $input;
        }

        $label = $this->escaper->html($this->label);

        if (isset($this->attribs['id'])) {
            $attribs = $this->escaper->attr(array('for' => $this->attribs['id']));
            return "<label {$attribs}>{$input} {$label}</label>";
        }

        return "<label>{$input} {$label}</label>";
    }
}
