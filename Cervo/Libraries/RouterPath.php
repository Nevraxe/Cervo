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
    const NO_MATCH = -1;
    const FULL_MATCH = -2;

    protected $stringpath;
    protected $arraypath;
    protected $module;
    protected $controller;
    protected $method;
    protected $args;

    public function __construct($stringpath, $module, $controller, $method)
    {
        $this->module = $module;
        $this->controller = $controller;
        $this->method = $method;

        $this->stringpath = trim($stringpath, '/');

        while (strpos($this->stringpath, '//') !== false)
            $this->stringpath = str_replace('//', '/', $this->stringpath);

        $this->arraypath = ($this->stringpath == '' ? [] : explode('/', $this->stringpath));
    }

    public function compare($arraypath)
    {
        $c_arraypath = count($arraypath);
        $c_this_arraypath = count($this->arraypath);
        $match = true;
        $wildcard_pos = null;
        $precision = 0;

        if ($c_arraypath < $c_this_arraypath - 1)
        {
            $match = false;
        }
        else
        {
            for ($i = 0; $i < $c_this_arraypath || $i < $c_arraypath; $i++)
            {
                if ($this->arraypath[$i] == '*')
                {
                    $wildcard_pos = $i;
                    break;
                }
                else if (strtolower($this->arraypath[$i]) != strtolower($arraypath[$i]))
                {
                    $match = false;
                    break;
                }

                $precision++;
            }
        }

        if ($wildcard_pos !== null && $match)
        {
            $this->args = array_slice($arraypath, $wildcard_pos);
            return $precision;
        }
        else
        {
            $this->args = [];
        }

        if ($match)
        {
            return self::FULL_MATCH;
        }
        else
        {
            return self::NO_MATCH;
        }
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
