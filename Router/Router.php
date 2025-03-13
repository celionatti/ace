<?php

declare(strict_types=1);

namespace Ace\Router;

use Ace\Exception\HttpException;
use Ace\Http\Request;
use Ace\Router\Route;
use Ace\Router\RouteGroup;
use Closure;

class Router
{
    private array $routes = [];
    private array $routesByMethod = []; // Improvement: index routes by HTTP method
    private array $namedRoutes = [];
    private array $groupStack = [];
    private array $patterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'alpha' => '[a-zA-Z]+',
        'alphanumeric' => '[a-zA-Z0-9]+'
    ];

    /**
     * Add a new route
     */
    public function addRoute(string $method, string $pattern, $handler): Route
    {
        // Apply group prefix if any
        $pattern = $this->applyGroupPrefix($pattern);

        $route = new Route($method, $pattern, $handler);

        // Apply group middleware if any
        $middleware = $this->getGroupMiddleware();
        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        // Apply group where constraints if any
        $constraints = $this->getGroupWhereConstraints();
        if (!empty($constraints)) {
            $route->whereMultiple($constraints);
        }

        $this->routes[] = $route;

        // Improvement: Index the route by HTTP method for faster lookups
        $upperMethod = strtoupper($method);
        if (!isset($this->routesByMethod[$upperMethod])) {
            $this->routesByMethod[$upperMethod] = [];
        }
        $this->routesByMethod[$upperMethod][] = $route;

        return $route;
    }

    /**
     * Resolve a route from a request
     */
    public function resolve($request)
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Check if the method exists in the routesByMethod array
        if (!isset($this->routesByMethod[$method])) {
            // Could add logging here to debug
            // For example: error_log("No routes found for method: $method, path: $path");
            throw new HttpException("No routes registered for method: $method", 404);
        }

        $routes = $this->routesByMethod[$method];

        foreach ($routes as $route) {
            if ($route->matches($method, $path)) {
                $params = $route->extractParams($path);
                return [
                    'handler' => $route->getHandler(),
                    'params' => $params,
                    'middleware' => $route->getMiddleware()
                ];
            }
        }

        // Could add logging here to debug
        // error_log("Path '$path' did not match any routes for method: $method");
        throw new HttpException("Route not found for path: $path", 404);
    }

    /**
     * Add a GET route
     */
    public function get(string $pattern, $handler): Route
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Add a POST route
     */
    public function post(string $pattern, $handler): Route
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Add a PUT route
     */
    public function put(string $pattern, $handler): Route
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * Add a PATCH route
     */
    public function patch(string $pattern, $handler): Route
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $pattern, $handler): Route
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Add a route for multiple HTTP verbs
     */
    public function match(array $methods, string $pattern, $handler): array
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[] = $this->addRoute(strtoupper($method), $pattern, $handler);
        }
        return $routes;
    }

    /**
     * Add a route that responds to all HTTP methods
     */
    public function any(string $pattern, $handler): array
    {
        return $this->match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $pattern, $handler);
    }

    /**
     * Define a route group with shared attributes
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = new RouteGroup(
            $attributes['prefix'] ?? '',
            $attributes['middleware'] ?? [],
            $attributes['where'] ?? [],
            $attributes['as'] ?? null
        );

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Define a route prefix group
     */
    public function prefix(string $prefix, Closure $callback): void
    {
        $this->group(['prefix' => $prefix], $callback);
    }

    /**
     * Define a middleware group
     */
    public function middleware(string|array $middleware, Closure $callback): void
    {
        $this->group(['middleware' => (array)$middleware], $callback);
    }

    /**
     * Define a name prefix group
     */
    public function name(string $name, Closure $callback): void
    {
        $this->group(['as' => $name], $callback);
    }

    /**
     * Apply patterns to routes
     */
    public function pattern(string $key, string $pattern): void
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Get a route by name
     */
    public function getRouteByName(string $name)
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Register a named route
     */
    public function registerNamedRoute(string $name, Route $route): void
    {
        // Apply group name prefix if any
        $namePrefix = $this->getGroupNamePrefix();
        if ($namePrefix) {
            $name = $namePrefix . $name;
        }

        $this->namedRoutes[$name] = $route;
        $route->name($name);
    }

    /**
     * Generate URL for a named route
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->getRouteByName($name);

        if (!$route) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $uri = $route->getPattern();

        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", (string)$value, $uri);
        }

        // Replace any remaining route parameters with empty strings
        $uri = preg_replace('/\{[^}]+\}/', '', $uri);

        return $uri;
    }

    /**
     * Apply group prefix to a pattern
     */
    private function applyGroupPrefix(string $pattern): string
    {
        if (empty($this->groupStack)) {
            return $pattern;
        }

        $prefix = '';
        foreach ($this->groupStack as $group) {
            $prefix .= $group->getPrefix();
        }

        if (empty($prefix)) {
            return $pattern;
        }

        return rtrim($prefix, '/') . '/' . ltrim($pattern, '/');
    }

    /**
     * Get middleware from all active groups
     */
    private function getGroupMiddleware(): array
    {
        if (empty($this->groupStack)) {
            return [];
        }

        $middleware = [];
        foreach ($this->groupStack as $group) {
            $middleware = array_merge($middleware, $group->getMiddleware());
        }

        return $middleware;
    }

    /**
     * Get where constraints from all active groups
     */
    private function getGroupWhereConstraints(): array
    {
        if (empty($this->groupStack)) {
            return [];
        }

        $constraints = [];
        foreach ($this->groupStack as $group) {
            $constraints = array_merge($constraints, $group->getWhereConstraints());
        }

        return $constraints;
    }

    /**
     * Get name prefix from all active groups
     */
    private function getGroupNamePrefix(): ?string
    {
        if (empty($this->groupStack)) {
            return null;
        }

        $prefix = '';
        foreach ($this->groupStack as $group) {
            if ($group->getNamePrefix()) {
                $prefix .= $group->getNamePrefix();
            }
        }

        return $prefix ?: null;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get all named routes
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }
}