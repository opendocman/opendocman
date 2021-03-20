<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html;

use Aura\Html\Escaper;
use Aura\Html\Escaper\HtmlEscaper;
use Aura\Html\Escaper\AttrEscaper;
use Aura\Html\Escaper\CssEscaper;
use Aura\Html\Escaper\JsEscaper;

/**
 *
 * Factory to create an Escaper object.
 *
 * @package Aura.Html
 *
 */
class EscaperFactory
{
    /**
     *
     * The encoding for the escapers.
     *
     * @var string
     *
     */
    protected $encoding;

    /**
     *
     * The `htmlspecialchars()` flags for the escapers.
     *
     * @var int
     *
     */
    protected $flags;

    /**
     *
     * Constructor.
     *
     * @param string $encoding The encoding for the escapers.
     *
     * @param int $flags The `htmlspecialchars()` flags for the escapers.
     *
     */
    public function __construct($encoding = null, $flags = null)
    {
        $this->encoding = $encoding;
        $this->flags = $flags;
    }

    /**
     *
     * Creates a new Escaper object.
     *
     * @param string $encoding The encoding for the escapers.
     *
     * @param int $flags The `htmlspecialchars()` flags for the escapers.
     *
     * @return Escaper
     *
     */
    public function newInstance()
    {
        $html = new HtmlEscaper($this->flags, $this->encoding);
        $attr = new AttrEscaper($html, $this->encoding);
        $css = new CssEscaper($this->encoding);
        $js = new JsEscaper($this->encoding);
        return new Escaper($html, $attr, $css, $js);
    }
}
