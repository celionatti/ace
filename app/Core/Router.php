<?php

declare(strict_types=1);

/**
 * Framework: ACE Framework
 * Author: Celio Natti
 * Year:   2024
 * Version: 1.0.0
 */

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function FastRoute\simpleDispatcher;

class Router
{
    protected $dispatcher;
    protected $middlewares = [];

    public function __construct() {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
            require_once '../app/Routes/web.php';
        });
    }

    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }

    public function dispatch(Request $request) {
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(), 
            $request->getPathInfo()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response('Not Found', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response('Method Not Allowed', 405);
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                $response = $this->handleMiddlewares($request, function($request) use ($handler, $vars) {
                    [$controller, $method] = explode('@', $handler);
                    $controller = new $controller;
                    // Inject the request object into the controller method
                    return call_user_func_array([$controller, $method], array_merge([$request], $vars));
                });

                return $response instanceof Response ? $response : new Response($response);
        }
    }

    protected function handleMiddlewares($request, $next) {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn ($next, $middleware) => fn ($request) => $middleware::handle($request, $next),
            $next
        );

        return $pipeline($request);
    }
}
