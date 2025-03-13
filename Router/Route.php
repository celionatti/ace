<?php

declare(strict_types=1);

namespace Ace\Router;

class Route
{
    private string $method;
    private string $pattern;
    private $handler;
    private array $params = [];
    private array $middleware = [];
    private ?string $name = null;
    private array $wheres = [];

    public function __construct(string $method, string $pattern, $handler)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
    }

    /**
     * Normalize URI by removing trailing slashes
     */
    public function normalizeUri(string $uri): string
    {
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Check if route matches the given method and URI
     */
    public function matches(string $method, string $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $uri = $this->normalizeUri($uri);
        $pattern = $this->normalizeUri($this->pattern);

        // Apply pattern constraints (wheres)
        $patternWithConstraints = $pattern;
        foreach ($this->wheres as $param => $regex) {
            $patternWithConstraints = str_replace(
                "{{$param}}",
                "(?<{$param}>{$regex})",
                $patternWithConstraints
            );
        }

        // Replace remaining standard parameters
        $patternWithConstraints = preg_replace(
            '/\{([a-zA-Z_]+)\}/',
            '(?<$1>[^/]+)',
            $patternWithConstraints
        );

        $regex = "#^{$patternWithConstraints}$#";
        return (bool)preg_match($regex, $uri, $this->params);
    }

    /**
     * Extract parameters from the URI
     */
    public function extractParams(string $uri): array
    {
        // Only extract if we haven't already from matches()
        if (empty($this->params)) {
            $this->matches($this->method, $uri);
        }

        return array_filter($this->params, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the route handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set middleware for this route
     */
    public function middleware(string|array $middleware): self
    {
        if (is_string($middleware)) {
            $this->middleware[] = $middleware;
        } else {
            $this->middleware = array_merge($this->middleware, $middleware);
        }

        return $this;
    }

    /**
     * Get middleware for this route
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Set name for this route
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get route method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get route pattern
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Add a constraint for a route parameter
     */
    public function where(string $param, string $regex): self
    {
        $this->wheres[$param] = $regex;
        return $this;
    }

    /**
     * Add multiple constraints for route parameters
     */
    public function whereMultiple(array $constraints): self
    {
        foreach ($constraints as $param => $regex) {
            $this->where($param, $regex);
        }
        return $this;
    }
}