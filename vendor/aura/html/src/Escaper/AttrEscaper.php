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
 * An escaper for unquoted HTML attribute output.
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 * @package Aura.Html
 *
 */
class AttrEscaper extends AbstractEscaper
{
    /**
     *
     * HTML entities mapped from ord() values.
     *
     * @var array
     *
     */
    protected $entities = array(
        34 => '&quot;',
        38 => '&amp;',
        60 => '&lt;',
        62 => '&gt;',
    );

    /**
     *
     * An HTML escaper.
     *
     * @var HtmlEscaper
     *
     */
    protected $html;

    /**
     *
     * Constructor.
     *
     * @param HtmlEscaper $html An HTML escaper.
     *
     * @param string $encoding The encoding to use for raw and escaped strings.
     *
     */
    public function __construct(HtmlEscaper $html, $encoding = null)
    {
        $this->html = $html;
        parent::__construct($encoding);
    }

    /**
     *
     * Gets the HTML escaper.
     *
     * @return HtmlEscaper
     *
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     *
     * Escapes an unquoted HTML attribute, or converts an array of such
     * attributes to a quoted-and-escaped attribute string.
     *
     * When converting associative array of HTML attributes to an escaped
     * attribute string, keys are attribute names, and values are attribute
     * values. A value of boolean true indicates a minimized attribute. For
     *  example, `['disabled' => 'disabled']` results in `disabled="disabled"`,
     * but `['disabled' => true]` results in `disabled`.  Values of `false` or
     * `null` will omit the attribute from output.  Array values will be
     * concatenated and space-separated before escaping.
     *
     * @param mixed $raw The attribute (or array of attributes) to escaped.
     *
     * @return string The escapted attribute string.
     *
     */
    public function __invoke($raw)
    {
        if (! is_array($raw)) {
            return $this->replace($raw, '/[^a-z0-9,\.\-_]/iSu');
        }

        $esc = '';
        foreach ($raw as $key => $val) {

            // do not add null and false values
            if ($val === null || $val === false) {
                continue;
            }

            // get rid of extra spaces in the key
            $key = trim($key);

            // concatenate and space-separate multiple values
            if (is_array($val)) {
                $val = implode(' ', $val);
            }

            // what kind of attribute representation?
            if ($val === true) {
                // minimized
                $esc .= $this->__invoke($key);
            } else {
                // full; because the it is quoted, we can use html ecaping
                $esc .= $this->__invoke($key) . '="'
                      . $this->html->__invoke($val) . '"';
            }

            // space separator
            $esc .= ' ';
        }

        // done; remove the last space
        return rtrim($esc);
    }

    /**
     *
     * Replaces unsafe HTML attribute characters.
     *
     * @param array $matches Matches from preg_replace_callback().
     *
     * @return string Escaped characters.
     *
     */
    protected function replaceMatches($matches)
    {
        $chr = $matches[0];

        if ($this->charIsUndefined($chr)) {
            // use the Unicode replacement char
            return '&#xFFFD;';
        }

        return $this->replaceDefined($chr);
    }

    /**
     *
     * Is a character undefined in HTML?
     *
     * @param string $chr The character to test.
     *
     * @return bool
     *
     */
    protected function charIsUndefined($chr)
    {
        $ord = ord($chr);
        return ($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r")
              || ($ord >= 0x7f && $ord <= 0x9f);
    }

    /**
     *
     * Replace a character defined in HTML.
     *
     * @param string $chr The character to replace.
     *
     * @return string
     *
     */
    protected function replaceDefined($chr)
    {
        $ord = $this->getHexOrd($chr);

        // is this a mapped entity?
        if (isset($this->entities[$ord])) {
            return $this->entities[$ord];
        }

        // is this an upper-range hex entity?
        if ($ord > 255) {
            return sprintf('&#x%04X;', $ord);
        }

        // everything else
        return sprintf('&#x%02X;', $ord);
    }
}
