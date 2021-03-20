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
 * Helper for a series of <link ... /> tags.
 *
 * @package Aura.Html
 *
 */
class Links extends AbstractSeries
{
    /**
     *
     * Adds a <link ... > tag to the series.
     *
     * @param array $attr Attributes for the <link> tag.
     *
     * @param int $pos The link position in the series.
     *
     * @return self
     *
     */
    public function add(array $attr, $pos = 100)
    {
        $this->addElement($pos, $this->void('link', $attr));
        return $this;
    }
}
