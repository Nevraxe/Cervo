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

use Cervo\Exceptions\ConfigurationNotFoundException;
use Cervo\Exceptions\Router\InvalidMiddlewareException;
use Cervo\Exceptions\Router\InvalidRouterCacheException;
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
use const DIRECTORY_SEPARATOR;

/**
 * Routes manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Router implements SingletonInterface
{
    const CACHE_FILE_NAME = 'router.cache.php';

    /** @var ?RouteCollector FastRoute, null if usingCache is set */
    private ?RouteCollector $routeCollector;

    /** @var array List of middlewares called using the middleware() method. */
    private array $currentMiddlewares = [];

    /** @var string List of group prefixes called using the group() method. */
    private string $currentGroupPrefix;

    /** @var Context The current context */
    private Context $context;

    /** @var string The path to the router cache file */
    private string $cacheFilePath;

    /** @var bool Whether the cache currently exists */
    private bool $cacheExists = false;

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

        $root_dir = $this->context->getConfig()->get('app/root_dir');
        $cache_dir = $this->context->getConfig()->get('app/cache_dir') ?? 'var' . DIRECTORY_SEPARATOR . 'cache';

        if (!$root_dir || strlen($root_dir) <= 0) {
            throw new ConfigurationNotFoundException('app/root_dir');
        }

        $this->cacheFilePath = $root_dir . DIRECTORY_SEPARATOR . $cache_dir . DIRECTORY_SEPARATOR . self::CACHE_FILE_NAME;

        if ($this->context->getConfig()->get('app/production') && file_exists($this->cacheFilePath)) {
            $this->cacheExists = true;
        }
    }

    /**
     * Load every PHP Routes files under the directory
     *
     * @param string $path
     */
    public function loadPath(string $path): void
    {
        if ($this->cacheExists) {
            return;
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . 'Routes')) {

            foreach (PathUtils::getRecursivePHPFilesIterator($path . DIRECTORY_SEPARATOR . 'Routes') as $file) {

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
     * @param string|string[] $middlewareClass The middleware to use
     * @param callable $func
     */
    public function middleware(array|string $middlewareClass, callable $func): void
    {
        /** @noinspection PhpArrayPushWithOneElementInspection */
        array_push($this->currentMiddlewares, $middlewareClass);

        $func($this);

        array_pop($this->currentMiddlewares);
    }

    /**
     * Adds a prefix in front of all the encapsulated routes.
     *
     * @param string $prefix The prefix of the group.
     * @param callable $func
     * @param array|string|null $middlewareClass string[] or string representing middleware(s) class(es)
     */
    public function group(string $prefix, callable $func, array|string $middlewareClass = null): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;

        if (is_array($middlewareClass) || is_string($middlewareClass)) {
            $this->middleware($middlewareClass, $func);
        } else {
            $func($this);
        }

        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * Dispatch the request to the router.
     *
     * @return Route
     * @throws MethodNotAllowedException if the request method is not supported, but others are for this route.
     * @throws RouteNotFoundException if the requested route did not match any routes.
     */
    public function dispatch(): Route
    {
        $dispatcher = $this->getDispatcher();

        if (defined('STDIN')) {
            $requestMethod = 'CLI';
        } else {
            $requestMethod = $_SERVER['REQUEST_METHOD'];
        }

        $routeInfo = $dispatcher->dispatch($requestMethod, $this->detectUri());

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
            throw new MethodNotAllowedException;
        } else {
            throw new RouteNotFoundException;
        }
    }

    /**
     * Add a new route.
     *
     * @param string|string[] $httpMethod The HTTP method, example: GET, HEAD, POST, PATCH, PUT, DELETE, CLI, etc.
     *  Can be an array of values.
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function addRoute(array|string $httpMethod, string $route, string $controllerClass, array $parameters = []): void
    {
        if ($this->cacheExists) {
            return;
        }

        $route = $this->currentGroupPrefix . $route;

        $this->routeCollector->addRoute($httpMethod, $route, [
            'controller_class' => $controllerClass,
            'middlewares' => $this->currentMiddlewares,
            'parameters' => $parameters
        ]);
    }

    /**
     * Add a new route with GET as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function get(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('GET', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with HEAD as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function head(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('HEAD', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with POST as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function post(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('POST', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with PUT as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function put(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('PUT', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with PATCH as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function patch(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('PATCH', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with DELETE as HTTP method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function delete(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('DELETE', $route, $controllerClass, $parameters);
    }

    /**
     * Add a new route with CLI as method.
     *
     * @param string $route The route
     * @param string $controllerClass The Controller's class
     * @param array $parameters The parameters to pass
     */
    public function cli(string $route, string $controllerClass, array $parameters = []): void
    {
        $this->addRoute('CLI', $route, $controllerClass, $parameters);
    }

    /**
     * @param array $dispatchData
     *
     * @return bool
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     */
    private function generateCache(array $dispatchData) : bool
    {
        $dir = dirname($this->cacheFilePath);

        if (!file_exists($this->cacheFilePath) && is_dir($dir) && is_writable($dir)) {

            return file_put_contents(
                $this->cacheFilePath,
                '<?php return ' . var_export($dispatchData, true) . ';' . PHP_EOL,
                LOCK_EX
            ) !== false;

        } else {
            return false;
        }
    }

    /**
     * @return Dispatcher\GroupCountBased
     */
    private function getDispatcher(): Dispatcher\GroupCountBased
    {
        $dispatchData = null;

        if ($this->cacheExists) {

            if (!is_array($dispatchData = require $this->cacheFilePath)) {
                throw new InvalidRouterCacheException;
            }

        } else {

            $dispatchData = $this->routeCollector->getData();

            if ($this->context->getConfig()->get('app/production')) {
                $this->generateCache($dispatchData);
            }

        }

        return new Dispatcher\GroupCountBased($dispatchData);
    }

    /**
     * Returns a parsable URI
     *
     * @return string
     */
    private function detectUri(): string
    {
        if (php_sapi_name() == 'cli') {
            $args = array_slice($_SERVER['argv'], 1);
            return count($args) > 0 ? '/' . implode('/', $args) : '/';
        }

        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME'])) {
            return '/';
        }

        $parts = preg_split('#\?#', $this->getBaseUri(), 2);
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
    private function getBaseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (strlen($_SERVER['SCRIPT_NAME']) > 0) {

            if (str_starts_with($uri, $_SERVER['SCRIPT_NAME'])) {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (str_starts_with($uri, dirname($_SERVER['SCRIPT_NAME']))) {
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
    private function handleMiddlewares(array $middlewares, Route $route): void
    {
        foreach ($middlewares as $middleware) {
            if (is_array($middleware)) {

                $this->handleMiddlewares($middleware, $route);

            } elseif (is_string($middleware) && strlen($middleware) > 0) {

                if (!ClassUtils::implements($middleware, MiddlewareInterface::class)) {
                    throw new InvalidMiddlewareException;
                }

                if (!(new $middleware($route))()) {
                    throw new RouteMiddlewareFailedException;
                }

            } else {
                throw new InvalidMiddlewareException;
            }

        }
    }
}
