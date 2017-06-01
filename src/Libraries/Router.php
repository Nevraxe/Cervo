<?php


/**
 *
 * Copyright (c) 2010-2017 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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
use Cervo\Exceptions\InvalidMiddlewareException;
use Cervo\Exceptions\InvalidRouterCacheException;
use Cervo\Exceptions\MethodNotAllowedException;
use Cervo\Exceptions\RouteMiddlewareFailedException;
use Cervo\Exceptions\RouteNotFoundException;
use Cervo\Route;
use FastRoute\RouteCollector;
use FastRoute\RouteParser as RouteParser;
use FastRoute\DataGenerator as DataGenerator;
use FastRoute\Dispatcher as Dispatcher;


/**
 * Route manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Router
{
    /**
     * FastRoute, null if usingCache is set
     * @var RouteCollector
     */
    private $routeCollector = null;

    /**
     * FastRoute cache file path.
     * @var string
     */
    private $cacheFilePath;

    /**
     * List of middlewares called using the middleware() method.
     * @var array
     */
    private $currentMiddlewares = [];

    /**
     * List of group prefixes called using the group() method.
     * @var string
     */
    private $currentGroupPrefix;

    /**
     * Initialize the route configurations.
     */
    public function __construct()
    {
        $config = _::getLibrary('Cervo/Config');

        $this->cacheFilePath = $config->get('Cervo/Application/Directory') . \DS . 'router.cache.php';

        $this->routeCollector = new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
        );

        foreach (glob($config->get('Cervo/Application/Directory') . '*' . \DS . 'Router.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            $function = require $file;

            if (is_callable($function)) {
                $function($this);
            }

        }
    }

    /**
     * Encapsulate all the routes that are added from $func(Router) with this middleware.
     *
     * If the return value of the middleware is false, throws a RouteMiddlewareFailedException.
     *
     * @param string $library_name The library to call through \Cervo\Core::getLibrary( string )
     * @param string $method_name The method to call through the library
     * @param callable $func
     */
    public function middleware(string $library_name, string $method_name, callable $func) : void
    {
        // It's easier to cache an array
        array_push($this->currentMiddlewares, [
            'library' => $library_name,
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

            if (is_array($middlewares)) {
                $this->handleMiddlewares($middlewares, $handler['parameters'], $arguments);
            }

            return new Route($handler['method_path'], $handler['parameters'], $arguments);

        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1]);
        } else {
            throw new RouteNotFoundException;
        }
    }

    /**
     * Add a new route.
     *
     * @param string|string[] $http_method The HTTP method, example: GET, POST, PATCH, PUT, DELETE, CLI, etc. Can be an array of values.
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function addRoute($http_method, string $route, string $method_path, array $parameters = []) : void
    {
        if (_::getLibrary('Cervo/Config')->get('Production') == true && file_exists($this->cacheFilePath)) {
            return;
        }

        $route = $this->currentGroupPrefix . $route;

        $this->routeCollector->addRoute($http_method, $route, [
            'method_path' => $method_path,
            'middlewares' => $this->currentMiddlewares,
            'parameters' => $parameters
        ]);
    }

    /**
     * Add a new route with GET as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function get(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('GET', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with POST as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function post(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('POST', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with PUT as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function put(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('PUT', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with PATCH as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function patch(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('PATCH', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with DELETE as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function delete(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('DELETE', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with HEAD as HTTP method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function head(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('HEAD', $route, $method_path, $parameters);
    }

    /**
     * Add a new route with CLI as method.
     *
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param array $parameters The parameters to pass
     */
    public function cli(string $route, string $method_path, array $parameters = []) : void
    {
        $this->addRoute('CLI', $route, $method_path, $parameters);
    }

    /**
     * @return Dispatcher\GroupCountBased
     * @throws InvalidRouterCacheException if the router cache exists and is invalid.
     */
    private function getDispatcher() : Dispatcher\GroupCountBased
    {
        $dispatchData = null;

        if (_::getLibrary('Cervo/Config')->get('Production') == true && file_exists($this->cacheFilePath)) {

            $dispatchData = require $this->cacheFilePath;

            if (!is_array($dispatchData)) {
                throw new InvalidRouterCacheException;
            }

        } else {
            $dispatchData = $this->routeCollector->getData();
        }

        $this->generateCache($dispatchData);

        return new Dispatcher\GroupCountBased($dispatchData);
    }

    /**
     * @param array $dispatchData
     */
    private function generateCache(array $dispatchData) : void
    {
        $dir = dirname($this->cacheFilePath);

        if (_::getLibrary('Cervo/Config')->get('Production') == true && !file_exists($this->cacheFilePath) && is_dir($dir) && is_writable($dir)) {
            file_put_contents(
                $this->cacheFilePath,
                '<?php return ' . var_export($dispatchData, true) . ';' . PHP_EOL,
                LOCK_EX
            );
        }
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
            return $args ? '/' . implode('/', $args) : '/';
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
     * Throws an exception or return.
     *
     * @param array $middlewares
     * @param array $parameters
     * @param array $arguments
     *
     * @return void
     * @throws RouteMiddlewareFailedException if a route middleware returned false.
     * @throws InvalidMiddlewareException if a middleware is invalid.
     */
    private function handleMiddlewares(array $middlewares, array $parameters, array $arguments) : void
    {
        foreach ($middlewares as $middleware) {

            if (is_array($middleware) && strlen($middleware['library']) > 0 && strlen($middleware['method']) > 0) {

                $middleware_library = _::getLibrary($middleware['library']);

                if (!$middleware_library->{$middleware['method']}($parameters, $arguments)) {
                    throw new RouteMiddlewareFailedException;
                }

            } else {
                throw new InvalidMiddlewareException;
            }

        }
    }
}
