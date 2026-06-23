<?php

namespace App\Middlewares;

use Ace\Application;
use Exception;

class CsrfMiddleware extends BaseMiddleware
{
    /**
     * Run the CSRF verification check
     */
    protected function run(): void
    {
        $request = Application::$app->request;

        if ($request->isPost()) {
            $body = $request->getBody();
            $submittedToken = $body['csrf_token'] ?? '';
            $sessionToken = Application::$app->session->getCsrfToken();

            if (empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
                throw new Exception("CSRF token validation failed. Unauthorized request.", 403);
            }
        }
    }
}

