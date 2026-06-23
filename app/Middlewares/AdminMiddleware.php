<?php

namespace App\Middlewares;

use Ace\Application;

/**
 * Convenience middleware that restricts access to admin users only.
 * Uses the RBAC system under the hood via User::hasRole('admin').
 */
class AdminMiddleware extends BaseMiddleware
{
    protected function run(): void
    {
        if (Application::isGuest()) {
            Application::$app->session->setFlash('error', 'Please login to access this page.');
            Application::$app->response->redirect('/login');
            exit;
        }

        if (!Application::$app->user->hasRole('admin')) {
            throw new \Exception("Forbidden. Admin access required.", 403);
        }
    }
}

