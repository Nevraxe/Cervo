<?php


/**
 *
 * Copyright (c) 2010-2016 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Config
{
    /**
     * The currently set default values in a multi-dimensional array.
     * @var array
     */
    protected $defaultValues = [];

    /**
     * The currently set values in a multi-dimensional array.
     * @var array
     */
    protected $values = [];

    /**
     * Add a new array element to the specified configuration path.
     * Warning: If the current value is not an array, it is overwritten.
     *
     * @param string|array $name The configuration path
     * @param mixed $value
     *
     * @return $this
     */
    public function add($name, $value)
    {
        $current = &$this->values;

        foreach (explode('/', $name) as $key) {
            $current = &$current[$key];
        }

        if (!is_array($current)) {
            $current = [];
        }

        $current[] = $value;

        return $this;
    }

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
        $this->_set($name, $value, false);
        return $this;
    }

    /**
     * Set the default fallback value for the specified configuration path.
     *
     * @param string|array $name The configuration path
     * @param mixed $value
     *
     * @return $this
     */
    public function setDefault($name, $value)
    {
        $this->_set($name, $value, true);
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
    public function get($name)
    {
        $current = &$this->values;

        foreach (explode('/', $name) as $key) {
            if ($current[$key]) {
                $current = &$current[$key];
            } else {
                return $this->getDefault($name);
            }
        }

        return $current;
    }

    /**
     * Return the default value for the specified configuration path.
     * Return null if not set.
     *
     * @param string $name The configuration path
     *
     * @return mixed
     */
    public function getDefault($name)
    {
        $current = &$this->defaultValues;

        foreach (explode('/', $name) as $key) {
            if ($current[$key]) {
                $current = &$current[$key];
            } else {
                return null;
            }
        }

        if ($current) {
            return $current;
        }

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
        if ($file === null || !file_exists($file)) {
            return false;
        }

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
        foreach ($array as $key => $el) {
            if (is_array($el)) {
                $this->setFromArrayRecursive($el, array_merge($current_path, [$key]));
            } else {
                $this->set(array_merge($current_path, [$key]), $el);
            }
        }
    }

    protected function _set($name, $value, $is_default = false)
    {
        if ($is_default) {
            $current = &$this->defaultValues;
        } else {
            $current = &$this->values;
        }

        foreach (explode('/', $name) as $key) {
            $current = &$current[$key];
        }

        $current = $value;
    }
}
