<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2019 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2019 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  MIT
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

declare(strict_types=1);

namespace Cervo\Config;

/**
 * Configuration manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class BaseConfig
{
    /**
     * The currently set values in a multi-dimensional array.
     * @var array
     */
    private $values = [];

    /**
     * Set the value at the specified configuration path.
     *
     * @param string|array $name The configuration path
     * @param mixed $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        if (!is_array($name)) {
            $name = explode('/', $name);
        }

        $current = &$this->values;

        foreach ($name as $key) {
            $current = &$current[$key];
        }

        $current = $value;

        return $this;
    }

    /**
     * Return the value for the specified configuration path.
     * If this value is not set, return the default value.
     * Return null if not set.
     *
     * @param string $name The configuration path
     *
     * @return mixed
     */
    public function get(string $name)
    {
        $current = &$this->values;

        foreach (explode('/', $name) as $key) {

            if (isset($current[$key])) {
                $current = &$current[$key];
            } else {
                return null;
            }

        }

        return $current;
    }

    /**
     * Recursively set all the values from an array.
     * Usually used when importing.
     *
     * @param array $array
     * @param array $currentPath
     */
    public function setFromArrayRecursive(array $array, array $currentPath = [])
    {
        foreach ($array as $key => $el) {

            if (is_array($el)) {
                $this->setFromArrayRecursive($el, array_merge($currentPath, [$key]));
            } else {
                $this->set(array_merge($currentPath, [$key]), $el);
            }

        }
    }
}
