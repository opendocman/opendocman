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
 * Factory to create View objects.
 *
 * @package Aura.View
 *
 */
class ViewFactory
{
    /**
     *
     * Returns a new View instance.
     *
     * @param object $helpers An arbitrary helper manager for the View; if not
     * specified, uses the HelperRegistry from this package.
     *
     * @param array $view_map A map of explicit template names and locations in View registry.
     *
     * @param array $view_paths A map of filesystem paths to search for templates in View registry.
     *
     * @param array $layout_map A map of explicit template names and locations in Layout registry.
     *
     * @param array $layout_paths A map of filesystem paths to search for templates in Layout registry.
     *
     * @return View
     *
     */
    public function newInstance(
        $helpers = null,
        $view_map = [],
        $view_paths = [],
        $layout_map = [],
        $layout_paths = []
    ) {
        if (! $helpers) {
            $helpers = new HelperRegistry;
        }

        return new View(
            new TemplateRegistry($view_map, $view_paths),
            new TemplateRegistry($layout_map, $layout_paths),
            $helpers
        );
    }
}
