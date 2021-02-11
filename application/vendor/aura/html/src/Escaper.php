<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html;

use Aura\Html\Escaper\HtmlEscaper;
use Aura\Html\Escaper\AttrEscaper;
use Aura\Html\Escaper\CssEscaper;
use Aura\Html\Escaper\JsEscaper;

/**
 *
 * A proxy for the various escapers.
 *
 * @package Aura.Html
 *
 */
class Escaper
{
    /**
     *
     * A singleton instance of an Escaper, typically for view scripts.
     *
     * @var Escaper
     *
     */
    static protected $escaper;

    /**
     *
     * An HtmlEscaper instance.
     *
     * @var HtmlEscaper
     *
     */
    protected $html;

    /**
     *
     * An AttrEscaper instance.
     *
     * @var AttrEscaper
     *
     */
    protected $attr;

    /**
     *
     * A CssEscaper instance.
     *
     * @var CssEscaper
     *
     */
    protected $css;

    /**
     *
     * A JsEscaper instance.
     *
     * @var JsEscaper
     *
     */
    protected $js;

    /**
     *
     * Constructor.
     *
     * @param HtmlEscaper $html An HtmlEscaper instance.
     *
     * @param AttrEscaper $attr An AttrEscaper instance.
     *
     * @param CssEscaper $css A CssEscaper instance.
     *
     * @param JsEscaper $js A JsEscaper instance.
     *
     */
    public function __construct(
        HtmlEscaper $html,
        AttrEscaper $attr,
        CssEscaper $css,
        JsEscaper $js
    ) {
        $this->html = $html;
        $this->attr = $attr;
        $this->css = $css;
        $this->js = $js;
    }

    /**
     *
     * Read-only property access; mostly for testing.
     *
     * @param string $key The property name.
     *
     * @return mixed
     *
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     *
     * Allows this object to be used as a helper.
     *
     * @return self
     *
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     *
     * Sets the encoding on all escapers.
     *
     * @param string $encoding The encoding to use.
     *
     * @return null
     *
     */
    public function setEncoding($encoding)
    {
        $this->html->setEncoding($encoding);
        $this->attr->setEncoding($encoding);
        $this->css->setEncoding($encoding);
        $this->js->setEncoding($encoding);
    }

    /**
     *
     * Sets the flags for `htmlspecialchars()` on the Html and Attr escapers.
     *
     * @param int $flags The `htmlspecialchars()` flags.
     *
     * @return null
     *
     */
    public function setFlags($flags)
    {
        $this->html->setFlags($flags);
        $this->attr->getHtml()->setFlags($flags);
    }

    /**
     *
     * Escapes for HTML body and quoted HTML attribute context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public function html($raw)
    {
        return $this->html->__invoke($raw);
    }

    /**
     *
     * Escapes for unquoted HTML attribute context.
     *
     * @param string|array $raw The raw attribute (or array of attributes).
     *
     * @return string The escaped string.
     *
     */
    public function attr($raw)
    {
        return $this->attr->__invoke($raw);
    }

    /**
     *
     * Escapes for CSS context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public function css($raw)
    {
        return $this->css->__invoke($raw);
    }

    /**
     *
     * Escapes for JavaScript context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public function js($raw)
    {
        return $this->js->__invoke($raw);
    }

    /**
     *
     * Sets the static singleton escaper instance.
     *
     * @param Escaper $escaper The Escaper to use as the singleton.
     *
     * @return null
     *
     */
    public static function setStatic(Escaper $escaper)
    {
        static::$escaper = $escaper;
    }

    /**
     *
     * Gets the static singleton escaper instance.
     *
     * @return Escaper
     *
     */
    public static function getStatic()
    {
        return static::$escaper;
    }

    /**
     *
     * Static escaping for HTML body and quoted HTML attribute context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public static function h($raw)
    {
        return static::$escaper->html($raw);
    }

    /**
     *
     * Static escaping for unquoted HTML attribute context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public static function a($raw)
    {
        return static::$escaper->attr($raw);
    }

    /**
     *
     * Static escaping for CSS context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public static function c($raw)
    {
        return static::$escaper->css($raw);
    }

    /**
     *
     * Static escaping for JavaScript context.
     *
     * @param string $raw The raw string.
     *
     * @return string The escaped string.
     *
     */
    public static function j($raw)
    {
        return static::$escaper->js($raw);
    }
}
