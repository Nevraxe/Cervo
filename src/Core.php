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

    /** @var Context|null */
    private static $global_context = null;

    public function __construct(?BaseConfig $config = null)
    {
        $this->context = new Context($config);
        self::$global_context = $this->context;
    }

    public static function get(): ?Context
    {
        return self::$global_context;
    }

    public function start()
    {
        if ($this->isInit === true) {
            throw new AlreadyInitialisedException;
        }

        $this->isInit = true;

        /** @var Router $router */
        $router = $this->getContext()->getSingletons()->get(Router::class);

        foreach ($this->getContext()->getModulesManager()->getAllModules() as [$vendor_name, $module_name, $path]) {
            $router->loadPath($path);
        }

        (new ControllerReflection($this->getContext(), $router->dispatch()))();
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }
}
