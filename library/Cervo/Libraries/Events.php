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



/**
 * Events manager for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Events
{
    /**
     * Holds all the events and their callbacks.
     * @var array
     */
    protected $events = [];

    /**
     * True while an event is fired.
     * @var bool
     */
    protected $in_progress = false;

    /**
     * Custom sort for priority.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    static public function priority_sort($a, $b)
    {
        if ($a['priority'] == $b['priority'])
            return 0;

        return $a['priority'] < $b['priority'] ? -1 : 1;
    }

    /**
     * Include all the events files that may register and/or hook to events.
     */
    public function __construct()
    {
        $config = &_::getLibrary('Cervo/Config');

        foreach (glob($config->get('Cervo/Application/Directory') . '*' . \DS . $config->get('Cervo/Application/EventsPath') . '*.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file)
        {
            require $file;
        }
    }

    /**
     * Register a new event.
     * Always return true.
     *
     * @param string $name
     *
     * @return bool
     */
    public function register($name)
    {
        if ($this->isRegistered($name))
            return false;

        $this->events[$name] = [];

        return true;
    }

    /**
     * Check if the event exists by it's name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isRegistered($name)
    {
        if (isset($this->events[$name]))
            return true;
        else
            return false;
    }

    /**
     * Remove an event and un-hook everything related to it.
     *
     * @param string $name
     */
    public function unregister($name)
    {
        unset($this->events[$name]);
    }

    /**
     * Hook a callable to an event.
     *
     * @param string   $name
     * @param callable $call
     * @param int      $priority
     *
     * @return bool
     */
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

    /**
     * Fire an event and call all the hooked callables.
     *
     * @param string $name
     * @param array  $params
     *
     * @return bool
     */
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

    /**
     * Return true if an event is being fired.
     *
     * @return bool
     */
    public function isInProgress()
    {
        return $this->in_progress;
    }
}
