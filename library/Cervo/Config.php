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



class Config
{
    static private $instance = null;

    static public function &getInstance()
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }

    private function __clone(){}

    private function __construct()
    {
        if (!defined('DS'))
            define('DS', \DIRECTORY_SEPARATOR);

        $this->setExtention('.php')
            ->setCervoDirectory(realpath(dirname(__FILE__)) . \DS)
            ->setCervoLibrariesDirectory(realpath($this->getCervoDirectory() . 'Libraries') . \DS)
            ->setModuleNamespaceSuffix('Module')
            ->setMethodSuffix('Method')
            ->setEventsSubPath('Events' . \DS)
            ->setControllersSubPath('Controllers' . \DS)
            ->setModelsSubPath('Models' . \DS)
            ->setViewsSubPath('Views' . \DS)
            ->setLibrariesSubPath('Libraries' . \DS)
            ->setTemplatesSubPath('Templates' . \DS)
            ;
    }

    protected $extention;
    protected $cervo_directory;
    protected $cervo_libraries_directory;
    protected $documents_directory;
    protected $application_directory;

    protected $module_namespace_suffix;
    protected $method_suffix;

    protected $events_sub_path;
    protected $controllers_sub_path;
    protected $models_sub_path;
    protected $views_sub_path;
    protected $libraries_sub_path;
    protected $templates_sub_path;

    public function getCervoDirectory()
    {
        return $this->cervo_directory;
    }

    public function getCervoLibrariesDirectory()
    {
        return $this->cervo_libraries_directory;
    }

    public function getApplicationDirectory()
    {
        return $this->application_directory;
    }

    public function getControllersSubPath()
    {
        return $this->controllers_sub_path;
    }

    public function getDocumentsDirectory()
    {
        return $this->documents_directory;
    }

    public function getEventsSubPath()
    {
        return $this->events_sub_path;
    }

    public function getExtention()
    {
        return $this->extention;
    }

    public function getLibrariesSubPath()
    {
        return $this->libraries_sub_path;
    }

    public function getMethodSuffix()
    {
        return $this->method_suffix;
    }

    public function getModelsSubPath()
    {
        return $this->models_sub_path;
    }

    public function getModuleNamespaceSuffix()
    {
        return $this->module_namespace_suffix;
    }

    public function getTemplatesSubPath()
    {
        return $this->templates_sub_path;
    }

    public function getViewsSubPath()
    {
        return $this->views_sub_path;
    }

    public function &setCervoDirectory($cervo_directory)
    {
        $this->cervo_directory = $cervo_directory;
        return $this;
    }

    public function &setCervoLibrariesDirectory($cervo_libraries_directory)
    {
        $this->cervo_libraries_directory = $cervo_libraries_directory;
        return $this;
    }

    public function &setApplicationDirectory($application_directory)
    {
        $this->application_directory = $application_directory;
        return $this;
    }

    public function &setControllersSubPath($controllers_sub_path)
    {
        $this->controllers_sub_path = $controllers_sub_path;
        return $this;
    }

    public function &setDocumentsDirectory($documents_directory)
    {
        $this->documents_directory = $documents_directory;
        return $this;
    }

    public function &setEventsSubPath($events_sub_path)
    {
        $this->events_sub_path = $events_sub_path;
        return $this;
    }

    public function &setExtention($extention)
    {
        $this->extention = $extention;
        return $this;
    }

    public function &setLibrariesSubPath($libraries_sub_path)
    {
        $this->libraries_sub_path = $libraries_sub_path;
        return $this;
    }

    public function &setMethodSuffix($method_suffix)
    {
        $this->method_suffix = $method_suffix;
        return $this;
    }

    public function &setModelsSubPath($models_sub_path)
    {
        $this->models_sub_path = $models_sub_path;
        return $this;
    }

    public function &setModuleNamespaceSuffix($module_namespace_suffix)
    {
        $this->module_namespace_suffix = $module_namespace_suffix;
        return $this;
    }

    public function &setTemplatesSubPath($templates_sub_path)
    {
        $this->templates_sub_path = $templates_sub_path;
        return $this;
    }

    public function &setViewsSubPath($views_sub_path)
    {
        $this->views_sub_path = $views_sub_path;
        return $this;
    }
}
