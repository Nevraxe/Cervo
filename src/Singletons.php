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

    public function get(string $className): SingletonInterface
    {
        if (is_object($this->objects[$className])) {
            return $this->objects[$className];
        }

        if (!ClassUtils::implements($className, SingletonInterface::class)) {
            throw new InvalidClassException();
        }

        return ($this->objects[$className] = new $className($this->context));
    }
}
