<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2023 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2023 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  MIT
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

declare(strict_types=1);

namespace Cervo;

use Cervo\Config\BaseConfig;
use Cervo\Exceptions\Router\InvalidProviderException;
use Cervo\Interfaces\ProviderInterface;
use Cervo\Utils\ClassUtils;

/**
 * Context class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Context
{
    private BaseConfig $config;
    private ModulesManager $modulesManager;
    private Singletons $singletons;

    /**
     * Context constructor.
     *
     * @param BaseConfig|null $config
     */
    public function __construct(?BaseConfig $config = null)
    {
        $this->config = $config ?? new BaseConfig();

        $this->modulesManager = new ModulesManager();
        $this->singletons = new Singletons($this);

    }

    /**
     * Register and run a Provider class
     *
     * @param string $providerClass The Provider class
     *
     * @return Context
     */
    public function register(string $providerClass): self
    {
        if (!ClassUtils::implements($providerClass, ProviderInterface::class)) {
            throw new InvalidProviderException;
        }

        (new $providerClass($this))();

        return $this;
    }

    public function getConfig(): BaseConfig
    {
        return $this->config;
    }

    public function getModulesManager(): ModulesManager
    {
        return $this->modulesManager;
    }

    public function getSingletons(): Singletons
    {
        return $this->singletons;
    }
}
