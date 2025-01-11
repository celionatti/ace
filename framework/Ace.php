<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ======= Ace Core Application ==========
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace;

use Celionatti\Ace\Plugins\PluginLoader;

class Ace
{
    public function __construct()
    {
        $this->initialize();
    }

    private function initialize()
    {
        $this->require_files();
    }

    public function run()
    {
        $pluginLoader = PluginLoader::getInstance();
    }

    private function require_files()
    {
        return [
            require __DIR__ . "/functions.php",
        ];
    }
}