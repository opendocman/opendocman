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
 * A generic HTML input value.
 *
 * @package Aura.Html
 *
 */
class Generic extends AbstractInput
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
        // set type attribute to type property, default 'text'
        $this->attribs['type'] = ($this->type) ? $this->type : 'text';

        // only set value if not null
        if ($this->value !== null) {
            $this->attribs['value'] = (string) $this->value;
        }

        // build html
        return $this->indent(0, $this->void('input', $this->attribs));
    }
}
