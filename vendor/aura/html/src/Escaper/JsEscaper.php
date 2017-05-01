<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Escaper;

/**
 *
 * An escaper for JavaScript output.
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 * @package Aura.Html
 *
 */
class JsEscaper extends AbstractEscaper
{
    /**
     *
     * Escapes a JavaScript value.
     *
     * @param mixed $raw The value to be escaped.
     *
     * @return mixed The escaped value.
     *
     */
    public function __invoke($raw)
    {
        return $this->replace($raw, '/[^a-z0-9,\._]/iSu');
    }

    /**
     *
     * Replaces unsafe JavaScript characters.
     *
     * @param array $matches Matches from preg_replace_callback().
     *
     * @return string Escaped characters.
     *
     */
    protected function replaceMatches($matches)
    {
        // get the character
        $chr = $matches[0];

        // is it UTF-8?
        if (strlen($chr) == 1) {
            // yes
            return sprintf('\\x%02X', ord($chr));
        } else {
            // no
            $chr = $this->convert($chr, 'UTF-8', 'UTF-16BE');
            return sprintf('\\u%04s', strtoupper(bin2hex($chr)));
        }
    }
}
