<?php

declare(strict_types=1);

namespace Ace\ace\Router;

use Ace\ace\Exception\HttpException;
use Ace\ace\Http\Request;
use Ace\ace\Router\Route;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $pattern, $handler): void
    {
        $this->routes[] = new Route($method, $pattern, $handler);
    }

    public function resolve(Request $request)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($request->getMethod(), $request->getPath())) {
                $params = $route->extractParams($request->getPath());
                return [
                    'handler' => $route->getHandler(),
                    'params' => $params
                ];
            }
        }

        throw new HttpException('Route not found', 404);
    }
}