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


use Cervo\Exceptions\Singletons\InvalidClassException;
use Cervo\Interfaces\SingletonInterface;
use Cervo\Utils\ClassUtils;


/**
 * Singletons manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Singletons
{
    private $objects = [];

    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function get(string $class_name) : SingletonInterface
    {
        if (is_object($this->objects[$class_name])) {
            return $this->objects[$class_name];
        }

        if (!ClassUtils::implements($class_name, SingletonInterface::class)) {
            throw new InvalidClassException();
        }

        return ($this->objects[$class_name] = new $class_name($this->context));
    }
}
