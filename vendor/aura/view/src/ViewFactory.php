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
     * @return View
     *
     */
    public function newInstance($helpers = null)
    {
        if (! $helpers) {
            $helpers = new HelperRegistry;
        }

        return new View(
            new TemplateRegistry,
            new TemplateRegistry,
            $helpers
        );
    }
}
