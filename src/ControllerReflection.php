<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2023 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2023 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  MIT
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

declare(strict_types=1);

namespace Cervo;

use Cervo\Exceptions\ControllerReflection\InvalidControllerException;
use Cervo\Interfaces\ControllerInterface;
use Cervo\Utils\ClassUtils;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Class ControllerReflection
 * @package Cervo
 */
final class ControllerReflection
{
    private Context $context;
    private Route $route;
    private array $parameters = [];

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
            $reflection = new ReflectionClass($this->route->getControllerClass());
        } catch (ReflectionException $e) {
            // TODO: Log
            // The constructor isn't defined, so we ignore the exception and move on
            return;
        }

        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $this->parameters[] = $this->getParameterValue($parameter);
        }
    }

    public function __invoke(): void
    {
        $controllerClass = $this->route->getControllerClass();
        (new $controllerClass(...$this->parameters))();
    }

    private function getParameterValue(ReflectionParameter $parameter)
    {
        try {
            $class = $parameter->getClass();
        } catch (ReflectionException $e) {
            // TODO: Log
            return null;
        }

        if ($class === null) {

            if ($parameter->isArray()) {

                if ($parameter->name == 'parameters') {
                    return $this->route->getParameters();
                } elseif ($parameter->name == 'arguments') {
                    return $this->route->getArguments();
                } else {

                    try {
                        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : [];
                    } catch (ReflectionException $e) {
                        // TODO: Log
                        return [];
                    }

                }

            } else {

                try {
                    return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                } catch (ReflectionException $e) {
                    // TODO: Log
                    return null;
                }

            }

        } elseif ($parameter->getClass()->name == Context::class) {
            return $this->context;
        } else {
            return $this->context->getSingletons()->get($parameter->getClass()->name);
        }
    }
}
