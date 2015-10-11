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


namespace Cervo\Libraries;


use Cervo\Core as _;
use Cervo\Libraries\Exceptions\RouteNotFoundException;
use FastRoute\RouteCollector;
use FastRoute\RouteParser as RouteParser;
use FastRoute\DataGenerator as DataGenerator;
use FastRoute\Dispatcher as Dispatcher;


/**
 * Route manager for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Router
{
    private $routeCollector;
    private $dispatcher;

    public function __construct()
    {
        $config = _::getLibrary('Cervo/Config');

        $this->routeCollector = new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
        );

        $this->dispatcher = new Dispatcher\GroupCountBased($this->routeCollector);

        foreach (glob($config->get('Cervo/Application/Directory') . '*' . \DS . 'router.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {
            $function = require $file;

            if (is_callable($function)) {
                $function($this);
            }
        }
    }

    public function dispatch()
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
            default:

                throw new RouteNotFoundException;

                break;
            case Dispatcher::FOUND:

                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                $middleware = $handler['middleware'];

                if (is_callable($middleware)) {
                    if (!$middleware($this)) {
                        throw new RouteNotFoundException;
                    }
                }

                return new Route($handler['method_path'], $handler['parameters'], $vars);

                break;
        }
    }

    public function addRoute($httpMethod, $route, $method_path, $middleware = null, $parameters = [])
    {
        $this->routeCollector->addRoute($httpMethod, $route, [
            'method_path' => $method_path,
            'middleware' => $middleware,
            'parameters' => $parameters
        ]);
    }
}
