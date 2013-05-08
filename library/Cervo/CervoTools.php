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



use Cervo as _;



/**
 * Tools for Cervo.
 *
 * @author Marc André Audet <root@manhim.net>
 */
class CervoTools
{
    public static function phpstorm_metadata($json_config_file = null)
    {
        // A small shortcut

        if (!defined('DS'))
            define('DS', \DIRECTORY_SEPARATOR);



        // We set the default configuration values

        $config = &_::getLibrary('Cervo/Config');

        $cervo_directory = realpath(dirname(__FILE__)) . \DS;

        $config
            ->setDefault('Cervo/Application/Directory', '')
            ->setDefault('Cervo/Directory', $cervo_directory)
            ->setDefault('Cervo/Libraries/Directory', realpath($cervo_directory . 'Libraries') . \DS)
            ->setDefault('Cervo/Application/MethodSuffix', 'Method')
            ->setDefault('Cervo/Application/EventsPath', 'Events' . \DS)
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



        // We start to generate the PHPStorm metadata content

        $application_directory = $config->get('Cervo/Application/Directory');

        $file_classes = [
            'CervoLibraries' => [],
            'Libraries' => [],
            'Controllers' => [],
            'Models' => [],
            'Views' => []
        ];



        // Reading Cervo Libraries

        $path = $cervo_directory . 'Libraries' . \DS;
        $len = strlen($path);

        $files = self::globRecursive([$path]);

        foreach ($files as $file)
        {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php')
            {
                $file_classes['CervoLibraries'][] = str_replace('\\', '/', substr($file, $len, -4));
            }
        }



        // Reading the Application Libraries

        $path = $application_directory;
        $len = strlen($path);

        $files = self::globRecursive(glob($path . '*' . \DS . 'Libraries', GLOB_ONLYDIR));

        foreach ($files as $file)
        {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php')
            {
                $cur = str_replace('\\', '/', str_replace(\DS . 'Libraries' . \DS, '/', substr($file, $len, -4)));

                $ex = explode('/', $cur);

                if (count($ex) === 2 && $ex[0] === $ex[1])
                {
                    $cur = $ex[0];
                }

                $file_classes['Libraries'][] = $cur;
            }
        }



        // Reading the Application Controllers

        $path = $application_directory;
        $len = strlen($path);

        $files = self::globRecursive(glob($path . '*' . \DS . 'Controllers', GLOB_ONLYDIR));

        foreach ($files as $file)
        {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php')
            {
                $cur = str_replace('\\', '/', str_replace(\DS . 'Controllers' . \DS, '/', substr($file, $len, -4)));

                $ex = explode('/', $cur);

                if (count($ex) === 2 && $ex[0] === $ex[1])
                {
                    $cur = $ex[0];
                }

                $file_classes['Controllers'][] = $cur;
            }
        }



        // Reading the Application Models

        $path = $application_directory;
        $len = strlen($path);

        $files = self::globRecursive(glob($path . '*' . \DS . 'Models', GLOB_ONLYDIR));

        foreach ($files as $file)
        {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php')
            {
                $cur = str_replace('\\', '/', str_replace(\DS . 'Models' . \DS, '/', substr($file, $len, -4)));

                $ex = explode('/', $cur);

                if (count($ex) === 2 && $ex[0] === $ex[1])
                {
                    $cur = $ex[0];
                }

                $file_classes['Models'][] = $cur;
            }
        }



        // Reading the Application Views

        $path = $application_directory;
        $len = strlen($path);

        $files = self::globRecursive(glob($path . '*' . \DS . 'Views', GLOB_ONLYDIR));

        foreach ($files as $file)
        {
            if (strncmp($path, $file, $len) === 0 && substr($file, -4) === '.php')
            {
                $cur = str_replace('\\', '/', str_replace(\DS . 'Views' . \DS, '/', substr($file, $len, -4)));

                $ex = explode('/', $cur);

                if (count($ex) === 2 && $ex[0] === $ex[1])
                {
                    $cur = $ex[0];
                }

                $file_classes['Views'][] = $cur;
            }
        }



        // We write the results

        $towrite = <<<METADATA
<?php

namespace PHPSTORM_META
{
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    \$STATIC_METHOD_TYPES = [
        \Cervo::getLibrary('') => [

METADATA;

        foreach ($file_classes['CervoLibraries'] as $f)
        {
            $call_name = $f;
            $class = str_replace('/', '\\', $f);

            if ($call_name === 'Exceptions')
                continue;

            $towrite .= '            \'Cervo/' . $call_name . '\' instanceof \Cervo\Libraries\\' . $class . ",\n";
        }

        foreach ($file_classes['Libraries'] as $f)
        {
            $ex = explode('/', $f);

            if (count($ex) <= 1)
            {
                $module = $ex[0];
                $call_name = $ex[0];
                $class = $ex[0];
            }
            else
            {
                $module = $ex[0];
                $call_name = $f;
                $class = implode('\\', array_slice($ex, 1));
            }

            $towrite .= '            \'' . $call_name . '\' instanceof \Application\\' . $module . 'Module\Libraries\\' . $class . ",\n";
        }

        $towrite .= <<<METADATA
        ],
        \Cervo::getController('') => [

METADATA;

        foreach ($file_classes['Controllers'] as $f)
        {
            $ex = explode('/', $f);

            if (count($ex) <= 1)
            {
                $module = $ex[0];
                $call_name = $ex[0];
                $class = $ex[0];
            }
            else
            {
                $module = $ex[0];
                $call_name = $f;
                $class = implode('\\', array_slice($ex, 1));
            }

            $towrite .= '            \'' . $call_name . '\' instanceof \Application\\' . $module . 'Module\Controllers\\' . $class . ",\n";
        }

        $towrite .= <<<METADATA
        ],
        \Cervo::getModel('') => [

METADATA;

        foreach ($file_classes['Models'] as $f)
        {
            $ex = explode('/', $f);

            if (count($ex) <= 1)
            {
                $module = $ex[0];
                $call_name = $ex[0];
                $class = $ex[0];
            }
            else
            {
                $module = $ex[0];
                $call_name = $f;
                $class = implode('\\', array_slice($ex, 1));
            }

            $towrite .= '            \'' . $call_name . '\' instanceof \Application\\' . $module . 'Module\Models\\' . $class . ",\n";
        }

        $towrite .= <<<METADATA
        ],
        \Cervo::getView('') => [

METADATA;

        foreach ($file_classes['Views'] as $f)
        {
            $ex = explode('/', $f);

            if (count($ex) <= 1)
            {
                $module = $ex[0];
                $call_name = $ex[0];
                $class = $ex[0];
            }
            else
            {
                $module = $ex[0];
                $call_name = $f;
                $class = implode('\\', array_slice($ex, 1));
            }

            $towrite .= '            \'' . $call_name . '\' instanceof \Application\\' . $module . 'Module\Views\\' . $class . ",\n";
        }

        $towrite .= <<<METADATA
        ],
METADATA;

        $towrite .= <<<METADATA

    ];
}

METADATA;




        echo '<pre>';

        echo htmlentities($towrite);

        echo '</pre>';
    }

    private static function globRecursive($folders)
    {
        $files = [];

        while (count($folders) > 0)
        {
            $cur = array_pop($folders);

            foreach (glob($cur . '*', GLOB_MARK) as $file)
            {
                if (is_dir($file))
                {
                    array_push($folders, $file);
                }
                else
                {
                    if (is_file($file) && is_readable($file))
                    {
                        $files[] = $file;
                    }
                }
            }
        }

        return $files;
    }
}
