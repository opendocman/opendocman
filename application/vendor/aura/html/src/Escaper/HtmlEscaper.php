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
 * An escaper for HTML output.
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 * @package Aura.Html
 *
 */
class HtmlEscaper extends AbstractEscaper
{
    /**
     *
     * Flags for `htmlspecialchars()`.
     *
     * @var mixed
     *
     */
    protected $flags = ENT_QUOTES;

    /**
     *
     * Constructor.
     *
     * @param int $flags Flags for `htmlspecialchars()`.
     *
     * @param string $encoding The encoding to use for raw and escaped strings.
     *
     */
    public function __construct($flags = null, $encoding = null)
    {
        if ($flags !== null) {
            // use custom flags only
            $this->setFlags($flags);
        } elseif (defined('ENT_SUBSTITUTE')) {
            // add ENT_SUBSTITUTE if available (PHP 5.4)
            $this->setFlags(ENT_QUOTES | ENT_SUBSTITUTE);
        }

        parent::__construct($encoding);
    }

    /**
     *
     * Escapes an HTML value.
     *
     * @param mixed $raw The value to be escaped.
     *
     * @return string The escaped value.
     *
     */
    public function __invoke($raw)
    {
        $raw = (string) $raw;

        // pre-empt escaping
        if ($raw === '' || ctype_digit($raw)) {
            return $raw;
        }

        // return the escaped string
        return htmlspecialchars(
            $raw,
            $this->flags,
            $this->encoding
        );
    }

    /**
     *
     * Sets the flags for `htmlspecialchars()`.
     *
     * @param int $flags The flags for `htmlspecialchars()`.
     *
     * @return null
     *
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     *
     * Gets the flags for `htmlspecialchars()`.
     *
     * @return int
     *
     */
    public function getFlags()
    {
        return $this->flags;
    }
}
