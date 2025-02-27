<?php

declare(strict_types=1);

namespace Ace\ace\Router;

class Route
{
    private string $method;
    private string $pattern;
    private $handler;
    private array $params;

    public function __construct(string $method, string $pattern, $handler)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->params = [];
    }

    public function normalizeUri(string $uri): string
    {
        return rtrim($uri, '/') ?: '/';
    }

    public function matches(string $method, string $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $uri = $this->normalizeUri($uri);
        $pattern = $this->normalizeUri($this->pattern); // Normalize pattern too
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?<$1>[^/]+)', $pattern);
        $regex = "#^{$pattern}$#";

        return (bool)preg_match($regex, $uri, $this->params); // Store matches in $this->params
    }

    public function extractParams(string $uri): array
    {
        // Only extract if we haven't already from matches()
        if (empty($this->params)) {
            $uri = $this->normalizeUri($uri);
            $pattern = $this->normalizeUri($this->pattern);
            $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?<$1>[^/]+)', $pattern);
            $regex = "#^{$pattern}$#";

            preg_match($regex, $uri, $this->params);
        }

        return array_filter($this->params, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    public function getHandler()
    {
        return $this->handler;
    }
}