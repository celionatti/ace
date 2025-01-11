<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ========= Ace Plugin Loader ===========
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\Plugins;

use Celionatti\Ace\View\View;

abstract class Plugin
{
    protected $name;
    protected $description;
    protected $path;
    protected View $view;

    public function __construct()
    {
        $this->name = $this->getName();
        $this->description = $this->getDescription();
        $this->path = $this->getPath();

        $this->view = new View($this->path);
        do_filter("view_output_home", []);
    }

    abstract protected function getName(): string;

    abstract protected function getPath(): string;

    abstract protected function getDescription(): string;

    public function initialize()
    {
        // Plugin-specific initialization logic (e.g., routes)
    }
}