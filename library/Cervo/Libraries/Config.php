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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
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
 * Configuration manager for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Config
{
    /**
     * The currently set default values in a multi-dimensional array.
     * @var array
     */
    protected $default_values = [];

    /**
     * The currently set values in a multi-dimensional array.
     * @var array
     */
    protected $values = [];

    /**
     * Magic method for set().
     *
     * @param string|array $name
     * @param mixed        $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Add a new array element to the specified configuration path.
     * Warning: If the current value is not an array, it is overwritten.
     *
     * @param string|array $name The configuration path
     * @param mixed        $value
     *
     * @return $this
     */
    public function &add($name, $value)
    {
        if (!is_array($name))
            $name = explode('/', trim($name, "/\t\n\r\0\x0B"));

        $current = &$this->values;

        foreach ($name as $key)
            $current = &$current[$key];

        if (!is_array($current))
            $current = [];

        $current[] = $value;

        return $this;
    }

    /**
     * Set the value at the specified configuration path.
     *
     * @param string|array $name The configuration path
     * @param mixed        $value
     *
     * @return $this
     */
    public function &set($name, $value)
    {
        if (!is_array($name))
            $name = explode('/', $name);

        $current = &$this->values;

        foreach ($name as $key)
            $current = &$current[$key];

        $current = $value;

        return $this;
    }

    /**
     * Set the default fallback value for the specified configuration path.
     *
     * @param string|array $name The configuration path
     * @param mixed        $value
     *
     * @return $this
     */
    public function &setDefault($name, $value)
    {
        if (!is_array($name))
            $name = explode('/', $name);

        $current = &$this->default_values;

        foreach ($name as $key)
            $current = &$current[$key];

        $current = $value;

        return $this;
    }

    /**
     * Magic method for get().
     *
     * @param string $name The configuration path
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Return the value for the specified configuration path.
     * If this value is not set, return the default value.
     * Return null if not set.
     *
     * @param string|array $name The configuration path
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!is_array($name))
            $name = explode('/', $name);

        $current = &$this->values;
        $is_set = true;

        foreach ($name as $key)
        {
            if ($current[$key])
            {
                $current = &$current[$key];
            }
            else
            {
                $is_set = false;
                break;
            }
        }

        if ($is_set === true && $current)
        {
            return $current;
        }

        return $this->getDefault($name);
    }

    /**
     * Return the default value for the specified configuration path.
     * Return null if not set.
     *
     * @param string|array $name The configuration path
     *
     * @return mixed
     */
    public function getDefault($name)
    {
        if (!is_array($name))
            $name = explode('/', $name);

        $current = &$this->default_values;

        foreach ($name as $key)
        {
            if ($current[$key])
            {
                $current = &$current[$key];
            }
            else
            {
                return null;
            }
        }

        if ($current)
            return $current;

        return null;
    }

    /**
     * Import a JSON file and parse it.
     * Return true on success, false if the file does not exists.
     *
     * @param string $file The path to the file.
     *
     * @return bool
     */
    public function importJSON($file)
    {
        if (!file_exists($file))
            return false;

        $this->setFromArrayRecursive(json_decode(file_get_contents($file), true));

        return true;
    }

    /**
     * Recursively set all the values from an array.
     * Usually used when importing.
     *
     * @param array $array
     * @param array $current_path
     */
    protected function setFromArrayRecursive($array, $current_path = [])
    {
        foreach ($array as $key => $el)
        {
            if (is_array($el))
            {
                $this->setFromArrayRecursive($el, array_merge($current_path, [$key]));
            }
            else
            {
                $this->set(array_merge($current_path, [$key]), $el);
            }
        }
    }
}
