<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2018 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2018 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  BSD-2-Clauses
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
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
    public function loadPath(string $path): void
    {
        if (file_exists($path . \DIRECTORY_SEPARATOR . 'Events')) {

            foreach (PathUtils::getRecursivePHPFilesIterator($path . \DIRECTORY_SEPARATOR . 'Events') as $file) {

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
    public function register(string $name): bool
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
    public function isRegistered(string $name): bool
    {
        return isset($this->events[$name]);
    }

    /**
     * Remove an event and un-hook everything related to it.
     *
     * @param string $name The name of the event
     */
    public function unregister(string $name): void
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
    public function hook(string $name, callable $callback, int $priority = 0): bool
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
    public function fire(string $name, array $params = []): bool
    {
        if (!is_array($params) || !$this->isRegistered($name)) {
            return false;
        }

        $this->inProgress = $name;

        usort($this->events[$name], function ($left, $right) {
            return $left['priority'] <=> $right['priority'];
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
    public function getInProgress(): ?string
    {
        return $this->inProgress;
    }

    /**
     * Returns true if an event is being fired.
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->inProgress !== null;
    }
}
