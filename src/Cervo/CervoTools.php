<?php


/**
 *
 * Copyright (c) 2015 Marc André "Manhim" Audet <root@manhim.net>. All rights reserved.
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


use Cervo\Core as _;


/**
 * Tools for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class Tools
{
    /**
     * Generates the content to put in a metadata file for PHPStorm.
     * It does not scan the content of the files, so it makes no difference
     * between an abstract class or a regular class. It have been written
     * in a way that it gives more results then should be used, but it
     * should cover every classes for every calls.
     *
     * To use this, in your index.php file (Or your file that calls
     * \Cervo::init()) you simply need to replace the init() call with
     * \CervoTools::phpstormMetadata()
     *
     * @param string|null $json_config_file The path to the JSON configuration file to use.
     */
    public static function phpstormMetadata($json_config_file = null)
    {
        // Start the configuration process

        _::initConfig($json_config_file);
        $config = _::getLibrary('Cervo/Config');


        // Print out the results

        echo '<pre>';

        echo htmlentities(
            self::phpstormMetadataHeader() .
            self::phpstormMetadataLibraries(self::getCervoLibraries($config->get('Cervo/Libraries/Directory')), self::getApplicationClasses($config->get('Cervo/Application/Directory'), $config->get('Cervo/Application/LibariesPath'))) .
            self::phpstormMetadataControllers(self::getApplicationClasses($config->get('Cervo/Application/Directory'), $config->get('Cervo/Application/ControllersPath'))) .
            self::phpstormMetadataModels(self::getApplicationClasses($config->get('Cervo/Application/Directory'), $config->get('Cervo/Application/ModelsPath'))) .
            self::phpstormMetadataViews(self::getApplicationClasses($config->get('Cervo/Application/Directory'), $config->get('Cervo/Application/ViewsPath'))) .
            self::phpstormMetadataFooter()
        );

        echo '</pre>';
    }

    /**
     * Fetch a list of all the Cervo Libraries class.
     *
     * @param string $path The path to the Cervo Libraries folder
     *
     * @return array
     */
    private static function getCervoLibraries($path)
    {
        $len = strlen($path);

        $files = self::globRecursive([$path]);
        $classes = [];

        foreach ($files as $file) {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php') {
                $classes[] = str_replace('\\', '/', substr($file, $len, -4));
            }
        }

        return $classes;
    }

    /**
     * Fetch a list of all the Application classes depending on the sub path.
     *
     * @param string $path The path to the Application root
     * @param string $sub_path The sub-path to look for
     *
     * @return array
     */
    private static function getApplicationClasses($path, $sub_path)
    {
        $files = self::globRecursive(glob($path . '*' . \DS . $sub_path, GLOB_ONLYDIR));
        $path_len = strlen($path);
        $classes = [];

        foreach ($files as $file) {
            if (strncmp($path, $file, $path_len) === 0 && substr($file, -4) === '.php') {
                $cur = str_replace('\\', '/', str_replace(\DS . $sub_path, '/', substr($file, $path_len, -4)));

                $ex = explode('/', $cur);

                if (count($ex) === 2 && $ex[0] === $ex[1]) {
                    $cur = $ex[0];
                }

                $classes[] = $cur;
            }
        }

        return $classes;
    }

    private static function phpstormMetadataHeader()
    {
        return <<<METADATA
<?php

namespace PHPSTORM_META
{
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    \$STATIC_METHOD_TYPES = [

METADATA;
    }

    private static function phpstormMetadataLibraries($cervo_libraries, $libraries)
    {
        $towrite = <<<METADATA
        \Cervo::getLibrary('') => [

METADATA;

        foreach ($cervo_libraries as $f) {
            $class = str_replace('/', '\\', $f);

            if ($f === 'Exceptions') {
                continue;
            }

            $towrite .= '            \'Cervo/' . $f . '\' instanceof \Cervo\Libraries\\' . $class . ",\n";
        }

        foreach ($libraries as $f) {
            $ex = explode('/', $f);

            if (count($ex) <= 1) {
                $towrite .= '            \'' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Libraries\\' . $ex[0] . ",\n";
                $towrite .= '            \'' . $ex[0] . '/' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Libraries\\' . $ex[0] . ",\n";
            } else {
                $towrite .= '            \'' . $f . '\' instanceof \Application\\' . $ex[0] . 'Module\Libraries\\' . implode('\\', array_slice($ex, 1)) . ",\n";
            }
        }

        $towrite .= <<<METADATA
        ],

METADATA;

        return $towrite;
    }

    private static function phpstormMetadataControllers($controllers)
    {
        $towrite = <<<METADATA
        \Cervo::getController('') => [

METADATA;

        foreach ($controllers as $f) {
            $ex = explode('/', $f);

            if (count($ex) <= 1) {
                $towrite .= '            \'' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Controllers\\' . $ex[0] . ",\n";
                $towrite .= '            \'' . $ex[0] . '/' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Controllers\\' . $ex[0] . ",\n";
            } else {
                $towrite .= '            \'' . $f . '\' instanceof \Application\\' . $ex[0] . 'Module\Controllers\\' . implode('\\', array_slice($ex, 1)) . ",\n";
            }
        }

        $towrite .= <<<METADATA
        ],

METADATA;

        return $towrite;
    }

    private static function phpstormMetadataModels($models)
    {
        $towrite = <<<METADATA
        \Cervo::getModel('') => [

METADATA;

        foreach ($models as $f) {
            $ex = explode('/', $f);

            if (count($ex) <= 1) {
                $towrite .= '            \'' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Models\\' . $ex[0] . ",\n";
                $towrite .= '            \'' . $ex[0] . '/' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Models\\' . $ex[0] . ",\n";
            } else {
                $towrite .= '            \'' . $f . '\' instanceof \Application\\' . $ex[0] . 'Module\Models\\' . implode('\\', array_slice($ex, 1)) . ",\n";
            }
        }

        $towrite .= <<<METADATA
        ],

METADATA;

        return $towrite;
    }

    private static function phpstormMetadataViews($views)
    {
        $towrite = <<<METADATA
        \Cervo::getView('') => [

METADATA;

        foreach ($views as $f) {
            $ex = explode('/', $f);

            if (count($ex) <= 1) {
                $towrite .= '            \'' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Views\\' . $ex[0] . ",\n";
                $towrite .= '            \'' . $ex[0] . '/' . $ex[0] . '\' instanceof \Application\\' . $ex[0] . 'Module\Views\\' . $ex[0] . ",\n";
            } else {
                $towrite .= '            \'' . $f . '\' instanceof \Application\\' . $ex[0] . 'Module\Views\\' . implode('\\', array_slice($ex, 1)) . ",\n";
            }
        }

        $towrite .= <<<METADATA
        ],

METADATA;

        return $towrite;
    }

    private static function phpstormMetadataFooter()
    {
        return <<<METADATA

    ];
}

METADATA;
    }

    /**
     * Recursively return every files under the $folders array.
     * Does not accept a string.
     *
     * Returns a list of all the files under the folders.
     *
     * @param array $folders List of folders to scan from
     *
     * @return array
     */
    private static function globRecursive(array $folders)
    {
        $files = [];

        while (count($folders) > 0) {
            $cur = array_pop($folders);

            foreach (glob($cur . '*', GLOB_MARK) as $file) {
                if (is_dir($file)) {
                    array_push($folders, $file);
                } else {
                    if (is_file($file) && is_readable($file)) {
                        $files[] = $file;
                    }
                }
            }
        }

        return $files;
    }
}
