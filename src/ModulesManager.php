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
    public function addPath(string $path): self
    {
        $path = realpath($path);
        $pathLen = strlen($path);

        foreach (glob($path . \DIRECTORY_SEPARATOR . '*', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            if (is_dir($file)) {
                $this->addVendor(substr($file, $pathLen + 1), $file);
            }

        }

        return $this;
    }

    /**
     * Add a new path and extract the modules from it, using a default Vendor name.
     *
     * @param string $vendorName The Vendor name
     * @param string $path The path to the Modules directory
     *
     * @return ModulesManager
     */
    public function addVendor(string $vendorName, string $path): self
    {
        $pathLen = strlen($path);

        foreach (glob($path . \DIRECTORY_SEPARATOR . '*', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file) {

            if (is_dir($file)) {
                $this->addModule($vendorName, substr($file, $pathLen + 1), $file);
            }

        }

        return $this;
    }

    /**
     * Add a new path and extract the module, using a default Vendor and Module name.
     *
     * @param string $vendorName The Vendor name
     * @param string $moduleName The Module name
     * @param string $path The path to the Module's directory
     *
     * @return ModulesManager
     */
    public function addModule(string $vendorName, string $moduleName, string $path): self
    {
        $this->vendors[$vendorName][$moduleName] = $path;
        return $this;
    }

    /**
     * Get an array of all the modules listed under a Vendor.
     *
     * @param string $vendorName The Vendor name
     *
     * @return array
     */
    public function getVendorModules(string $vendorName): array
    {
        return $this->vendors[$vendorName];
    }

    /**
     * Get the root path of a module.
     *
     * @param string $vendorName The Vendor's name
     * @param string $moduleName The Module's name
     *
     * @return null|string
     */
    public function getModulePath(string $vendorName, string $moduleName): ?string
    {
        return $this->vendors[$vendorName][$moduleName];
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
    public function getAllModules(): \Generator
    {
        foreach ($this->vendors as $vendorName => $modules) {
            foreach ($modules as $moduleName => $path) {
                yield [$vendorName, $moduleName, $path];
            }
        }
    }
}
