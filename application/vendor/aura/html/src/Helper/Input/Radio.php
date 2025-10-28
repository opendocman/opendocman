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
 * An HTML radio input.
 *
 * @package Aura.Html
 *
 */
class Radio extends AbstractChecked
{
    /**
     *
     * Returns the HTML for the input.
     *
     * @return string
     *
     */
    public function __toString()
    {
        $this->attribs['type'] = 'radio';

        if ($this->options) {
            return $this->multiple();
        }

        $input = $this->htmlChecked();
        $html  = $this->htmlLabel($input);
        return $this->indent(0, $html);
    }

    /**
     *
     * Returns the HTML for multiple radios.
     *
     * @return string
     *
     */
    protected function multiple()
    {
        $html = '';
        $radio = clone($this);
        foreach ($this->options as $value => $label) {
            $this->attribs['value'] = $value;
            $this->attribs['label'] = $label;
            $html .= $radio(array(
                'name'    => $this->name,
                'value'   => $this->value,
                'attribs' => $this->attribs
            ));
        }
        return $html;
    }
}
