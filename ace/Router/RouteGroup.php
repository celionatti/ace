<?php

declare(strict_types=1);

namespace Ace\ace\Router;

use Ace\ace\Exception\HttpException;
use Ace\ace\Http\Request;
use Ace\ace\Router\Route;

class RouteGroup
{
    private string $prefix;
    private array $middleware;
    private array $whereConstraints;
    private ?string $namePrefix;

    public function __construct(string $prefix = '', array $middleware = [], array $whereConstraints = [], ?string $namePrefix = null) {
        $this->prefix = $prefix;
        $this->middleware = $middleware;
        $this->whereConstraints = $whereConstraints;
        $this->namePrefix = $namePrefix;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getWhereConstraints(): array
    {
        return $this->whereConstraints;
    }

    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }
}