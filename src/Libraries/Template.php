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


use Cervo\Core as _;
use Cervo\Libraries\Exceptions\TemplateFileMissingException;


/**
 * Template class for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Template
{
    /**
     * The template path.
     * @var array
     */
    protected $name;

    /**
     * The template's data.
     * Usually set from the View.
     * @var array
     */
    protected $data = [];

    /**
     * Initialize the template.
     *
     * @param string $name The template (file)name.
     *
     * @throws Exceptions\TemplateFileMissingException
     */
    public function __construct($name)
    {
        $config = _::getLibrary('Cervo/Config');

        $this->name = explode('/', $name);

        if (!file_exists($config->get('Cervo/Application/Directory') . $this->name[0] . \DS . $config->get('Cervo/Application/TemplatesPath') . implode('/', array_slice($this->name, 1)) . '.php')) {
            throw new TemplateFileMissingException();
        }
    }

    /**
     * Magic method to return the data.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    /**
     * Assign an array as the template's data.
     * Usually set from the View.
     *
     * @param array $data
     *
     * @return $this
     */
    public function assign($data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Render the template.
     */
    public function render()
    {
        $config = _::getLibrary('Cervo/Config');

        require $config->get('Cervo/Application/Directory') . $this->name[0] . \DS . $config->get('Cervo/Application/TemplatesPath') . implode('/', array_slice($this->name, 1)) . '.php';
    }
}
