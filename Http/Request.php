<?php

declare(strict_types=1);

/**
 * =================================
 * Ace - Request Class ===========
 * =================================
 */

namespace Ace\Http;

use Ace\Validation\Validator;
use Ace\Exception\SecurityException;

class Request
{
    private array $queryParams;
    private array $bodyParams;
    private array $serverParams;
    private array $cookies;
    private array $files;
    private string $method;
    private string $path;
    private string $content;
    private array $headers;
    private array $errors = [];
    private array $validated = [];

    public function __construct(
        array $query = [],
        array $body = [],
        array $server = [],
        array $cookies = [],
        array $files = [],
        string $content = ''
    ) {
        $this->queryParams = $this->sanitizeArray($query);
        $this->bodyParams = $this->sanitizeArray($body);
        $this->serverParams = $server;
        $this->cookies = $this->sanitizeArray($cookies);
        $this->files = $this->processFiles($files);
        $this->content = $content;
        $this->method = $this->determineMethod();
        $this->path = $this->determinePath();
        $this->headers = $this->parseHeaders();
    }

    public static function createFromGlobals(): self
    {
        return new self(
            $_GET,
            self::parseRequestBody(),
            $_SERVER,
            $_COOKIE,
            $_FILES,
            file_get_contents('php://input') ?: ''
        );
    }

    private static function parseRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');

        if (str_starts_with($contentType, 'application/json')) {
            return json_decode($input, true) ?? [];
        }

        if (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($input, $data);
            return $data;
        }

        return $_POST;
    }

    public function validate(array $rules, bool $throw = true): bool
    {
        $validator = new Validator($this->all(), $rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            if ($throw) {
                throw new SecurityException('Validation failed', 422, $this->errors);
            }
            return false;
        }

        $this->validated = $validator->validated();
        return true;
    }

    public function validated(): array
    {
        if (empty($this->validated)) {
            throw new \LogicException('No validated data available');
        }
        return $this->validated;
    }

    public function all(): array
    {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): ?UploadedFile
    {
        return $this->files[$key] ?? null;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept', ''), 'application/json');
    }

    public function prefersJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json') ||
               str_contains($accept, 'application/*') ||
               str_contains($accept, '*/*');
    }

    public function isSecure(): bool
    {
        return ($this->serverParams['HTTPS'] ?? '') !== 'off'
            || ($this->serverParams['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function ip(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if ($ip = $this->serverParams[$key] ?? null) {
                return filter_var($ip, FILTER_VALIDATE_IP) ?: '';
            }
        }
        return '';
    }

    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        return preg_match('/Bearer\s+(\S+)/', $header, $matches) ? $matches[1] : null;
    }

    private function determineMethod(): string
    {
        $method = strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            return strtoupper($this->serverParams['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $method);
        }

        return $method;
    }

    private function determinePath(): string
    {
        $path = parse_url($this->serverParams['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim(rawurldecode($path), '/');
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->serverParams as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[strtolower($name)] = $value;
            }
        }
        return $headers;
    }

    private function sanitizeArray(array $data): array
    {
        return array_map(fn($value) => $this->sanitize($value), $data);
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }

        if (is_numeric($value)) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        $value = strip_tags((string)$value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function processFiles(array $files): array
    {
        $processed = [];
        foreach ($files as $key => $file) {
            $processed[$key] = new UploadedFile(
                $file['tmp_name'],
                $file['name'],
                $file['type'],
                $file['error'],
                $file['size']
            );
        }
        return $processed;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDebugInfo(): array
    {
        return [
            'url' => $this->getPath(),
            'method' => $this->getMethod(),
            'query_params' => $this->queryParams,
            'body_params' => $this->bodyParams,
            'headers' => $this->headers,
            'cookies' => $this->cookies,
            'files' => array_map(function($file) {
                return [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getClientMimeType()
                ];
            }, $this->files)
        ];
    }
}
