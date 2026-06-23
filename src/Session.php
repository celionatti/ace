<?php

namespace Ace;

class Session
{
    protected const FLASH_KEY = 'flash_messages';

    public function __construct()
    {
        if (php_sapi_name() !== 'cli') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
        }

        // Retrieve flash messages and mark them to be removed
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    /**
     * Set a flash message
     */
    public function setFlash(string $key, string $message): void
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    /**
     * Get a flash message
     */
    public function getFlash(string $key): mixed
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**
     * Set a standard session variable
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Remove a session variable
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if a session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Retrieve or generate CSRF token
     */
    public function getCsrfToken(): string
    {
        $token = $this->get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->set('csrf_token', $token);
        }
        return $token;
    }

    /**
     * Clean up expired flash messages at the end of the request lifecycle
     */
    public function __destruct()
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            }
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }
}

