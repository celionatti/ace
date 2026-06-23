<?php

namespace App\Middlewares;

use Ace\Application;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * Run the authentication middleware check
     */
    protected function run(): void
    {
        if (Application::isGuest()) {
            Application::$app->session->setFlash('error', 'Please log in to view that page.');
            Application::$app->response->redirect('/login');
        }
    }
}

