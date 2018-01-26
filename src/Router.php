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


use Cervo\Exceptions\Router\InvalidMiddlewareException;
use Cervo\Exceptions\Router\MethodNotAllowedException;
use Cervo\Exceptions\Router\RouteMiddlewareFailedException;
use Cervo\Exceptions\Router\RouteNotFoundException;
use Cervo\Interfaces\MiddlewareInterface;
use Cervo\Interfaces\SingletonInterface;
use Cervo\Utils\ClassUtils;
use Cervo\Utils\PathUtils;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher as Dispatcher;


/**
 * Routes manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Router implements SingletonInterface
{
    /** @var RouteCollector FastRoute, null if usingCache is set */
    private $routeCollector = null;

    /** @var array List of middlewares called using the middleware() method. */
    private $currentMiddlewares = [];

    /** @var string List of group prefixes called using the group() method. */
    private $currentGroupPrefix;

    /** @var Context The current context */
    private $context;

    /**
     * Router constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->routeCollector = new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
        );

        $this->context = $context;
    }

    /**
     * Load every PHP Routes files under the directory
     *
     * @param string $path
     */
    public function loadPath(string $path) : void
    {
        if (file_exists($path . \DIRECTORY_SEPARATOR . 'Routes')) {

            foreach (
                PathUtils::getRecursivePHPFilesIterator($path . \DIRECTORY_SEPARATOR . 'Routes')
                as $file
            ) {

                $callback = require $file->getPathName();

                if (is_callable($callback)) {
                    $callback($this);
                }

            }

        }
    }

    /**
     * Encapsulate all the routes that are added from $func(Router) with this middleware.
     *
     * If the return value of the middleware is false, throws a RouteMiddlewareFailedException.
     *
     * @param string $middleware_class The middleware to use
     * @param string $method_name The method of the singleton to call
     * @param callable $func
     */
    public function middleware(string $middleware_class, string $method_name, callable $func) : void
    {
        // It's easier to cache an array
        array_push($this->currentMiddlewares, [
            'middleware_class' => $middleware_class,
            'method' => $method_name
        ]);

        $func($this);

        array_pop($this->currentMiddlewares);
    }

    /**
     * Adds a prefix in front of all the encapsulated routes.
     *
     * @param string $prefix The prefix of the group.
     * @param callable $func
     */
    public function group(string $prefix, callable $func) : void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;

        $func($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * Dispatch the request to the router.
     *
     * @return Route
     * @throws MethodNotAllowedException if the request method is not supported, but others are for this route.
     * @throws RouteNotFoundException if the requested route did not match any routes.
     */
    public function dispatch() : Route
    {
        $dispatcher = $this->getDispatcher();

        if (defined('STDIN')) {
            $request_method = 'CLI';
        } else {
            $request_method = $_SERVER['REQUEST_METHOD'];
        }

        $routeInfo = $dispatcher->dispatch($request_method, $this->detectUri());

        if ($routeInfo[0] === Dispatcher::FOUND) {

            $handler = $routeInfo[1];
            $arguments = $routeInfo[2];
            $middlewares = $handler['middlewares'];

            $route = new Route($handler['controller_class'], $handler['parameters'], $arguments);

            if (is_array($middlewares)) {
                $this->handleMiddlewares($middlewares, $route);
            }

            return $route;

        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1]);
        } else {
            throw new RouteNotFoundException;
        }
    }

    /**
     * Add a new route.
     *
     * @param string|string[] $http_method The HTTP method, example: GET, HEAD, POST, PATCH, PUT, DELETE, CLI, etc. Can be an array of values.
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function addRoute($http_method, string $route, string $controller_class, array $parameters = []) : void
    {
        $route = $this->currentGroupPrefix . $route;

        $this->routeCollector->addRoute($http_method, $route, [
            'controller_class' => $controller_class,
            'middlewares' => $this->currentMiddlewares,
            'parameters' => $parameters
        ]);
    }

    /**
     * Add a new route with GET as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function get(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('GET', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with HEAD as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function head(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('HEAD', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with POST as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function post(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('POST', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with PUT as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function put(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('PUT', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with PATCH as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function patch(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('PATCH', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with DELETE as HTTP method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function delete(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('DELETE', $route, $controller_class, $parameters);
    }

    /**
     * Add a new route with CLI as method.
     *
     * @param string $route The route
     * @param string $controller_class The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function cli(string $route, string $controller_class, array $parameters = []) : void
    {
        $this->addRoute('CLI', $route, $controller_class, $parameters);
    }

    /**
     * @return Dispatcher\GroupCountBased
     */
    private function getDispatcher() : Dispatcher\GroupCountBased
    {
        return new Dispatcher\GroupCountBased($this->routeCollector->getData());
    }

    /**
     * Returns a parsable URI
     *
     * @return string
     */
    private function detectUri() : string
    {
        if (php_sapi_name() == 'cli') {
            $args = array_slice($_SERVER['argv'], 1);
            return count($args) > 0 ? '/' . implode('/', $args) : '/';
        }

        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME'])) {
            return '/';
        }

        $parts = preg_split('#\?#i', $this->getBaseUri(), 2);
        $uri = $parts[0];

        if ($uri == '/' || strlen($uri) <= 0) {
            return '/';
        }

        $uri = parse_url($uri, PHP_URL_PATH);
        return '/' . str_replace(['//', '../', '/..'], '/', trim($uri, '/'));
    }

    /**
     * Return the base URI for a request
     *
     * @return string
     */
    private function getBaseUri() : string
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (strlen($_SERVER['SCRIPT_NAME']) > 0) {

            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }

        }

        return $uri;
    }

    /**
     * Throws an exception or return void.
     *
     * @param array $middlewares
     * @param Route $route
     *
     * @return void
     * @throws RouteMiddlewareFailedException if a route middleware returned false.
     * @throws InvalidMiddlewareException if a middleware is invalid.
     */
    private function handleMiddlewares(array $middlewares, Route $route) : void
    {
        foreach ($middlewares as $middleware) {

            if (is_array($middleware) && strlen($middleware['middleware_class']) > 0 && strlen($middleware['method']) > 0) {

                if (!ClassUtils::implements($middleware['middleware_class'], MiddlewareInterface::class)) {
                    throw new InvalidMiddlewareException;
                }

                if (!(new $middleware['middleware_class'])($route)()) {
                    throw new RouteMiddlewareFailedException;
                }

            } else {
                throw new InvalidMiddlewareException;
            }

        }
    }
}
