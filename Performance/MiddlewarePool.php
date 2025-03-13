<?php

declare(strict_types=1);

namespace Ace\Performance;

use Ace\Container\Container;

class MiddlewarePool
{
    protected array $pool = [];
    protected array $loading = [];
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $middleware): object
    {
        if (isset($this->pool[$middleware])) {
            return $this->pool[$middleware];
        }

        if (isset($this->loading[$middleware])) {
            throw new \RuntimeException("Circular middleware dependency detected");
        }

        $this->loading[$middleware] = true;
        $instance = $this->container->make($middleware);
        $this->pool[$middleware] = $instance;
        unset($this->loading[$middleware]);

        return $instance;
    }

    public function preload(string $middleware): void
    {
        if (!isset($this->pool[$middleware])) {
            $this->get($middleware);
        }
    }

    public function remove(string $middleware): void
    {
        unset($this->pool[$middleware]);
    }

    public function clear(): void
    {
        $this->pool = [];
    }
}