<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Html;

/**
 *
 * A ServiceLocator implementation for loading and retaining helper objects.
 *
 * @package Aura.Html
 *
 */
class HelperLocator
{
    /**
     *
     * A map of helper factories.
     *
     * @var array
     *
     */
    protected $map = array();

    /**
     *
     * The helper object instances.
     *
     * @var array
     *
     */
    protected $helpers = array();

    /**
     *
     * Constructor.
     *
     * @param array $map An array of key-value pairs where the key is the
     * helper name and the value is a callable that returns a helper object.
     *
     */
    public function __construct(array $map = array())
    {
        $this->map = $map;
    }

    /**
     *
     * Magic call to make the helper objects available as methods.
     *
     * @param string $name A helper name.
     *
     * @param array $args Arguments to pass to the helper.
     *
     * @return mixed
     *
     */
    public function __call($name, $args)
    {
        return call_user_func_array(
            $this->get($name),
            $args
        );
    }

    /**
     *
     * Sets a helper object factory into the map.
     *
     * @param string $name The helper name.
     *
     * @param callable $callable A callable to create the helper object.
     *
     * @return null
     *
     */
    public function set($name, $callable)
    {
        $this->map[$name] = $callable;
        unset($this->helpers[$name]);
    }

    /**
     *
     * Does a named helper exist in the locator?
     *
     * @param string $name The helper name.
     *
     * @return bool
     *
     */
    public function has($name)
    {
        return isset($this->map[$name]);
    }

    /**
     *
     * Returns a helper object instance, using the map to factory it if needed.
     *
     * @param string $name The helper to retrieve.
     *
     * @return object
     *
     */
    public function get($name)
    {
        if (! $this->has($name)) {
            throw new Exception\HelperNotFound($name);
        }

        if (! isset($this->helpers[$name])) {
            $factory = $this->map[$name];
            $this->helpers[$name] = $factory();
        }

        return $this->helpers[$name];
    }
}
