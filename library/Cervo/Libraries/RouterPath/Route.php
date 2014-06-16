<?php

/**
 *
 * Copyright (c) 2013 Marc André "Manhim" Audet <root@manhim.net>. All rights reserved.
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



/**
 * Used in Router. Each of the module's routes are RouterPath objects.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Route extends \Cervo\Libraries\RouterPath
{
    const M_ANY = 0b1111111;
    const M_HTTP_GET = 0b1;
    const M_HTTP_POST = 0b10;
    const M_HTTP_PUT = 0b100;
    const M_HTTP_DELETE = 0b1000;
    const M_HTTP_UPDATE = 0b10000;
    const M_HTTP_PATCH = 0b100000;
    const M_CLI = 0b1000000;

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
     * The http method to match against.
     * @var int
     */
    protected $http_method;

    /**
     * The paramters to pass.
     * @var array
     */
    protected $params = [];

    /**
     * Set the path, the module, the controller and the method.
     * Sanitize the path and compute the regex.
     *
     * @param string $path
     * @param string $controller
     * @param int    $http_method
     * @param array  $params
     *
     * @throws InvalidControllerException
     */
    public function __construct($path, $controller, $http_method = self::M_ALL, $params = [])
    {
        $controller_e = explode('/', $controller);
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
        $this->http_method = $http_method;
        $this->params = $params;

        parent::__construct($path);
    }

    public function compare($path)
    {
        if ($this->http_method !== self::M_ANY)
        {
            if (defined('STDIN'))
            {
                if ($this->http_method & self::M_CLI !== self::M_CLI)
                    return \Cervo\Libraries\RouterPath::NO_MATCH;
            }
            else
            {
                // TODO: Should probably encapsulate the $_SERVER variables in an object (Request)
                switch ($_SERVER['REQUEST_METHOD'])
                {
                    case 'GET':
                        if (($this->http_method & self::M_HTTP_GET) !== self::M_HTTP_GET)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    case 'POST':
                        if (($this->http_method & self::M_HTTP_POST) !== self::M_HTTP_POST)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    case 'PUT':
                        if (($this->http_method & self::M_HTTP_PUT) !== self::M_HTTP_PUT)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    case 'DELETE':
                        if (($this->http_method & self::M_HTTP_DELETE) !== self::M_HTTP_DELETE)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    case 'UPDATE':
                        if (($this->http_method & self::M_HTTP_UPDATE) !== self::M_HTTP_UPDATE)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    case 'PATCH':
                        if (($this->http_method & self::M_HTTP_PATCH) !== self::M_HTTP_PATCH)
                            return \Cervo\Libraries\RouterPath::NO_MATCH;
                        break;
                    default:
                        return \Cervo\Libraries\RouterPath::NO_MATCH;
                }
            }
        }

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

    public function getHttpMethod()
    {
        return $this->http_method;
    }

    public function getParams()
    {
        return $this->params;
    }
}
