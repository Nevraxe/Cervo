<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2018 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2018 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  BSD-2-Clauses
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

namespace Cervo;

use Cervo\Config\BaseConfig;

/**
 * Core class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Core
{
    private $context = null;

    public function __construct(?BaseConfig $config = null)
    {
        $this->context = new Context($config);
    }

    public function init()
    {
        /** @var Events $events */
        $events = $this->context->getSingletons()->get(Events::class);

        /** @var Router $router */
        $router = $this->context->getSingletons()->get(Router::class);

        foreach ($this->context->getModulesManager()->getAllModules() as [$vendor_name, $module_name, $path]) {
            $events->loadPath($path);
            $router->loadPath($path);
        }

        $events->fire('Cervo/System/Before');

        $route = $router->dispatch();

        $events->fire('Cervo/Route/Before');
        (new ControllerReflection($this->context, $route))();
        $events->fire('Cervo/Route/After');

        $events->fire('Cervo/System/After');
    }

    public function getContext()
    {
        return $this->context;
    }
}
