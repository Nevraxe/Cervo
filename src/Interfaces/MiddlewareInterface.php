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

namespace Cervo\Interfaces;

use Cervo\Route;

/**
 * Cervo middleware interface.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
interface MiddlewareInterface
{
    /**
     * MiddlewareInterface constructor.
     *
     * @param Route $route
     */
    public function __construct(Route $route);

    /**
     * Called when checking against the Middleware in the Router
     * Return true to keep the request going
     * Return false to stop the Router and return an error
     *
     * @return bool
     */
    public function __invoke(): bool;
}
