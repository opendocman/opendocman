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
     * The namespaced paths to search for implicit template names.
     *
     * @var array
     *
     */
    protected $namespaces = array();

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
     * File extension to use when searching the path list for templates.
     *
     * @var string
     *
     */
    protected $templateFileExtension = '.php';

    /**
     *
     * Constructor.
     *
     * @param array $map A map of explicit template names and locations.
     *
     * @param array $paths A map of filesystem paths to search for templates.
     *
     * @param array $namespaces A map of filesystem paths to search for namespaced templates.
     *
     */
    public function __construct(
        array $map = array(),
        array $paths = array(),
        array $namespaces = array()
    ) {
        foreach ($map as $name => $spec) {
            $this->set($name, $spec);
        }
        $this->setPaths($paths);
        $this->setNamespaces($namespaces);
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
     * Is a namespace registered?
     *
     * @param string $name The namespace.
     *
     * @return bool
     *
     */
    public function hasNamespace($namespace)
    {
        return isset($this->namespaces[$namespace]);
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
     * @param string $path The directories to add to the paths.
     * @param string|null $namespace The directory namespace
     *
     * @return null
     *
     */
    public function prependPath($path, $namespace = null)
    {
        $this->found = [];
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if ($namespace !== null) {
            if (! $this->hasNamespace($namespace)) {
                $this->namespaces[$namespace] = [];
            }
            array_unshift($this->namespaces[$namespace], $path);
            return;
        }
        array_unshift($this->paths, $path);
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
     * @param string|null $namespace The directory namespace
     *
     * @return null
     *
     */
    public function appendPath($path, $namespace = null)
    {
        $this->found = [];
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if ($namespace !== null) {
            if (! $this->hasNamespace($namespace)) {
                $this->namespaces[$namespace] = [];
            }
            $this->namespaces[$namespace][] = $path;
            return;
        }

        $this->paths[] = $path;
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
     * @return null
     *
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        $this->found = [];
    }

    /**
     * Set namespaces directly
     *
     * @param array $namespaces array of namesspaces
     *
     * @return void
     *
     * @access public
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
        $this->found = [];
    }

    /**
     *
     * Sets the extension to be used when searching for templates via find().
     *
     * @param string $templateFileExtension
     *
     * @return null
     *
     */
    public function setTemplateFileExtension($templateFileExtension)
    {
        $this->templateFileExtension = $templateFileExtension;
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

        if ($this->isNamespaced($name)) {
            return $this->findNamespaced($name);
        }

        foreach ($this->paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $name . $this->templateFileExtension;
            if ($this->isReadable($file)) {
                $this->found[$name] = $this->enclose($file);
                return true;
            }
        }

        return false;
    }

    /**
     * Parse namespaced template name
     *
     * @param string $name namespaced template name
     *
     * @return array
     * @throws InvalidArgumentException if invalid template name
     *
     * @access protected
     */
    protected function parseName($name)
    {
        $info  = explode('::', $name);
        $count = count($info);

        if ($count == 1) {
            return array('name' => $info[0]);
        }

        if ($count == 2) {
            return array(
                'namespace' => $info[0],
                'name'      => $info[1]
            );
        }

        throw new \InvalidArgumentException('Invalid name: ' . $name);
    }

    /**
     * Is template name namespaced?
     *
     * @param string $name name to check
     *
     * @return bool
     *
     * @access protected
     */
    protected function isNamespaced($name)
    {
        $info = $this->parseName($name);
        return isset($info['namespace']);;
    }

    /**
     * Fine a namespaced template
     *
     * @param string $name namespaced template name
     *
     * @return bool
     *
     * @access protected
     */
    protected function findNamespaced($name)
    {
        $info = $this->parseName($name);
        $namespace = $info['namespace'];
        $shortname = $info['name'];

        if (! $this->hasNamespace($namespace)) {
            return false;
        }

        $paths = $this->namespaces[$namespace];

        foreach ($paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $shortname . $this->templateFileExtension;
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
