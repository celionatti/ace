<?php

declare(strict_types=1);

/**
 * =====================================
 * SessionServiceProvider Class ========
 * =====================================
 */

namespace Ace\Providers\Services;

use Ace\Providers\ServiceProvider;
use Ace\Session\Handlers\DefaultSessionHandler;

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