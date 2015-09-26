<?php


/**
 *
 * Copyright (c) 2015 Marc André "Manhim" Audet <root@manhim.net>. All rights reserved.
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


namespace Cervo\Libraries\RouterPath;


use Cervo\Libraries\Exceptions\InvalidControllerException;
use Cervo\Libraries\RouterPath;


/**
 * Used in Router. Each of the module's routes are RouterPath objects.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Route extends RouterPath
{
    /**
     * The route's module.
     * @var string
     */
    protected $module;

    /**
     * The route's controller.
     * @var string
     */
    protected $controller;

    /**
     * The route's method.
     * @var string
     */
    protected $method;

    /**
     * The paramters to pass.
     * @var array
     */
    protected $params = [];

    /**
     * Set the path, the module, the controller and the method.
     * Sanitize the path and compute the regex.
     *
     * @param string          $path
     * @param string|callable $method_path
     * @param int             $http_method
     * @param array           $params
     *
     * @throws InvalidControllerException
     */
    public function __construct($path, $method_path, $http_method = RouterPath::M_ANY, $params = [])
    {
        if (is_callable($method_path))
        {
            $method_path = $method_path();
        }

        $controller_e = explode('/', $method_path);
        $c_controller_e = count($controller_e);

        if ($c_controller_e < 3)
        {
            throw new InvalidControllerException;
        }
        else
        {
            $module = $controller_e[0];
            $controller_p = implode('/', array_slice($controller_e, 1, $c_controller_e - 2));
            $method = $controller_e[$c_controller_e - 1];
        }

        $this->module = $module;
        $this->controller = $controller_p;
        $this->method = $method;
        $this->params = $params;

        parent::__construct($path, $http_method);
    }

    /**
     * Compare the input path to the regex.
     *
     * @param string $path
     *
     * @return bool
     */
    public function compare($path)
    {
        return parent::compare($path);
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

    public function getParams()
    {
        return $this->params;
    }
}
