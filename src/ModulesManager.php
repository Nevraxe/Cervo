<?php


/**
 *
 * Copyright (c) 2010-2018 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
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
 * DISCLAIMED. IN NO EVENT SHALL NEVRAXE INC. & MARC ANDRÉ AUDET BE LIABLE FOR ANY
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
 * Modules manager for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class ModulesManager
{
    private $vendors = [];

    /**
     * Add a new path and extract the modules from it
     *
     * @param string $path The path to the Vendors directory
     *
     * @return ModulesManager
     */
    public function addPath(string $path) : self
    {
        $path = realpath($path);
        $path_len = strlen($path);

        foreach (glob($path . \DIRECTORY_SEPARATOR . '*', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            if (is_dir($file)) {
                $this->addVendor(substr($file, $path_len + 1), $file);
            }

        }

        return $this;
    }

    /**
     * Add a new path and extract the modules from it, using a default Vendor name.
     *
     * @param string $vendor_name The Vendor name
     * @param string $path The path to the Modules directory
     *
     * @return ModulesManager
     */
    public function addVendor(string $vendor_name, string $path) : self
    {
        $path_len = strlen($path);

        foreach (glob($path . \DIRECTORY_SEPARATOR . '*', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            if (is_dir($file)) {
                $this->addModule($vendor_name, substr($file, $path_len + 1), $file);
            }

        }

        return $this;
    }

    /**
     * Add a new path and extract the module, using a default Vendor and Module name.
     *
     * @param string $vendor_name The Vendor name
     * @param string $module_name The Module name
     * @param string $path The path to the Module's directory
     *
     * @return ModulesManager
     */
    public function addModule(string $vendor_name, string $module_name, string $path) : self
    {
        $this->vendors[$vendor_name][$module_name] = $path;
        return $this;
    }

    /**
     * Get an array of all the modules listed under a Vendor.
     *
     * @param string $vendor_name The Vendor name
     *
     * @return array
     */
    public function getVendorModules(string $vendor_name) : array
    {
        return $this->vendors[$vendor_name];
    }

    /**
     * Get the root path of a module.
     *
     * @param string $vendor_name The Vendor's name
     * @param string $module_name The Module's name
     *
     * @return null|string
     */
    public function getModulePath(string $vendor_name, string $module_name) : ?string
    {
        return $this->vendors[$vendor_name][$module_name];
    }

    /**
     * Return a generator of every modules.
     * Each iterations are formatted like this:
     * [$vendor_name, $module_name, $path]
     *
     * You should use a list() to extract the data.
     *
     * @return \Generator
     */
    public function getAllModules() : \Generator
    {
        foreach ($this->vendors as $vendor_name => $modules) {
            foreach ($modules as $module_name => $path) {
                yield [$vendor_name, $module_name, $path];
            }
        }
    }
}
