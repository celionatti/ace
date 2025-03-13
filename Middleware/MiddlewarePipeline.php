<?php

declare(strict_types=1);

namespace Ace\ace\Middleware;

use Ace\ace\Http\Request;
use Ace\ace\Container\Container;
use Ace\ace\Exception\AceException;
use Ace\ace\Security\MiddlewareSignature;
use Ace\ace\Performance\MiddlewarePool;

class MiddlewarePipeline
{
    protected array $middleware;
    protected Container $container;
    protected array $excludedMiddleware = [];
    protected MiddlewarePool $pool;
    protected array $verifiedMiddleware = [];

    public function __construct(array $middleware, Container $container)
    {
        $this->middleware = $middleware;
        $this->container = $container;
    }

    protected function validateMiddleware(array $middleware): array
    {
        return array_map(function ($mw) {
            if (is_string($mw)) {
                MiddlewareSignature::verify($mw);
                $this->verifiedMiddleware[] = $mw;
            }
            return $mw;
        }, $middleware);
    }

    public function handle(Request $request, \Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($request);
    }

    protected function carry(): \Closure
    {
        return function ($stack, $middleware) {
            return function ($request) use ($stack, $middleware) {
                [$name, $parameters] = $this->parseMiddleware($middleware);
                $instance = $this->container->make($name);

                return $instance->handle($request, $stack, ...$parameters);
            };
        };
    }

    protected function validateMiddlewareInstance($instance, string $name): void
    {
        if (!method_exists($instance, 'handle')) {
            $this->pool->remove($name);
            throw new AceException("Middleware [{$name}] must implement handle() method");
        }
    }

    protected function checkMiddlewareSafety(string $name): void
    {
        if (in_array($name, $this->verifiedMiddleware)) {
            MiddlewareSignature::validateRuntime($name);
        }
    }

    protected function parseMiddleware($middleware): array
    {
        if (is_string($middleware) && str_contains($middleware, ':')) {
            return explode(':', $middleware, 2);
        }

        return [$middleware, []];
    }

    protected function prepareDestination(\Closure $destination): \Closure
    {
        return function ($request) use ($destination) {
            try {
                $response = $destination($request);
            } catch (\Throwable $e) {
                $response = $this->handleException($request, $e);
            }

            return $this->handleResponse($request, $response);
        };
    }

    protected function handleException(Request $request, \Throwable $e)
    {
        throw $e;
    }

    protected function handleResponse(Request $request, $response)
    {
        $this->runAsyncTerminations($request, $response);
        return $response;
    }

    protected function runAsyncTerminations(Request $request, $response): void
    {
        foreach ($this->middleware as $middleware) {
            if (!is_string($middleware)) continue;

            $instance = $this->pool->get($middleware);
            if (method_exists($instance, 'terminate')) {
                $this->container->get('async')->run(
                    fn() => $instance->terminate($request, $response)
                );
            }
        }
    }

    public function warmup(): void
    {
        foreach ($this->middleware as $middleware) {
            if (is_string($middleware)) {
                $this->pool->preload($middleware);
            }
        }
    }

    public function exclude(array $middleware): self
    {
        $this->excludedMiddleware = $middleware;
        return $this;
    }

    protected function shouldSkipMiddleware($middleware): bool
    {
        if (!is_string($middleware)) return false;

        $name = is_array($middleware) ? $middleware[0] : $middleware;

        return in_array($name, $this->excludedMiddleware);
    }
}