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

/**
 * Used in Router. Each of the module's routes are RouterPath objects.
 *
 * @author Marc André Audet <root@manhim.net>
 */
abstract class RouterPath
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
     * When it does not match.
     */
    const NO_MATCH = false;

    /**
     * When it matches.
     */
    const FULL_MATCH = true;

    /**
     * The current path.
     * @var string
     */
    protected $path;

    /**
     * The http method to match against.
     * @var int
     */
    protected $http_method;

    /**
     * The current arguments.
     * @var array
     */
    protected $args = [];

    /**
     * The regex to match against.
     * @var string
     */
    protected $regex = '';

    /**
     * Set the path, the module, the controller and the method.
     * Sanitize the path and compute the regex.
     *
     * @param int    $http_method
     * @param string $path
     */
    public function __construct($path, $http_method = self::M_ANY)
    {
        $this->http_method = $http_method;

        $this->path = trim($path, '/');

        while (strpos($this->path, '//') !== false)
            $this->path = str_replace('//', '/', $this->path);

        $arraypath = ($this->path == '' ? [] : explode('/', $this->path));

        $c_arraypath = count($arraypath);
        $this->regex .= '/^';

        for ($i = 0; $i < $c_arraypath; $i++)
        {
            if ($arraypath[$i] == '*')
            {
                $this->regex .= '(?:\/(.+))?';
            }
            else
            {
                if ($i > 0)
                {
                    $this->regex .= '\/';
                }

                if ($arraypath[$i] == '?')
                {
                    $this->regex .= '([^\/]+)';
                }
                else
                {
                    $this->regex .= preg_quote(strtolower($arraypath[$i]), '/');
                }
            }
        }
        $this->regex .= '$/i';
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
        if ($this->http_method !== self::M_ANY)
        {
            if (defined('STDIN'))
            {
                if ($this->http_method & self::M_CLI !== self::M_CLI)
                    return \Cervo\Libraries\RouterPath::NO_MATCH;
            }
            else
            {
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

        $matches = null;
        if (preg_match($this->regex, $path, $matches) !== 1)
        {
            return self::NO_MATCH;
        }

        $c_matches = count($matches);
        for ($i = 1; $i < $c_matches; $i++)
        {
            $this->args = array_merge($this->args, explode('/', $matches[$i]));
        }

        return self::FULL_MATCH;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getHTTPMethod()
    {
        return $this->http_method;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
