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
 * Autoloader for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Autoloader
{
    /** @var Context The current context */
    private $context;

    /**
     * Autoloader constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;

        spl_autoload_register($this);
    }

    public function __invoke($class)
    {
        $classParts = explode('\\', $class);

        if (count($classParts) < 3) {
            return;
        }

        $path = $this->context->getModulesManager()->getModulePath($classParts[0], $classParts[1]);

        if (strlen($path) <= 0) {
            return;
        }

        $filePath = $path . \DIRECTORY_SEPARATOR . implode(\DIRECTORY_SEPARATOR, array_slice($classParts, 2)) . '.php';

        if (file_exists($filePath)) {
            require $filePath;
        }
    }
}
