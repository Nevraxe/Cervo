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



use Cervo as _;



class Events
{
    protected $events = [];
    protected $in_progress = false;

    static public function priority_sort($a, $b)
    {
        if ($a['priority'] == $b['priority'])
            return 0;

        return $a['priority'] < $b['priority'] ? -1 : 1;
    }

    public function __construct()
    {
        $config = _\Config::getInstance();

        foreach (glob($config->getApplicationDirectory() . '*' . \DS . $config->getEventsSubPath() . '*.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file)
        {
            require $file;
        }
    }

    public function register($name)
    {
        if ($this->isRegistered($name))
            return false;

        $this->events[$name] = [];

        return true;
    }

    public function isRegistered($name)
    {
        if (isset($this->events[$name]))
            return true;
        else
            return false;
    }

    public function unregister($name)
    {
        unset($this->events[$name]);
    }

    public function hook($name, $call, $priority = 0)
    {
        if (!$this->isRegistered($name))
            $this->register($name);

        $this->events[$name][] = [
            'call' => $call,
            'priority' => $priority
        ];

        return true;
    }

    public function fire($name, $params = array())
    {
        if (!is_array($params) || !$this->isRegistered($name))
            return false;

        $this->in_progress = true;

        usort($this->events[$name], '\\' . __CLASS__ . '::priority_sort');

        foreach ($this->events[$name] as $call)
        {
            call_user_func($call['call'], $name, $params);
        }

        $this->in_progress = false;

        return true;
    }

    public function isInProgress()
    {
        return $this->in_progress;
    }
}
