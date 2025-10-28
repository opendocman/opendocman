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
 * Helper for a series of <meta ... /> tags.
 *
 * @package Aura.Html
 *
 */
class Metas extends AbstractSeries
{
    /**
     *
     * Adds a `<meta ...>` tag to the series.
     *
     * @param array $attr Attributes for the <link> tag.
     *
     * @param int $pos The meta position in the series.
     *
     * @return self
     *
     */
    public function add(array $attr = array(), $pos = 100)
    {
        $this->addElement($pos, $this->void('meta', $attr));
        return $this;
    }

    /**
     *
     * Returns a `<meta http-equiv="" content="">` tag.
     *
     * @param string $http_equiv The http-equiv type.
     *
     * @param string $content The content value.
     *
     * @param int $pos The meta position in the series.
     *
     * @return self
     *
     */
    public function addHttp($http_equiv, $content, $pos = 100)
    {
        $attr = array(
            'http-equiv' => $http_equiv,
            'content'    => $content,
        );

        return $this->add($attr, $pos);
    }

    /**
     *
     * Returns a `<meta name="" content="">` tag.
     *
     * @param string $name The name value.
     *
     * @param string $content The content value.
     *
     * @param int $pos The meta position in the series.
     *
     * @return self
     *
     */
    public function addName($name, $content, $pos = 100)
    {
        $attr = array(
            'name'    => $name,
            'content' => $content,
        );

        return $this->add($attr, $pos);
    }
}
