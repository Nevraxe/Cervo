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

declare(strict_types=1);

namespace Cervo;

/**
 * Route for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class Route
{
    /** @var string The controller class */
    private $controllerClass;

    /** @var array The parameters to pass */
    private $parameters = [];

    /** @var array The arguments to pass */
    private $arguments = [];

    public function __construct(string $controllerClass, array $parameters = [], array $arguments = [])
    {
        $this->controllerClass = $controllerClass;
        $this->parameters = $parameters;
        $this->arguments = $arguments;
    }

    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
