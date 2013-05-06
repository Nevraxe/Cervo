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
    Cervo\Libraries\RouterPath;



class Router
{
    protected $path = '';
    protected $routes = [];

    public function __construct()
    {
        $this->path = trim($this->parseRoute(), '/');

        while (strpos($this->path, '//') !== false)
            $this->path = str_replace('//', '/', $this->path);

        $this->route();
    }

    public function addRoute($path, $module, $controller, $method)
    {
        $this->routes[] = new RouterPath($path, $module, $controller, $method);
    }

    public function getRoute()
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
            return current($returns);
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

    protected function route()
    {
        $config = &_::getLibrary('Cervo/Config');

        foreach (glob($config->get('application_directory') . '*' . \DS . 'Router.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file)
        {
            require $file;
        }
    }
}
