<?php

declare(strict_types=1);

namespace Ace;

use Ace\Http\Request;
use Ace\Http\Response;
use Ace\Router\Router;
use Ace\Container\Container;
use Ace\Exception\HttpException;
use Ace\Exception\AceException;
use Ace\Config\Config;
use Ace\Database\Database;
use Ace\View\View;
use Ace\Exception\Handler\ExceptionHandler;

class Ace
{
    private static ?self $instance = null;
    private Request $request;
    private Response $response;
    private Router $router;
    private Container $container;
    private array $providers = [];
    private bool $booted = false;
    private ExceptionHandler $exceptionHandler;

    public function __construct()
    {
        $this->container = new Container();
        $this->initializeCore();
        $this->loadConfiguration();
        $this->loadEssentialProviders();
        $this->bootProviders();

        // Get instances from container
        $this->exceptionHandler = $this->container->get(ExceptionHandler::class);
        $this->request = $this->container->get(Request::class);
        $this->response = $this->container->get(Response::class);
        $this->router = $this->container->get(Router::class);
        $this->container->get(Database::class);
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeCore()
    {
        require __DIR__ . "/load.php";
        require __DIR__ . "/functions.php";
        $this->container->singleton(Config::class, fn() => new Config());
        $this->container->singleton(Request::class, function () {
            return Request::createFromGlobals();
        });
        $this->container->singleton(Response::class);
        $this->container->singleton(Router::class);
        $this->container->singleton(Database::class, function () {
            $config = $this->container->get(Config::class);
            if (!Database::init($config->get('database.default'), $config->get('database.connections.mysql'))) {
                throw new AceException("Failed to initialize database connection");
            }
            return new Database($config->get('database.default'), $config->get('database.connections.mysql'));
        });
        $this->container->singleton(View::class);
        $this->container->singleton(ExceptionHandler::class, function () {
            return new ExceptionHandler($_ENV['APP_ENV'] ?? 'production');
        });
    }

    private function loadConfiguration(): void
    {
        $config = $this->container->get(Config::class);
        $config->loadMultiple([
            $this->getConfigPath('app'),
            $this->getConfigPath('database'),
            // $this->getConfigPath('security'),
        ]);
    }

    private function loadEssentialProviders(): void
    {
        $providers = require $this->getConfigPath('providers');

        foreach ($providers as $provider) {
            $this->registerProvider(new $provider($this->container));
        }
    }

    public function registerProvider($provider): void
    {
        $this->providers[] = $provider;
        $provider->register();
    }

    private function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
        $this->booted = true;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        if (!$this->booted) {
            throw new AceException("Application not booted properly");
        }

        try {
            $this->verifyAceKey();
            $this->dispatch();
        } catch (\Throwable $e) {
            // Log the exception if logging is enabled
            if ($this->container->has('Logger')) {
                $logger = $this->container->get('Logger');
                $logger->error($e->getMessage(), ['exception' => $e]);
            }

            // Use the HTTP-aware exception handling
            $this->exceptionHandler->handleHttpException($e, $this->request);
        }
    }

    private function verifyAceKey(): void
    {
        if (empty(env('APP_KEY'))) {
            throw new AceException(
                "Application key missing. Generate one with 'php ace generate:key'",
                500
            );
        }
    }

    private function dispatch()
    {
        $routeInfo = $this->router->resolve($this->request);

        // --- Middleware Processing Start ---
        foreach ($routeInfo['middleware'] as $middleware) {
            // If middleware is a callable, execute it.
            if (is_callable($middleware)) {
                $middleware($this->request, $this->response);
            }
            // Otherwise, if it's a class name, resolve and execute it.
            elseif (class_exists($middleware)) {
                $instance = new $middleware();
                if (method_exists($instance, 'handle')) {
                    $instance->handle($this->request, $this->response);
                }
            }
        }
        // --- Middleware Processing End ---

        $handler = $routeInfo['handler'];
        $params = $routeInfo['params'];

        if (is_callable($handler)) {
            $handler($this->request, $this->response, ...array_values($params));
        } elseif (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;

            if (!class_exists($controllerClass)) {
                throw new HttpException("Controller {$controllerClass} not found", 500);
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                throw new HttpException("Method {$method} not found in {$controllerClass}", 500);
            }

            $controller->$method($this->request, $this->response, ...array_values($params));
        } else {
            throw new HttpException('Invalid route handler', 500);
        }

        $this->response->send();
    }

    private function getConfigPath(string $name): string
    {
        return config_path("{$name}.php");
    }

    public function isProduction(): bool
    {
        return env('APP_ENV') === 'production';
    }

    public function __get(string $name)
    {
        return $this->container->get($name);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    private function __clone() {}
    public function __wakeup() {}
}