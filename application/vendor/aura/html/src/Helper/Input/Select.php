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
 * An HTML select input.
 *
 * @package Aura.Html
 *
 */
class Select extends AbstractInput
{
    /**
     *
     * A stack of HTML pieces for the select.
     *
     * @var array
     *
     */
    protected $stack = array();

    /**
     *
     * Are we currently processing an optgroup?
     *
     * @var bool
     *
     */
    protected $optgroup = false;

    /**
     *
     * The current option indent level.
     *
     * @var int
     *
     */
    protected $optlevel = 1;

    /**
     *
     * The value of the 'placeholder' pseudo-attribute.
     *
     * @var mixed
     *
     */
    protected $placeholder;

    /**
     *
     * Use strict equality when matching selected option values?
     *
     * @var bool
     *
     */
    protected $strict = false;

    /**
     *
     * If a $spec is passed, returns a full select tag with options; if no $spec
     * is passed, returns this helper object itself.
     *
     * @param array $spec A select input specfication.
     *
     * @return string|self
     *
     */
    public function __invoke(array $spec = null)
    {
        if ($spec !== null) {
            $this->prep($spec);
            $this->attribs($this->attribs);
            $this->options($this->options);
            $this->selected($this->value);
        }

        return $this;
    }

    /**
     *
     * Returns a select tag with options.
     *
     * @return string
     *
     */
    public function __toString()
    {
        // build the html
        $html = $this->buildSelect()
              . $this->buildOptionPlaceholder()
              . $this->buildOptions()
              . $this->indent(0, '</select>');

        // reset for next time
        $this->stack = array();
        $this->optgroup = false;
        $this->optlevel = 1;
        $this->placeholder = null;
        $this->strict = false;

        // done!
        return $html;
    }

    /**
     *
     * Sets the HTML attributes for the select tag.
     *
     * @param array $attribs The attribues to set.
     *
     * @return string
     *
     */
    public function attribs(array $attribs)
    {
        $this->attribs = $attribs;
        if (isset($this->attribs['placeholder'])) {
            $this->placeholder($this->attribs['placeholder']);
            unset($this->attribs['placeholder']);
        }
        if (isset($this->attribs['strict'])) {
            $this->strict($this->attribs['strict']);
            unset($this->attribs['strict']);
        }
        return $this;
    }

    /**
     *
     * Adds a single option to the stack.
     *
     * @param string $value The option value.
     *
     * @param string $label The option label.
     *
     * @param array $attribs Attributes for the option.
     *
     * @return self
     *
     */
    public function option($value, $label, array $attribs = array())
    {
        $this->stack[] = array('buildOption', $value, $label, $attribs);
        return $this;
    }

    /**
     *
     * Adds multiple options to the stack.
     *
     * @param array $options An array of options where the key is the option
     * value, and the value is the option label.  If the value is an array,
     * the key is treated as a label for an optgroup, and the value is
     * a sub-array of options.
     *
     * @param array $attribs Attributes to be used on each option.
     *
     * @return self
     *
     */
    public function options(array $options, array $attribs = array())
    {
        // set the options and optgroups
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                // the key is an optgroup label
                $this->optgroup($key);
                // recursively descend into the array
                $this->options($val, $attribs);
            } else {
                // the key is an option value and the val is an option label
                $this->option($key, $val, $attribs);
            }
        }

        return $this;
    }

    /**
     *
     * Adds an optgroup input to the stack.
     *
     * @param string $label The optgroup label.
     *
     * @param array $attribs Attributes for the optgroup.
     *
     * @return self
     *
     */
    public function optgroup($label, array $attribs = array())
    {
        if ($this->optgroup) {
            $this->stack[] = array('endOptgroup');
        }
        $this->stack[] = array('beginOptgroup', $label, $attribs);
        $this->optgroup = true;
        return $this;
    }

    /**
     *
     * Sets the selected value(s).
     *
     * @param mixed $selected The selected value(s).
     *
     * @return self
     *
     */
    public function selected($selected)
    {
        $this->value = (array) $selected;
        return $this;
    }

    /**
     *
     * Sets the text for a placeholder option.
     *
     * @param string $placeholder The placeholder text.
     *
     * @return self
     *
     */
    public function placeholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     *
     * Use strict equality when matching selected option values?
     *
     * @param bool $strict True for strict equality, false for loose equality.
     *
     * @return self
     *
     * @see buildOption()
     *
     */
    public function strict($strict = true)
    {
        $this->strict = (bool) $strict;
        return $this;
    }

    /**
     *
     * Builds the opening select tag.
     *
     * @return string
     *
     */
    protected function buildSelect()
    {
        $append_brackets = isset($this->attribs['multiple'])
                        && $this->attribs['multiple']
                        && isset($this->attribs['name'])
                        && substr($this->attribs['name'], -2) != '[]';

        // if this is a multiple select, the name needs to end in "[]"
        if ($append_brackets) {
            $this->attribs['name'] .= '[]';
        }

        $attr = $this->escaper->attr($this->attribs);
        return $this->indent(0, "<select {$attr}>");
    }

    /**
     *
     * Builds the 'placeholder' option (if any).
     *
     * @return string
     *
     */
    protected function buildOptionPlaceholder()
    {
        if ($this->placeholder) {
            return $this->buildOption(array(
                '',
                $this->placeholder,
                array('disabled' => true),
            ));
        }
    }

    /**
     *
     * Builds the collection of option tags.
     *
     * @return string
     *
     */
    protected function buildOptions()
    {
        $html = '';

        foreach ($this->stack as $info) {
            $method = array_shift($info);
            $html .= $this->$method($info);
        }

        // close any optgroup tags
        if ($this->optgroup) {
            $html .= $this->endOptgroup();
        }

        return $html;
    }

    /**
     *
     * Builds the HTML for a single option.
     *
     * @param array $info The option info.
     *
     * @return string
     *
     */
    protected function buildOption($info)
    {
        list($value, $label, $attribs) = $info;

        // set the option value into the attribs
        $attribs['value'] = $value;

        // is the value selected?
        if (in_array($value, $this->value, $this->strict)) {
            $attribs['selected'] = true;
        } else {
            unset($attribs['selected']);
        }

        // build attributes and return option tag with label text
        $attribs = $this->escaper->attr($attribs);
        $label = $this->escaper->html($label);
        return $this->indent($this->optlevel, "<option {$attribs}>{$label}</option>");
    }

    /**
     *
     * Builds the HTML to begin an optgroup.
     *
     * @param array $info The optgroup info.
     *
     * @return null
     *
     */
    protected function beginOptgroup($info)
    {
        list($label, $attribs) = $info;
        $this->optlevel += 1;
        $attribs['label'] = $label;
        $attribs = $this->escaper->attr($attribs);
        return $this->indent(1, "<optgroup {$attribs}>");
    }

    /**
     *
     * Builds the HTML to end an optgroup.
     *
     * @return null
     *
     */
    protected function endOptgroup()
    {
        $this->optlevel -= 1;
        return $this->indent(1, "</optgroup>");
    }
}
