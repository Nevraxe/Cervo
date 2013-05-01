<?php

/**
 *
 * Copyright (c) 2013 Marc André "Manhim" Audet <root@manhim.net>. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *   1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 *
 *   2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */



namespace Cervo;



class Cervo
{
    const VERSION = '1.1.0';

    protected static $libraries = [];
    protected static $controllers = [];
    protected static $autoloads = [];

    private static $modulenamespacesuffix_len = null;
    private static $is_init = false;

    public static function init()
    {
        // We check if the system is already initiated

        if (self::$is_init)
            return;

        self::$is_init = true;



        // We set the autolaoder

        spl_autoload_register('\Cervo\Cervo::autoload');



        // We fetch the config

        $config = Config::getInstance();



        // Events startup

        $events = self::getLibrary('Cervo/Events');

        $events->register('core_pre_system');
        $events->register('core_pre_controller');
        $events->register('core_post_controller');
        $events->register('core_post_system');



        // We fire the pre_system event

        $events->fire('core_pre_system');



        // We initialize the Router

        $router = self::getLibrary('Cervo/Router');



        // We initialise the system

        $route = $router->getRoute();

        $events->fire('core_pre_controller');

        $method = $route->getMethod() . $config->getMethodSuffix();

        self::getController($route->getModule() . '/' . $route->getController())->$method($route->getArgs());

        $events->fire('core_post_controller');



        // We fire the post_system event

        $events->fire('core_post_system');
    }

    public static function &getLibrary($name)
    {
        if (is_object(self::$libraries[$name]))
            return self::$libraries[$name];

        $config = Config::getInstance();

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Libraries\\' . $path[0];
        }
        else
        {
            if ($path[0] === 'Cervo')
            {
                $i_name = '\Cervo\Libraries\\' . implode('\\', array_slice($path, 1));
            }
            else
            {
                $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Libraries\\' . implode('\\', array_slice($path, 1));
            }
        }

        self::$libraries[$name] = new $i_name;
        return self::$libraries[$name];
    }

    public static function &getController($name)
    {
        if (is_object(self::$controllers[$name]))
            return self::$controllers[$name];

        $config = Config::getInstance();

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Controllers\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Controllers\\' . implode('\\', array_slice($path, 1));
        }

        self::$controllers[$name] = new $i_name;
        return self::$controllers[$name];
    }

    public static function &getModel($name)
    {
        $config = Config::getInstance();

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Models\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Models\\' . implode('\\', array_slice($path, 1));
        }

        return new $i_name;
    }

    public static function &getView($name)
    {
        $config = Config::getInstance();

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Views\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . $config->getModuleNamespaceSuffix() . '\Views\\' . implode('\\', array_slice($path, 1));
        }

        return new $i_name;
    }

    public static function &getTemplate($name)
    {
        return new \Cervo\Libraries\Template($name);
    }

    public static function &getConfig($name)
    {
        $config = Config::getInstance();

        if (file_exists($config->getApplicationDirectory() . $name . \DS . 'Config.php'))
        {
            return require $config->getApplicationDirectory() . $name . \DS . 'Config.php';
        }
        else
        {
            return [];
        }
    }

    public static function autoload($name)
    {
        if (strncmp($name, 'Application\\', 12) === 0 || strncmp($name, 'Cervo\Libraries\\', 16) === 0)
        {
            $config = Config::getInstance();

            if (self::$modulenamespacesuffix_len === null)
                self::$modulenamespacesuffix_len = strlen($config->getModuleNamespaceSuffix());

            $ex = explode('\\', $name);

            if ($ex[0] === 'Cervo' && $ex[1] === 'Libraries')
            {
                require $config->getCervoLibrariesDirectory() . $ex[2] . $config->getExtention();
            }
            else if ($ex[0] === 'Application' && substr($ex[1], -1 * self::$modulenamespacesuffix_len) === $config->getModuleNamespaceSuffix())
            {
                require $config->getApplicationDirectory() . substr($ex[1], 0, strlen($ex[1]) - self::$modulenamespacesuffix_len) . \DS . implode(\DS, array_slice($ex, 2)) . $config->getExtention();
            }
        }

        $c_autoloads = count(self::$autoloads);
        for ($i = 0; $i < $c_autoloads; $i++)
        {
            $func = self::$autoloads[$i];
            $func($name);
        }
    }

    public static function register_autoload($function)
    {
        self::$autoloads[] = $function;
    }
}
