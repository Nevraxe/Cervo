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



namespace Cervo\Libraries;



use Cervo as _,
    Cervo\Libraries\RouterPath\Route,
    Cervo\Libraries\RouterPath\Event;



/**
 * Route manager for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Router
{
    /**
     * The current path.
     * @var string
     */
    protected $path = '';

    /**
     * All the registered routes.
     * @var \Cervo\Libraries\RouterPath\Route[]
     */
    protected $routes = [];

    /**
     * All the registered events that will be run before the route is calculated.
     * @var \Cervo\Libraries\RouterPath\Event[]
     */
    protected $events = [];

    /**
     * If set to true, the route will not be calculated and will return false (silently).
     * @var bool
     */
    protected $prevent_default = false;

    /**
     * The currently matching route.
     * Set when calling getRoute().
     * @var \Cervo\Libraries\RouterPath|null
     */
    private $route = null;

    /**
     * Parse the route and sanitize the $path string.
     */
    public function __construct()
    {
        $this->path = trim($this->parseRoute(), '/');

        while (strpos($this->path, '//') !== false)
            $this->path = str_replace('//', '/', $this->path);

        $this->route();
    }

    /**
     * Add a new route.
     * Usually set in each module's Router.php.
     *
     * @param string $path
     * @param string $controller
     * @param int    $http_method
     * @param array  $params
     *
     * @return $this
     */
    public function &addRoute($path, $controller, $http_method = Route::M_ALL, $params = [])
    {
        $this->routes[] = new Route($path, $controller, $http_method, $params);
        return $this;
    }

    /**
     * Add a new Route object to the routes.
     *
     * @param Route $route
     *
     * @return $this
     */
    public function &addRouteObject(Route $route)
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * Add a new event that will be run before the routes.
     *
     * @param string $path
     * @param string $callback
     * @param array  $params
     *
     * @return $this
     */
    public function &addEvent($path, $callback, $params = [])
    {
        $this->events[] = new Event($path, $callback, $params);
        return $this;
    }

    /**
     * Add a new Event object that will be run before the routes.
     *
     * @param Event $event
     *
     * @return $this
     */
    public function &addEventObject(Event $event)
    {
        $this->events[] = $event;
        return $this;
    }

    /**
     * Return the current Route.
     *
     * If prevent_default is set to true, this function will always return false.
     *
     * @return Route|false
     *
     * @throws Exceptions\TooManyRoutesException
     * @throws Exceptions\RouteNotFoundException
     */
    public function getRoute()
    {
        if ($this->route !== null)
            return $this->route;

        foreach ($this->events as $e)
        {
            if ($e->compare($this->path) === RouterPath::FULL_MATCH)
            {
                $e->run();
            }
        }

        if (!$this->prevent_default)
        {
            $returns = [];

            foreach ($this->routes as $r)
            {
                if ($r->compare($this->path) === RouterPath::FULL_MATCH)
                {
                    $returns[] = $r;
                }
            }

            $c_returns = count($returns);

            if ($c_returns == 1)
            {
                $this->route = current($returns);
                return $this->route;
            }
            else if ($c_returns > 1)
            {
                throw new _\Libraries\Exceptions\TooManyRoutesException();
            }
            else
            {
                throw new _\Libraries\Exceptions\RouteNotFoundException();
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Detect the current input method and parse the route accordingly.
     * Return the requested route.
     *
     * @return string
     */
    protected function parseRoute()
    {
        if (defined('STDIN'))
        {
            $args = array_slice($_SERVER['argv'], 1);
            return $args ? '/' . implode('/', $args) : '';
        }

        if ($uri = $this->detectUri())
        {
            return $uri;
        }

        $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : getenv('PATH_INFO');

        if (trim($path, '/') != '' && $path != "/" . SELF)
        {
            return $path;
        }

        $path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : getenv('QUERY_STRING');

        if (trim($path, '/') != '')
        {
            return $path;
        }

        return '';
    }

    /**
     * Return the current Uri.
     *
     * @return string
     */
    protected function detectUri()
    {
        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME']))
        {
            return '';
        }

        $uri = $_SERVER['REQUEST_URI'];

        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
        {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        }
        elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
        {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }

        if (strpos($uri, '?/') === 0)
        {
            $uri = substr($uri, 2);
        }

        $parts = preg_split('#\?#i', $uri, 2);
        $uri = $parts[0];

        if (isset($parts[1]))
        {
            $_SERVER['QUERY_STRING'] = $parts[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
        else
        {
            $_SERVER['QUERY_STRING'] = '';
            $_GET = [];
        }

        if ($uri == '/' || empty($uri))
        {
            return '/';
        }

        $uri = parse_url($uri, PHP_URL_PATH);

        return str_replace([
            '//',
            '../'
        ], '/', trim($uri, '/'));
    }

    /**
     * Go through all the module's Router.php and include each of them using Glob().
     * Warning: The files are not included in a particular order and it should be considered
     * that the order always changes.
     */
    protected function route()
    {
        $config = &_::getLibrary('Cervo/Config');

        foreach (glob($config->get('Cervo/Application/Directory') . '*' . \DS . 'Router.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file)
        {
            require $file;
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function &preventDefault()
    {
        $this->prevent_default = true;
        return $this;
    }

    public function getPreventDefault()
    {
        return $this->prevent_default;
    }
}
