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



use Cervo as _;



class Template
{
    protected $name;
    protected $data = array();

    public function __construct($name)
    {
        $config = _\Config::getInstance();

        $this->name = explode('/', $name);

		if (!file_exists($config->getApplicationDirectory() . $this->name[0] . \DS . $config->getTemplatesSubPath() . implode('/', array_slice($this->name, 1)) . '.php'))
		{
			throw new _\Libraries\Exceptions\TemplateNotFoundException();
		}
    }

	public function __get($name)
	{
		if (isset($this->data[$name]))
		{
			return $this->data[$name];
		}
		else
		{
			return null;
		}
	}

	public function &assign($data = array())
	{
		$this->data = $data;
		return $this;
	}

	public function render()
	{
        $config = _\Config::getInstance();

		require $config->getApplicationDirectory() . $this->name[0] . \DS . $config->getTemplatesSubPath() . implode('/', array_slice($this->name, 1)) . '.php';
	}
}
