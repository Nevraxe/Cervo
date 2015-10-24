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
use Cervo\Libraries\Exceptions\InvalidRouterCacheException;
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
    /**
     * FastRoute, null if usingCache is set
     * @var RouteCollector
     */
    protected $routeCollector;

    /**
     * FastRoute cache file path.
     * @var string
     */
    protected $cacheFilePath;

    /**
     * This is set to true if we are using the cache.
     * @var bool
     */
    protected $usingCache = false;

    /**
     * Set to true if we need to generate the cache
     * @var bool
     */
    protected $generateCache = false;

    /**
     * Initialize the route configurations.
     */
    public function __construct()
    {
        $config = _::getLibrary('Cervo/Config');

        $this->cacheFilePath = $config->get('Cervo/Application/Directory') . \DS . 'router.cache.php';

        if ($config->get('Production') == true && file_exists($this->cacheFilePath)) {
            $this->usingCache = true;
        } else {
            if ($config->get('Production') == true) {
                $this->generateCache = true;
            }

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
    }

    /**
     * Dispatch the request to the router.
     *
     * @return bool|Route
     */
    public function dispatch()
    {
        $dispatcher = $this->getDispatcher();

        if (defined('STDIN')) {
            $request_method = 'CLI';
        } else {
            $request_method = $_SERVER['REQUEST_METHOD'];
        }

        $routeInfo = $dispatcher->dispatch($request_method, $this->detectUri());

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
            default:

                throw new RouteNotFoundException;

            case Dispatcher::FOUND:

                $handler = $routeInfo[1];
                $arguments = $routeInfo[2];
                $middleware = $handler['middleware'];

                if (is_array($middleware) && count($middleware) === 2) {
                    $middleware_library = _::getLibrary($middleware[0]);

                    if (!$middleware_library->$middleware[1]($this)) {
                        return false;
                    }
                }

                return new Route($handler['method_path'], $handler['parameters'], $arguments);

        }
    }

    /**
     * Add a new route.
     *
     * @param string $httpMethod The HTTP method, example: GET, POST, PATCH, etc.
     * @param string $route The route
     * @param string $method_path The Method Path
     * @param callable|null $middleware Call a middleware before executing the route. The format is ['MyModule/MyLibrary', 'MyMethod']
     * @param array $parameters The parameters to pass
     */
    public function addRoute($httpMethod, $route, $method_path, $middleware = [], $parameters = [])
    {
        if ($this->usingCache) {
            return;
        }

        $this->routeCollector->addRoute($httpMethod, $route, [
            'method_path' => $method_path,
            'middleware' => $middleware,
            'parameters' => $parameters
        ]);
    }

    protected function getDispatcher()
    {
        $dispatchData = null;

        if ($this->usingCache) {
            $dispatchData = require $this->cacheFilePath;

            if (!is_array($dispatchData)) {
                throw new InvalidRouterCacheException;
            }
        } else {
            $dispatchData = $this->routeCollector->getData();
        }

        if ($this->generateCache) {
            file_put_contents(
                $this->cacheFilePath,
                '<?php return ' . var_export($dispatchData, true) . ';' . PHP_EOL
            );
        }

        return new Dispatcher\GroupCountBased($dispatchData);
    }

    /**
     * Returns a parsable URI
     *
     * @return string
     */
    protected function detectUri()
    {
        if (defined('STDIN')) {
            $args = array_slice($_SERVER['argv'], 1);
            return $args ? '/' . implode('/', $args) : '/';
        }

        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME'])) {
            return '/';
        }

        $uri = $this->getQueryStringUri($this->getBaseUri());

        if ($uri == '/' || empty($uri)) {
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
    protected function getBaseUri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }

        return $uri;
    }

    /**
     * Return the uri with the query string parsed if the request is made using the query string method
     *
     * @param string $baseUri
     *
     * @return string
     */
    protected function getQueryStringUri($baseUri)
    {
        if (strpos($baseUri, '?/') === 0) {
            $baseUri = substr($baseUri, 2);
        }

        $parts = preg_split('#\?#i', $baseUri, 2);
        $baseUri = $parts[0];

        if (isset($parts[1])) {
            $_SERVER['QUERY_STRING'] = $parts[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            $_SERVER['QUERY_STRING'] = '';
            $_GET = [];
        }

        return $baseUri;
    }
}
