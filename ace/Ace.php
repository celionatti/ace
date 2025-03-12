<?php

declare(strict_types=1);

namespace Ace\ace;

use Ace\ace\Http\Request;
use Ace\ace\Http\Response;
use Ace\ace\Router\Router;
use Ace\ace\Container\Container;
use Ace\ace\Exception\HttpException;
use Ace\ace\Exception\AceException;
use Ace\ace\Config\Config;
use Ace\ace\Database\Database;
use Ace\ace\View\View;

class Ace
{
    private static ?self $instance = null;
    private Request $request;
    private Response $response;
    private Router $router;
    private Container $container;
    private array $providers = [];
    private bool $booted = false;

    public function __construct()
    {
        $this->container = new Container();
        $this->initializeCore();
        $this->loadConfiguration();
        $this->loadEssentialProviders();
        $this->bootProviders();

        // Get instances from container
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

        $this->verifyAceKey();

        $this->dispatch();
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
        try {
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
        } catch (\Throwable $e) {  // Catch all exceptions and errors
            // --- Error Logging (if logger is available) ---
            if ($this->container->has('Logger')) {
                $logger = $this->container->get('Logger');
                $logger->error($e->getMessage(), ['exception' => $e]);
            }
            // -----------------------------------------------
            $code = $e instanceof HttpException ? $e->getCode() : 500;
            $message = "HTTP Error {$code}";

            // In production, it's a good practice to show generic error messages.
            if (ini_get('display_errors')) {
                $message .= ": " . $e->getMessage();
            }

            $this->response->setStatusCode($code)
                           ->html($message)
                           ->send();
        }
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