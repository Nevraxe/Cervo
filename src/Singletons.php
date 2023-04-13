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
    private array $objects = [];

    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Fetch an object from the Singletons registry, or instantialise it.
     *
     * @param string $className The name of the class to get as Singleton
     *
     * @return SingletonInterface
     */
    public function get(string $className): SingletonInterface
    {
        if (isset($this->objects[$className]) && is_object($this->objects[$className])) {
            return $this->objects[$className];
        }

        if (!ClassUtils::implements($className, SingletonInterface::class)) {
            throw new InvalidClassException();
        }

        return ($this->objects[$className] = new $className($this->context));
    }
}
