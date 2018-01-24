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

    public function __construct()
    {

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

            if ($current[$key]) {
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
     * @param array $current_path
     */
    public function setFromArrayRecursive(array $array, array $current_path = [])
    {
        foreach ($array as $key => $el) {

            if (is_array($el)) {
                $this->setFromArrayRecursive($el, array_merge($current_path, [$key]));
            } else {
                $this->set(array_merge($current_path, [$key]), $el);
            }

        }
    }
}
