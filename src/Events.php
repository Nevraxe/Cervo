<?php


/**
 *
 * Copyright (c) 2010-2018 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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
 * DISCLAIMED. IN NO EVENT SHALL NEVRAXE INC. & MARC ANDRÉ AUDET BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


namespace Cervo;


use Cervo\Interfaces\SingletonInterface;
use Cervo\Utils\PathUtils;


/**
 * Events manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Events implements SingletonInterface
{
    /** @var array Holds all the events and their callbacks */
    private $events = [];

    /** @var string|null Name of the event in progress, null if there are no active events */
    private $inProgress = null;

    /** @var Context The current context */
    private $context;

    /**
     * Events constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Load every PHP Events files under the directory
     *
     * @param string $path Path to the module
     */
    public function loadPath(string $path) : void
    {
        if (file_exists($path . \DIRECTORY_SEPARATOR . 'Events')) {

            foreach (
                PathUtils::getRecursivePHPFilesIterator($path . \DIRECTORY_SEPARATOR . 'Events')
                as $file
            ) {

                $callback = require $file->getPathName();

                if (is_callable($callback)) {
                    $callback($this);
                }

            }

        }
    }

    /**
     * Register a new event.
     *
     * @param string $name The name of the event
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
     * @param string $name The name of the event
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
     * @param string $name The name of the event
     */
    public function unregister(string $name) : void
    {
        unset($this->events[$name]);
    }

    /**
     * Hook a callable to an event.
     * If the event is not registered, register it.
     *
     * @param string $name The name of the event to hook on
     * @param callable $callback The callback to call once the event is fired
     * @param int $priority The priority order of the callback
     *
     * @return bool
     */
    public function hook(string $name, callable $callback, int $priority = 0) : bool
    {
        if (!$this->isRegistered($name)) {
            $this->register($name);
        }

        $this->events[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        return true;
    }

    /**
     * Fire an event and call all the hooked callables.
     *
     * @param string $name The name of the event to fire
     * @param array $params The parameters to pass down on the callbacks
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
            call_user_func($call['callback'], $name, $params);
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
