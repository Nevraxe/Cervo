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

    public function __invoke(): void
    {
        $controllerClass = $this->route->getControllerClass();
        (new $controllerClass(...$this->parameters))();
    }

    private function getParameterValue(\ReflectionParameter $parameter)
    {
        if ($parameter->getClass() === null) {

            if ($parameter->isArray()) {

                if ($parameter->name == 'parameters') {
                    return $this->route->getParameters();
                } elseif ($parameter->name == 'arguments') {
                    return $this->route->getArguments();
                } else {
                    return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : [];
                }

            } else {
                return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }

        } else {
            return $this->context->getSingletons()->get($parameter->getClass()->name);
        }
    }
}
