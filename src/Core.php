<?php


/**
 *
 * Copyright (c) 2010-2017 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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


/**
 * Core class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Core
{
    /**
     * The current version of Cervo.
     */
    const VERSION = '4.0.0';

    /**
     * All the libraries instances that have been initialized through getLibrary().
     * @var array
     */
    private static $libraries = [];

    /**
     * All the library classes to be used whend not found in the application through getLibrary().
     * @var array
     */
    private static $injected_libraries = [];

    /**
     * All the controller instances that have been initialized through getController().
     * @var array
     */
    private static $controllers = [];

    /**
     * All the controller classes to be used when not found in the application through getController().
     * @var array
     */
    private static $injected_controllers = [];

    /**
     * If Cervo have been initialized.
     * @var bool
     */
    private static $is_init = false;

    /**
     * If the configuration/autoloaders have been initialized.
     * @var bool
     */
    private static $is_init_config = false;

    /**
     * Initialize Cervo.
     *
     * @param string|null $json_config_file The path to the JSON configuration file to use.
     */
    public static function init(?string $json_config_file = null) : void
    {
        // Check if the system is already initiated

        if (self::$is_init) {
            return;
        }

        self::$is_init = true;


        // Start the configuration process

        self::initConfig($json_config_file);


        // Events startup

        $events = self::getLibrary('Cervo/Events');

        $events->register('Cervo/System/Before');
        $events->register('Cervo/Controller/Before');
        $events->register('Cervo/Controller/After');
        $events->register('Cervo/System/After');


        // Fire the pre-system event

        $events->fire('Cervo/System/Before');


        // Get the required libraries

        $router = self::getLibrary('Cervo/Router');


        // Initialise the system

        $route = $router->dispatch();

        if ($route instanceof Route) {
            $events->fire('Cervo/Controller/Before');

            $method = $route->getMethod();
            self::getController($route->getModule() . '/' . $route->getController())->$method($route->getArguments(), $route->getParameters());

            $events->fire('Cervo/Controller/After');
        }


        // Fire the post-system event

        $events->fire('Cervo/System/After');
    }

    public static function getInjectedLibraries() : array
    {
        return self::$injected_libraries;
    }

    public static function getInjectedControllers() : array
    {
        return self::$injected_controllers;
    }

    /**
     * Initialize the configuration for Cervo with default configs.
     *
     * @param string|null $json_config_file
     */
    public static function initConfig(?string $json_config_file = null) : void
    {
        // Check if the system is already initiated

        if (self::$is_init_config) {
            return;
        }

        self::$is_init_config = true;


        // Add the autoloader
        spl_autoload_register('\Cervo\Core::autoload');


        // Small shortcut

        if (!defined('DS')) {
            define('DS', \DIRECTORY_SEPARATOR);
        }


        // Set the default configuration values

        $config = self::getLibrary('Cervo/Config');

        $cervo_directory = realpath(dirname(__FILE__)) . \DS;

        $config
            ->setDefault('Cervo/Application/Directory', '')
            ->setDefault('Cervo/Directory', $cervo_directory)
            ->setDefault('Cervo/Libraries/Directory', realpath($cervo_directory . 'Libraries') . \DS)
            ->setDefault('Production', false);

        $config->importJSON($json_config_file);
    }

    /**
     * First iteration of a provider interface to inject elements into Cervo.
     *
     * @param ProviderInterface $provider
     */
    public static function register(ProviderInterface $provider)
    {
        $provider->register();
    }

    /**
     * Return a library. It will be stored in an internal cache and reused if called again.
     * $name format: [Module]/[Name]
     * Name MAY contain slashes (/) to go deeper in the tree.
     * The module name Cervo may be used to access the Cervo standard libraries.
     *
     * @param string $name The path name
     *
     * @return object
     */
    public static function getLibrary(string $name)
    {
        if (is_object(self::$libraries[$name])) {
            return self::$libraries[$name];
        }

        $path = explode('/', $name);

        if ($path[0] === 'Cervo') {
            $i_name = '\Cervo\Libraries\\' . implode('\\', array_slice($path, 1));
        } else {
            $i_name = self::getPath($name, 'Libraries');
        }

        if (!class_exists($i_name, true) && isset(self::$injected_libraries[$name])) {
            $i_name = self::$injected_libraries[$name];
        }

        return (self::$libraries[$name] = new $i_name);
    }

    /**
     * Injects a library to be used in getLibrary() if not found in the application.
     *
     * @param string $name The path name
     * @param string $i_name The class name
     */
    public static function injectLibrary(string $name, string $i_name)
    {
        self::$injected_libraries[$name] = $i_name;
    }

    /**
     * Return a controller. It will be stored in an internal cache and reused if called again.
     * $name format: [Module]/[Name]
     * $name MAY contain slashes (/) to go deeper in the tree.
     *
     * @param string $name The path name
     *
     * @return object
     */
    public static function getController(string $name)
    {
        if (is_object(self::$controllers[$name])) {
            return self::$controllers[$name];
        }

        $i_name = self::getPath($name, 'Libraries');

        if (!class_exists($i_name, true) && isset(self::$injected_controllers[$name])) {
            $i_name = self::$injected_controllers[$name];
        }

        return (self::$controllers[$name] = new $i_name);
    }

    /**
     * Injects a controller to be used in getController() if not found in the application.
     *
     * @param string $name The path name
     * @param string $i_name The class name
     */
    public static function injectController(string $name, string $i_name)
    {
        self::$injected_controllers[$name] = $i_name;
    }

    /**
     * Return an instanciated object depending on the module sub-folder.
     * $class_path format: [Module]/[Name]
     * $class_path MAY contain slashes (/) to go deeper in the tree.
     * $application_path is the module sub-folder to look in for.
     *
     * @param string $name The path name
     * @param string $application_path The sub-folder within the module
     *
     * @return object
     */
    public static function getPath(string $name, string $application_path)
    {
        $path = explode('/', $name);

        if (count($path) <= 1) {
            $i_name = '\Application\\' . $path[0] . 'Module\\' . $application_path . '\\' . $path[0];
        } else {
            $i_name = '\Application\\' . $path[0] . 'Module\\' . $application_path . '\\' . implode('\\', array_slice($path, 1));
        }

        return new $i_name;
    }

    /**
     * Return a template.
     * $name format: [Module]/[Name]
     * Name MAY contain slashes (/) to go deeper in the tree.
     *
     * @param string $name The path name
     *
     * @return Template
     */
    public static function getTemplate(string $name) : Template
    {
        return new Template($name);
    }

    /**
     * The default class autoloader.
     * Also run any additional autoloaders added with register_autoload().
     *
     * @param string $name The class full name (Include the namespace(s))
     */
    public static function autoload(string $name) : void
    {
        if (strpos($name, 'Application\\') === 0) {

            $config = self::getLibrary('Cervo/Config');

            $ex = explode('\\', $name);

            if ($ex[0] === 'Application' && substr($ex[1], -1 * 6) === 'Module') {
                require $config->get('Cervo/Application/Directory') . substr($ex[1], 0, strlen($ex[1]) - 6) . \DS . implode(\DS, array_slice($ex, 2)) . '.php';
            }

        }
    }
}
