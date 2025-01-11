<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ========== Ace Router =================
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\router;


class Router
{
    private static $routes = [];

    public static function add($method, $uri, $callback)
    {
        self::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback,
        ];
    }

    public static function dispatch($requestMethod, $requestUri)
    {
        foreach (self::$routes as $route) {
            if ($route['method'] === $requestMethod && $route['uri'] === $requestUri) {
                call_user_func($route['callback']);
                return;
            }
        }

        // If no route matches, output 404
        http_response_code(404);
        echo "<center><h3>404 - Page Not Found</h3></center>";
    }
}
