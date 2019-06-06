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

namespace Cervo\Config;

/**
 * Configuration manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
class JsonConfig extends BaseConfig
{
    /**
     * JsonConfig constructor.
     *
     * @param null|string $jsonFilePath The path to the JSON file to use as configuration source.
     */
    public function __construct(?string $jsonFilePath = null)
    {
        if ($jsonFilePath !== null && file_exists($jsonFilePath)) {
            $this->setFromArrayRecursive(json_decode(file_get_contents($jsonFilePath), true));
        }
    }
}
