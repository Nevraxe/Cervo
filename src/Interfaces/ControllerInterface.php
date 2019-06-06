<?php

/**
 * This file is part of the Cervo package.
 *
 * Copyright (c) 2010-2019 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>.
 *
 * @package   Cervo
 * @author    Marc André Audet <maaudet@nevraxe.com>
 * @copyright 2010 - 2019 Nevraxe inc. & Marc André Audet
 * @license   See LICENSE.md  MIT
 * @link      https://github.com/Nevraxe/Cervo
 * @since     5.0.0
 */

declare(strict_types=1);

namespace Cervo\Interfaces;

/**
 * Cervo controller interface.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
interface ControllerInterface
{
    /**
     * This function is executed from Cervo when the class matches the Route
     *
     * @return void
     */
    public function __invoke(): void;
}
