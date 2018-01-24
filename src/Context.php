<?php


/**
 *
 * Copyright (c) 2010-2018 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *   1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 *
 *   2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL NEVRAXE INC. & MARC ANDRÉ AUDET BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


namespace Cervo;


use Cervo\Config\BaseConfig;
use Cervo\Exceptions\Router\InvalidProviderException;
use Cervo\Interfaces\ProviderInterface;


/**
 * Context class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Context
{
    private $config;
    private $autoloader;
    private $modulesManager;
    private $singletons;

    /**
     * Context constructor.
     *
     * @param BaseConfig|null $config
     */
    public function __construct(
        ?BaseConfig $config = null
    ) {
        $this->config = $config ?? new BaseConfig();

        $this->modulesManager = new ModulesManager();
        $this->autoloader = new Autoloader($this);
        $this->singletons = new Singletons($this);
    }

    /**
     * Register and run a Provider class
     *
     * @param string $provider_class The Provider class
     *
     * @return Context
     */
    public function register(string $provider_class) : self
    {
        if (!is_subclass_of($provider_class, ProviderInterface::class)) {
            throw new InvalidProviderException;
        }

        (new $provider_class)($this)();

        return $this;
    }

    public function getConfig() : BaseConfig
    {
        return $this->config;
    }

    public function getModulesManager() : ModulesManager
    {
        return $this->modulesManager;
    }

    public function getAutoloader() : Autoloader
    {
        return $this->autoloader;
    }

    public function getSingletons() : Singletons
    {
        return $this->singletons;
    }
}
