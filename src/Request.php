<?php

namespace Ace;

class Request
{
    /**
     * Get the relative request path, stripping base subdirectories and query params
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query parameters
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // Auto-detect and strip subdirectory path (e.g. /mvc/public/)
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /mvc/public/index.php
        $baseDir = dirname($scriptName);       // e.g., /mvc/public
        
        // Normalize backslashes to forward slashes just in case
        $baseDir = str_replace('\\', '/', $baseDir);
        
        if ($baseDir !== '/' && !empty($baseDir)) {
            if (strpos($path, $baseDir) === 0) {
                $path = substr($path, strlen($baseDir));
            }
        }

        // Ensure path starts with a slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Return '/' for root, otherwise strip trailing slash to match routes consistently
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Get HTTP request method (e.g. 'get', 'post')
     */
    public function method(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
    }

    public function isGet(): bool
    {
        return $this->method() === 'get';
    }

    public function isPost(): bool
    {
        return $this->method() === 'post';
    }

    /**
     * Retrieve and sanitize request input parameters
     */
    public function getBody(): array
    {
        $body = [];
        
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = $this->sanitize($value);
            }
        }
        
        if ($this->isPost()) {
            // Check if request is JSON
            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $rawJson = file_get_contents('php://input');
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        $body[$key] = $this->sanitize($value);
                    }
                }
            } else {
                foreach ($_POST as $key => $value) {
                    $body[$key] = $this->sanitize($value);
                }
            }
        }

        return $body;
    }

    /**
     * Retrieve raw, unsanitized request input parameters
     */
    public function getRawBody(): array
    {
        $body = [];
        
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = $value;
            }
        }
        
        if ($this->isPost()) {
            // Check if request is JSON
            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $rawJson = file_get_contents('php://input');
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        $body[$key] = $value;
                    }
                }
            } else {
                foreach ($_POST as $key => $value) {
                    $body[$key] = $value;
                }
            }
        }

        return $body;
    }

    /**
     * Recursively sanitize values to prevent XSS
     */
    private function sanitize($value)
    {
        return XssSanitizer::clean($value);
    }
}

