<?php

declare(strict_types=1);

/**
 * =====================================
 * RouteServiceProvider Class ==========
 * =====================================
 */

namespace Ace\Providers\Services;

use Ace\Providers\ServiceProvider;
use Ace\Router\Router;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {
        // No need to register anything here
    }

    public function boot()
    {
        /** @var Router $router */
        $router = $this->app->get(Router::class);

        $this->loadWebRoutes($router);
        $this->loadApiRoutes($router);
    }

    protected function loadWebRoutes(Router $router)
    {
        // require base_path('routes/web.php');
        require BASE_PATH . '/routes/web.php';
    }

    protected function loadApiRoutes(Router $router)
    {
        require base_path('routes/api.php');
    }
}