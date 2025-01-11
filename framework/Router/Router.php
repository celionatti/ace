<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ========== Ace Router =================
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\Router;


class Router
{
    private static $routes = [];

    /**
     * Add a route to the router.
     *
     * @param string $method - HTTP Method (GET, POST, etc.)
     * @param string $uri - Route URI (supports dynamic placeholders)
     * @param callable $callback - Callback to be executed for the route
     */
    public static function add(string $method, string $uri, callable $callback): void
    {
        // Convert the URI into a regular expression pattern
        $uriPattern = self::convertToRegex($uri);

        // Store the route
        self::$routes[] = [
            'method' => $method,
            'uri' => $uriPattern,
            'callback' => $callback,
        ];
    }

    /**
     * Dispatch the request to the corresponding route.
     *
     * @param string $requestMethod - The HTTP request method (GET, POST, etc.)
     * @param string $requestUri - The request URI
     */
    public static function dispatch(string $requestMethod, string $requestUri): void
    {
        foreach (self::$routes as $route) {
            // Check if the HTTP method matches
            if ($route['method'] === $requestMethod) {
                // Check if the route URI matches using regex
                if (preg_match($route['uri'], $requestUri, $matches)) {
                    // Extract matched parameters (excluding full URI match)
                    array_shift($matches);
                    // Call the route's callback with the matched parameters
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
            }
        }

        // If no route matches, output 404
        http_response_code(404);
        echo "<center><h3>404 - Page Not Found</h3></center>";
    }

    /**
     * Convert route URI to a regular expression for matching dynamic parts.
     *
     * @param string $uri - The route URI with placeholders
     * @return string - The regex pattern for the URI
     */
    private static function convertToRegex(string $uri): string
    {
        // Replace placeholders like {param} with regex capture groups
        $regex = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $uri);

        // Ensure the regex matches the entire URI (start and end)
        return "#^$regex$#";
    }

    /**
     * Get all registered routes (for debugging/logging).
     *
     * @return array - List of all routes
     */
    public static function getAllRoutes(): array
    {
        return self::$routes;
    }
}
