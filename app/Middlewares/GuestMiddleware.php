<?php

namespace App\Middlewares;

use Ace\Application;

class GuestMiddleware extends BaseMiddleware
{
    /**
     * Run the guest middleware check
     */
    protected function run(): void
    {
        if (!Application::isGuest()) {
            Application::$app->response->redirect('/profile');
        }
    }
}

