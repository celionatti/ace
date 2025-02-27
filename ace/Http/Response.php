<?php

declare(strict_types=1);

namespace Ace\ace\Http;

use Ace\ace\Exception\HttpException;

class Response
{
    private array $headers = [];
    private array $cookies = [];
    private int $statusCode = 200;
    private $content = '';
    private bool $sent = false;

    public function __construct()
    {
        $this->setDefaultHeaders();
    }

    public function html(string $content): self
    {
        $this->content = $content;
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        return $this;
    }

    public function json($data, int $status = 200): self
    {
        $this->content = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->statusCode = $status;
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->statusCode = $status;
        $this->setHeader('Location', $url);
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        if ($code < 100 || $code > 599) {
            throw new HttpException("Invalid HTTP status code: {$code}", 500);
        }
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[strtolower($name)] = $value;
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) {
            throw new HttpException('Response already sent');
        }

        $this->sent = true;

        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }

    private function setDefaultHeaders(): void
    {
        $this->headers = [
            'content-security-policy' => "default-src 'self'",
            'x-content-type-options' => 'nosniff',
            'x-frame-options' => 'DENY',
            'strict-transport-security' => 'max-age=31536000; includeSubDomains',
        ];
    }

    public function __destruct()
    {
        if (!$this->sent) {
            $this->send();
        }
    }
}