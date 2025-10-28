<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Helper;

use Aura\Html\HelperLocator;

/**
 *
 * A helper to generate form input elements.
 *
 * @package Aura.Html
 *
 */
class Input extends HelperLocator
{
    /**
     *
     * Given an input specification, returns the HTML for the input.
     *
     * @param array $spec The element specification.
     *
     * @return mixed
     *
     */
    public function __invoke(array $spec = null)
    {
        if ($spec === null) {
            return $this;
        }

        if (empty($spec['type'])) {
            $spec['type'] = 'text';
        }

        if (empty($spec['attribs']['name'])) {
            $spec['attribs']['name'] = $spec['name'];
        }

        $input = $this->get($spec['type']);
        return $input($spec);
    }
}
