<?php


/**
 *
 * Copyright (c) 2010-2016 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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
 * DISCLAIMED. IN NO EVENT SHALL MARC ANDRÉ "MANHIM" AUDET BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


namespace Cervo\Libraries;


use Cervo\Exceptions\InvalidControllerException;


/**
 * Route manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Route
{
    /**
     * The route's module
     * @var string
     */
    protected $module;

    /**
     * The route's controller
     * @var string
     */
    protected $controller;

    /**
     * The route's method
     * @var string
     */
    protected $method;

    /**
     * The parameters to pass
     * @var array
     */
    protected $parameters = [];

    /**
     * The arguments to pass
     * @var array
     */
    protected $arguments = [];

    /**
     * Set the method path.
     * Sanitize and split the method_path.
     *
     * @param string $method_path
     * @param array $parameters
     * @param array $arguments
     */
    public function __construct($method_path, $parameters = [], $arguments = [])
    {
        $controller_e = explode('/', $method_path);
        $c_controller_e = count($controller_e);

        if ($c_controller_e < 3) {
            throw new InvalidControllerException;
        }

        $this->module = $controller_e[0];
        $this->controller = implode('/', array_slice($controller_e, 1, $c_controller_e - 2));
        $this->method = $controller_e[$c_controller_e - 1];
        $this->parameters = $parameters;
        $this->arguments = $arguments;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
}