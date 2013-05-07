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



class RouterPath
{
    const NO_MATCH = false;
    const FULL_MATCH = true;

    protected $path;
    protected $module;
    protected $controller;
    protected $method;
    protected $args = [];
    protected $regex = '';

    public function __construct($path, $module, $controller, $method)
    {
        $this->module = $module;
        $this->controller = $controller;
        $this->method = $method;

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
                $this->regex .= '[\/]{0,1}(.*)';
            }
            else
            {
                if ($i > 0)
                {
                    $this->regex .= '\/';
                }

                if ($arraypath[$i] == '?')
                {
                    $this->regex .= '(.[^\/]*)';
                }
                else
                {
                    $this->regex .= preg_quote(strtolower($arraypath[$i]), '/');
                }
            }
        }
        $this->regex .= '$/i';
    }

    public function compare($path)
    {
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

    public function getArgs()
    {
        return $this->args;
    }
}
