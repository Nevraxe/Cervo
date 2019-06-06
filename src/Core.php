<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2019 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2019 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  MIT
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

declare(strict_types=1);

namespace Cervo;

use Cervo\Config\BaseConfig;
use Cervo\Exceptions\ControllerReflection\AlreadyInitialisedException;


/**
 * Core class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Core
{
    /** @var bool */
    private $isInit = false;

    /** @var Context|null */
    private $context = null;

    /** @var Core|null */
    private static $core = null;

    public function __construct(?BaseConfig $config = null)
    {
        $this->context = new Context($config);
        self::$core = $this;
    }

    public static function get(): ?Core
    {
        return self::$core;
    }

    public function start()
    {
        if ($this->isInit === true) {
            throw new AlreadyInitialisedException;
        }

        $this->isInit = true;

        /** @var Events $events */
        $events = $this->getContext()->getSingletons()->get(Events::class);

        /** @var Router $router */
        $router = $this->getContext()->getSingletons()->get(Router::class);

        foreach ($this->getContext()->getModulesManager()->getAllModules() as [$vendor_name, $module_name, $path]) {
            $events->loadPath($path);
            $router->loadPath($path);
        }

        $events->fire('Cervo/System/Before');

        $route = $router->dispatch();

        $events->fire('Cervo/Route/Before');
        (new ControllerReflection($this->getContext(), $route))();
        $events->fire('Cervo/Route/After');

        $events->fire('Cervo/System/After');
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }
}
