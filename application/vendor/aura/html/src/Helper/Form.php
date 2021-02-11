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
 * Helper to generate an opening form tag.
 *
 * @package Aura.Html
 *
 */
class Form extends AbstractHelper
{
    /**
     *
     * Helper to generate an opening form tag.
     *
     * @param array $attr Attributes for the form tag.
     *
     * @return string
     *
     */
    public function __invoke(array $attr = array())
    {
        $base = array(
            'id' => null,
            'method' => 'post',
            'action' => null,
            'enctype' => 'multipart/form-data',
        );

        $attr = $this->escaper->attr(array_merge($base, $attr));
        return "<form $attr>";
    }
}
