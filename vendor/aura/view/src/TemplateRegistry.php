<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\View;

/**
 *
 * A registry for templates.
 *
 * @package Aura.View
 *
 */
class TemplateRegistry
{
    /**
     *
     * The map of explicit template names and locations.
     *
     * @var array
     *
     */
    protected $map = array();

    /**
     *
     * The paths to search for implicit template names.
     *
     * @var array
     *
     */
    protected $paths = array();

    /**
     *
     * Templates found in the search paths.
     *
     * @var array
     *
     */
    protected $found = array();

    /**
     *
     * Constructor.
     *
     * @param array $map A map of explicit template names and locations.
     *
     * @param array $paths A map of filesystem paths to search for templates.
     *
     */
    public function __construct(
        array $map = array(),
        array $paths = array()
    ) {
        foreach ($map as $name => $spec) {
            $this->set($name, $spec);
        }
        $this->setPaths($paths);
    }

    /**
     *
     * Registers a template.
     *
     * If the template is a string, it is treated as a path to a PHP include
     * file, and gets wrapped inside a closure that includes that file.
     * Otherwise the template is treated as a callable.
     *
     * @param string $name Register the template under this name.
     *
     * @param string|callable $spec A string path to a PHP include file, or a
     * callable.
     *
     * @return null
     *
     */
    public function set($name, $spec)
    {
        if (is_string($spec)) {
            $spec = $this->enclose($spec);
        }
        $this->map[$name] = $spec;
    }

    /**
     *
     * Is a named template registered?
     *
     * @param string $name The template name.
     *
     * @return bool
     *
     */
    public function has($name)
    {
        return isset($this->map[$name]) || $this->find($name);
    }

    /**
     *
     * Gets a template from the registry.
     *
     * @param string $name The template name.
     *
     * @return \Closure
     *
     */
    public function get($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name];
        }

        if ($this->find($name)) {
            return $this->found[$name];
        }

        throw new Exception\TemplateNotFound($name);
    }

    /**
     *
     * Gets a copy of the current search paths.
     *
     * @return array
     *
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     *
     * Adds one path to the top of the search paths.
     *
     *     $registry->prependPath('/path/1');
     *     $registry->prependPath('/path/2');
     *     $registry->prependPath('/path/3');
     *     // $this->getPaths() reveals that the directory search
     *     // order will be '/path/3/', '/path/2/', '/path/1/'.
     *
     * @param array|string $path The directories to add to the paths.
     *
     * @return void
     *
     */
    public function prependPath($path)
    {
        array_unshift($this->paths, rtrim($path, DIRECTORY_SEPARATOR));
        $this->found = [];
    }

    /**
     *
     * Adds one path to the end of the search paths.
     *
     *     $registry->appendPath('/path/1');
     *     $registry->appendPath('/path/2');
     *     $registry->appendPath('/path/3');
     *     // $registry->getPaths() reveals that the directory search
     *     // order will be '/path/1/', '/path/2/', '/path/3/'.
     *
     * @param array|string $path The directories to add to the paths.
     *
     * @return void
     *
     */
    public function appendPath($path)
    {
        $this->paths[] = rtrim($path, DIRECTORY_SEPARATOR);
        $this->found = [];
    }

    /**
     *
     * Sets the paths directly.
     *
     *      $registry->setPaths([
     *          '/path/1',
     *          '/path/2',
     *          '/path/3',
     *      ]);
     *      // $registry->getPaths() reveals that the search order will
     *      // be '/path/1', '/path/2', '/path/3'.
     *
     * @param array $paths The paths to set.
     *
     * @return void
     *
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        $this->found = [];
    }

    /**
     *
     * Finds a template in the search paths.
     *
     * @param string $name The template name.
     *
     * @return bool True if found, false if not.
     *
     */
    protected function find($name)
    {
        if (isset($this->found[$name])) {
            return true;
        }

        foreach ($this->paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $name . '.php';
            if ($this->isReadable($file)) {
                $this->found[$name] = $this->enclose($file);
                return true;
            }
        }

        return false;
    }

    /**
     *
     * Checks to see if a file is readable.
     *
     * @param string $file The file to find.
     *
     * @return bool
     *
     */
    protected function isReadable($file)
    {
        return is_readable($file);
    }

    /**
     *
     * Wraps a template file name in a Closure.
     *
     * @param string $__FILE__ The file name.
     *
     * @return \Closure
     *
     */
    protected function enclose($__FILE__)
    {
        return function (array $__VARS__ = array()) use ($__FILE__) {
            extract($__VARS__, EXTR_SKIP);
            require $__FILE__;
        };
    }
}
