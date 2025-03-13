<?php

declare(strict_types=1);

namespace Ace\Security;

class MiddlewareSignature
{
    protected static $trustedMiddleware = [];
    protected static $signatureKey;

    public static function initialize(string $signatureKey, array $trustedMiddleware): void
    {
        self::$signatureKey = $signatureKey;
        self::$trustedMiddleware = $trustedMiddleware;
    }

    public static function verify(string $middleware): void
    {
        if (!in_array($middleware, self::$trustedMiddleware)) {
            throw new \RuntimeException("Untrusted middleware: {$middleware}");
        }

        $hash = hash_hmac('sha256', $middleware, self::$signatureKey);
        if (!hash_equals($hash, self::getStoredHash($middleware))) {
            throw new \RuntimeException("Middleware signature verification failed: {$middleware}");
        }
    }

    public static function validateRuntime(string $middleware): void
    {
        $filePath = self::getMiddlewarePath($middleware);
        $currentHash = hash_file('sha256', $filePath);

        if ($currentHash !== self::getStoredHash($middleware)) {
            throw new \RuntimeException("Middleware file tampered with: {$middleware}");
        }
    }

    private static function getStoredHash(string $middleware): string
    {
        // Implementation to retrieve stored hashes (e.g., from secure config)
        return config("middleware_hashes.{$middleware}");
    }

    private static function getMiddlewarePath(string $middleware): string
    {
        // Implementation to resolve middleware class to file path
        return (new \ReflectionClass($middleware))->getFileName();
    }
}