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


use Cervo\Exceptions\ControllerReflection\InvalidControllerException;
use Cervo\Interfaces\ControllerInterface;
use Cervo\Utils\ClassUtils;


/**
 * Class ControllerReflection
 * @package Cervo
 */
final class ControllerReflection
{
    /** @var Context */
    private $context;

    /** @var Route */
    private $route;

    /** @var array */
    private $parameters = [];

    /**
     * ControllerReflection constructor.
     *
     * @param Context $context
     * @param Route $route
     */
    public function __construct(Context $context, Route $route)
    {
        if (!ClassUtils::implements($route->getControllerClass(), ControllerInterface::class)) {
            throw new InvalidControllerException;
        }

        $this->context = $context;
        $this->route = $route;

        try {

            $reflection = new \ReflectionClass($this->route->getControllerClass());

            foreach ($reflection->getConstructor()->getParameters() as $parameter) {
                $this->parameters[] = $this->getParameterValue($parameter);
            }

        } catch (\ReflectionException $e) {
            // The contructor isn't defined, so we ignore the exception and move on
        }
    }

    public function __invoke() : void
    {
        $controller_class = $this->route->getControllerClass();
        (new $controller_class(...$this->parameters))();
    }

    private function getParameterValue(\ReflectionParameter $parameter)
    {
        if ($parameter->getClass() === null) {

            if ($parameter->isArray()) {

                if ($parameter->getName() == 'parameters') {
                    return $this->route->getParameters();
                } elseif ($parameter->getName() == 'arguments') {
                    return $this->route->getArguments();
                } else {
                    return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : [];
                }

            } else {
                return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }

        } else {
            return $this->context->getSingletons()->get($parameter->getClass()->getName());
        }
    }
}
