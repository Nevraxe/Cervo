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



namespace Cervo\Libraries\RouterPath;



/**
 * Used in Router. Each of the module's routes are RouterPath objects.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Event extends \Cervo\Libraries\RouterPath
{
    /**
     * The route's callback.
     * @var string
     */
    protected $callback;

    /**
     * Arguments to send while calling the function.
     * @var array
     */
    protected $params = [];

    /**
     * Set the path and the callback.
     * Sanitize the path and compute the regex.
     *
     * @param string $path
     * @param string $callback
     * @param int    $http_method
     * @param array  $params
     */
    public function __construct($path, $callback, $http_method = self::M_ANY, $params = [])
    {
        $this->callback = $callback;
        $this->params = $params;

        parent::__construct($path, $http_method);
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function run()
    {
        call_user_func($this->getCallback(), $this, $this->params);
    }
}
