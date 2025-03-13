<?php

declare(strict_types=1);

namespace Ace\ace\Config;

use Ace\ace\Exception\ConfigException;

class Config
{
    private array $config = [];

    public function loadMultiple(array $files): void
    {
        foreach ($files as $file) {
            $this->load($file);
        }
    }

    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new ConfigException("Config file not found: {$path}");
        }

        $key = pathinfo($path, PATHINFO_FILENAME);
        $this->config[$key] = require $path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $this->config;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function all(): array
    {
        return $this->config;
    }
}