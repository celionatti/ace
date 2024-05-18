<?php

declare(strict_types=1);

/**
 * Framework: ACE Framework
 * Author: Celio Natti
 * Year:   2024
 * Version: 1.0.0
 */

namespace App\Middlewares;

use App\Core\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware extends Middleware
{
    public static function handle(Request $request, callable $next)
    {
        // Authentication logic here
        if (true) { // Replace with actual auth logic
            return $next($request);
        }

        return new Response('Unauthorized', 401);
    }
}
