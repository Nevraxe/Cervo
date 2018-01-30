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

use Cervo\Config\BaseConfig;
use Cervo\Exceptions\ControllerReflection\AlreadyInitialisedException;


/**
 * Core class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Core
{
    private static $isInit = false;
    private static $context = null;

    public static function init(?BaseConfig $config = null)
    {
        if (self::$isInit === true) {
            throw new AlreadyInitialisedException;
        }

        self::$isInit = true;
        self::$context = new Context($config);
    }

    public static function getContext()
    {
        return self::$context;
    }
}
