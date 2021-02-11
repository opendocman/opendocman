<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html\Escaper;

use Aura\Html\Exception;

/**
 *
 * An asbtract escaper for output.
 *
 * Based almost entirely on Zend\Escaper by Padraic Brady et al. and modified
 * for conceptual integrity with the rest of Aura.  Some portions copyright
 * (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * under the New BSD License (http://framework.zend.com/license/new-bsd).
 *
 * @package Aura.Html
 *
 */
abstract class AbstractEscaper
{
    /**
     *
     * Encoding for raw and escaped values.
     *
     * @var string
     *
     */
    protected $encoding = 'utf-8';

    /**
     *
     * All supported encodings.
     *
     * @var array
     *
     */
    protected $supported_encodings = array(
        '1251', '1252', '866', '932', '936', '950', 'big5', 'big5-hkscs',
        'cp1251', 'cp1252', 'cp866', 'cp932', 'euc-jp', 'eucjp', 'eucjp-win',
        'gb2312', 'ibm866', 'iso-8859-1', 'iso-8859-15', 'iso-8859-5',
        'iso-8859-1', 'iso-8859-15', 'iso-8859-5', 'koi8-r', 'koi8-ru', 'koi8r',
        'macroman', 'shift_jis', 'sjis', 'sjis-win', 'utf-8', 'win-1251',
        'windows-1251', 'windows-1252',
    );

    /**
     *
     * Constructor.
     *
     * @param string $encoding The encoding to use for raw and escaped strings.
     *
     */
    public function __construct($encoding = null)
    {
        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
    }

    /**
     *
     * Main function to escape output.
     *
     * @param mixed $raw The raw value to escape.
     *
     */
    abstract public function __invoke($raw);

    /**
     *
     * Sets the encoding for raw and escaped strings.
     *
     * @param string $encoding The encoding.
     *
     * @return null
     *
     */
    public function setEncoding($encoding)
    {
        $encoding = strtolower($encoding);
        if (! in_array($encoding, $this->supported_encodings)) {
            throw new Exception\EncodingNotSupported($encoding);
        }
        $this->encoding = $encoding;
    }

    /**
     *
     * Returns the encoding for raw and escaped strings.
     *
     * @return string
     *
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     *
     * Replaces the raw value with an escaped value.
     *
     * @param mixed $raw The raw value.
     *
     * @param string $regex The regex to determine what characters to replace.
     *
     * @return mixed The escaped value.
     *
     */
    protected function replace($raw, $regex)
    {
        // pre-empt replacement
        if ($raw === '' || ctype_digit($raw)) {
            return $raw;
        }

        // escape the string in UTF-8 encoding
        $esc = preg_replace_callback(
            $regex,
            array($this, 'replaceMatches'),
            $this->toUtf8($raw)
        );

        // return using original encoding
        return $this->fromUtf8($esc);
    }

    /**
     *
     * Converts a string to UTF-8 encoding.
     *
     * @param string $str The string to be converted.
     *
     * @return string The UTF-8 string.
     *
     */
    public function toUtf8($str)
    {
        // pre-empt converting
        if ($str === '') {
            return $str;
        }

        // do we need to convert it?
        if ($this->encoding != 'utf-8') {
            // convert to UTF-8
            $str = $this->convert($str, $this->encoding, 'UTF-8');
        }

        // do we have a valid UTF-8 string?
        if (! preg_match('/^./su', $str)) {
            throw new Exception\InvalidUtf8($str);
        }

        // looks ok, return the encoded version
        return $str;
    }

    /**
     *
     * Converts a string from UTF-8.
     *
     * @param string $str The UTF-8 string.
     *
     * @return string The string in its original encoding.
     *
     */
    public function fromUtf8($str)
    {
        if ($this->encoding == 'utf-8') {
            return $str;
        }

        return $this->convert($str, 'UTF-8', $this->encoding);
    }

    /**
     *
     * Converts a string from one encoding to another.
     *
     * @param string $str The string to be converted.
     *
     * @param string $from Convert from this encoding.
     *
     * @param string $to Convert to this encoding.
     *
     * @return string The string in the new encoding.
     *
     */
    protected function convert($str, $from, $to)
    {
        if (function_exists('iconv')) {
            return (string) iconv($from, $to, $str);
        }

        if (function_exists('mb_convert_encoding')) {
            return (string) mb_convert_encoding($str, $to, $from);
        }

        $message = "Extension 'iconv' or 'mbstring' is required.";
        throw new Exception\ExtensionNotInstalled($message);
    }

    /**
     *
     * Get the UTF-16BE hexadecimal ordinal value for a character.
     *
     * @param string $chr The character to get the value for.
     *
     * @return string The hexadecimal ordinal value.
     *
     */
    protected function getHexOrd($chr)
    {
        if (strlen($chr) > 1) {
            $chr = $this->convert($chr, 'UTF-8', 'UTF-16BE');
        }
        return hexdec(bin2hex($chr));
    }
}
