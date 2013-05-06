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



use Cervo\Config;



class Cervo
{
    const VERSION = '2.1.0';

    protected static $libraries = [];
    protected static $controllers = [];
    protected static $autoloads = [];

    private static $is_init = false;

    public static function init($json_config_file = null)
    {
        // We check if the system is already initiated

        if (self::$is_init)
            return;

        self::$is_init = true;



        // We set the autolaoder

        spl_autoload_register('\Cervo::autoload');



        // We set the default configuration values

        $config = &self::getLibrary('Cervo/Config');

        $cervo_directory = realpath(__FILE__) . \DS;

        $config
            ->setDefault('Cervo/Directory', $cervo_directory)
            ->setDefault('Cervo/Libraries/Directory', realpath($cervo_directory . 'Libraries') . \DS)
            ->setDefault('Cervo/Application/MethodSuffix', 'Method')
            ->setDefault('Cervo/Application/EventsSubPath', 'Events' . \DS)
            ->setDefault('Cervo/Application/ControllersPath', 'Controllers' . \DS)
            ->setDefault('Cervo/Application/ModelsPath', 'Models' . \DS)
            ->setDefault('Cervo/Application/ViewsPath', 'Views' . \DS)
            ->setDefault('Cervo/Application/LibariesPath', 'Libraries' . \DS)
            ->setDefault('Cervo/Application/TemplatesPath', 'Templates' . \DS)
            ->setDefault('Production', false)
            ;

        if ($json_config_file !== null)
        {
            $config->importJSON($json_config_file);
        }



        // Events startup

        $events = &self::getLibrary('Cervo/Events');

        $events->register('core_pre_system');
        $events->register('core_pre_controller');
        $events->register('core_post_controller');
        $events->register('core_post_system');



        // We fire the pre_system event

        $events->fire('core_pre_system');



        // We initialize the Router

        $router = &self::getLibrary('Cervo/Router');



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

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Libraries\\' . $path[0];
        }
        else
        {
            if ($path[0] === 'Cervo')
            {
                $i_name = '\Cervo\Libraries\\' . implode('\\', array_slice($path, 1));
            }
            else
            {
                $i_name = '\Application\\' . $path[0] . 'Module\Libraries\\' . implode('\\', array_slice($path, 1));
            }
        }

        self::$libraries[$name] = new $i_name;
        return self::$libraries[$name];
    }

    public static function &getController($name)
    {
        if (is_object(self::$controllers[$name]))
            return self::$controllers[$name];

        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Controllers\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Controllers\\' . implode('\\', array_slice($path, 1));
        }

        self::$controllers[$name] = new $i_name;
        return self::$controllers[$name];
    }

    public static function &getModel($name)
    {
        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Models\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Models\\' . implode('\\', array_slice($path, 1));
        }

        return new $i_name;
    }

    public static function &getView($name)
    {
        $path = explode('/', $name);

        if (count($path) <= 1)
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Views\\' . $path[0];
        }
        else
        {
            $i_name = '\Application\\' . $path[0] . 'Module\Views\\' . implode('\\', array_slice($path, 1));
        }

        return new $i_name;
    }

    public static function &getTemplate($name)
    {
        return new \Cervo\Libraries\Template($name);
    }

    // Deprecated
    public static function &getConfig($name)
    {
        $config = &self::getLibrary('Cervo/Config');

        if (file_exists($config->get('Cervo/Application/Directory') . $name . \DS . 'Config.php'))
        {
            return require $config->get('Cervo/Application/Directory') . $name . \DS . 'Config.php';
        }
        else
        {
            return [];
        }
    }

    public static function autoload($name)
    {
        if (strpos($name, 'Application\\') === 0 || strpos($name, 'Cervo\Libraries\\') === 0)
        {
            $config = &self::getLibrary('Cervo/Config');

            $ex = explode('\\', $name);

            if ($ex[0] === 'Cervo' && $ex[1] === 'Libraries')
            {
                require $config->get('Cervo/Libraries/Directory') . $ex[2] . '.php';
            }
            else if ($ex[0] === 'Application' && substr($ex[1], -1 * 6) === 'Module')
            {
                require $config->get('Cervo/Application/Directory') . substr($ex[1], 0, strlen($ex[1]) - 6) . \DS . implode(\DS, array_slice($ex, 2)) . '.php';
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
