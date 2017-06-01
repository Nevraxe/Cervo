<?php


/**
 *
 * Copyright (c) 2010-2017 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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


/**
 * Events manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Events
{
    /**
     * Holds all the events and their callbacks.
     * @var array
     */
    private $events = [];

    /**
     * Name of the event in progress, null if there are no active events.
     * @var string|null
     */
    private $inProgress = null;

    /**
     * Include all the events files that may register and/or hook to events.
     */
    public function __construct()
    {
        $config = _::getLibrary('Cervo/Config');

        foreach (glob($config->get('Cervo/Application/Directory') . '*' . \DS . $config->get('Cervo/Application/EventsPath') . '*.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            $function = require $file;

            if (is_callable($function)) {
                $function($this);
            }

        }
    }

    /**
     * Register a new event.
     *
     * @param string $name
     *
     * @return bool
     */
    public function register(string $name) : bool
    {
        if ($this->isRegistered($name)) {
            return false;
        }

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
    public function isRegistered(string $name) : bool
    {
        return isset($this->events[$name]);
    }

    /**
     * Remove an event and un-hook everything related to it.
     *
     * @param string $name
     */
    public function unregister(string $name) : void
    {
        unset($this->events[$name]);
    }

    /**
     * Hook a callable to an event.
     * If the event is not registered, register it.
     *
     * @param string $name
     * @param callable $call
     * @param int $priority
     *
     * @return bool
     */
    public function hook(string $name, callable $call, int $priority = 0) : bool
    {
        if (!$this->isRegistered($name)) {
            $this->register($name);
        }

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
     * @param array $params
     *
     * @return bool
     */
    public function fire(string $name, array $params = []) : bool
    {
        if (!is_array($params) || !$this->isRegistered($name)) {
            return false;
        }

        $this->inProgress = $name;

        usort($this->events[$name], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach ($this->events[$name] as $call) {
            call_user_func($call['call'], $name, $params);
        }

        $this->inProgress = null;

        return true;
    }

    /**
     * Returns the name of the event being fired, null otherwise.
     *
     * @return string|null
     */
    public function getInProgress() : ?string
    {
        return $this->inProgress;
    }

    /**
     * Returns true if an event is being fired.
     *
     * @return bool
     */
    public function isInProgress() : bool
    {
        return $this->inProgress !== null;
    }
}
