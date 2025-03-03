<?php

declare(strict_types=1);

/**
 * =====================================
 * SessionServiceProvider Class ========
 * =====================================
 */

namespace Ace\ace\Providers\Services;

use Ace\ace\Providers\ServiceProvider;
use Ace\ace\Session\Handlers\DefaultSessionHandler;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('sessions', function($app) {
            return new DefaultSessionHandler();
        });
    }

    public function boot()
    {
        $this->app->make('sessions');
    }
}