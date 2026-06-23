<?php

namespace Ace;

use App\Middlewares\BaseMiddleware;

class Controller
{
    public string $layout = 'main';
    public string $action = '';
    
    /**
     * @var BaseMiddleware[]
     */
    protected array $middlewares = [];

    /**
     * Set layout template for the controller
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Render a view from the controller
     */
    public function render(string $view, array $params = []): string
    {
        return Application::$app->router->renderView($view, $params);
    }

    /**
     * Register route middleware on the controller
     */
    public function registerMiddleware(BaseMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Get all registered middlewares for this controller
     * 
     * @return BaseMiddleware[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}

