<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ========= Ace Plugin Loader ===========
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\plugins;

class PluginLoader
{
    private static $instance;
    private $pluginDir;
    private $publicDir;
    private $loadedPlugins = [];

    /**
     * *************************
     * Singleton instance getter
     * *************************
    **/
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Set paths relative to the Composer project root
        $projectRoot = dirname(__DIR__, 2); // Adjust based on the folder structure
        $this->pluginDir = $projectRoot . '/plugins';
        $this->publicDir = $projectRoot . '/public/plugins';

        $this->ensurePublicDirectories();
        $this->loadPlugins();
    }

    /**
     * *************************
     * Ensure public directories
     * for plugin assets exist.
     * *************************
    **/
    private function ensurePublicDirectories()
    {
        if (!is_dir($this->publicDir)) {
            mkdir($this->publicDir, 0755, true);
        }
    }

    /**
     * *************************
     * Load all plugins
     * *************************
    **/
    private function loadPlugins()
    {
        $pluginFolders = glob($this->pluginDir . '/*', GLOB_ONLYDIR);
        foreach ($pluginFolders as $folder) {
            $manifestPath = $folder . '/manifest.json';
            if (file_exists($manifestPath)) {
                $this->registerPlugin($folder, $manifestPath);
            }
        }
    }

    /**
     * *************************
     * Register a plugin from its manifest
     * *************************
    **/
    private function registerPlugin($pluginPath, $manifestPath)
    {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (isset($manifest['entry_point'])) {
            $entryPoint = $pluginPath . '/' . $manifest['entry_point'];
            if (file_exists($entryPoint)) {
                require_once $entryPoint;
                $className = $manifest['class'] . ucfirst(basename($pluginPath));
                if (class_exists($className)) {
                    $pluginInstance = new $className();
                    $pluginInstance->initialize();
                    $this->loadedPlugins[$manifest['name']] = $pluginInstance;
                }
            }
        }
    }

    /**
     * ********************
     * Get loaded plugins
     * ********************
    **/
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }
}