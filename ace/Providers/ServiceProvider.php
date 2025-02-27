<?php

declare(strict_types=1);

/**
 * =====================================
 * ServiceProvider Class ===============
 * =====================================
 */

namespace Ace\ace\Providers;

abstract class ServiceProvider
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    abstract public function register();

    public function boot()
    {
        // Optional boot method
    }
}