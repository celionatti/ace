<?php

declare(strict_types=1);

namespace Ace\Illuminate; // Adjusted namespace casing

class CSRFGuard
{
    public function __construct()
    {
        $this->startSession(); // Ensure session is active
        $this->getToken();
    }

    /**
     * Start the session if not already active.
     */
    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Get the current CSRF token.
     */
    public function getToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Regenerate the CSRF token.
     */
    public function regenerateToken(): void
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Validate the provided token against the stored token.
     */
    public function validateToken(string $token): bool
    {
        return hash_equals($this->getToken(), $token);
    }

    /**
     * Generate a CSRF token meta tag.
     */
    public function metaTag(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars($this->getToken(), ENT_QUOTES) . '">';
    }

    /**
     * Generate a CSRF token hidden input field.
     */
    public function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($this->getToken(), ENT_QUOTES) . '">';
    }
}