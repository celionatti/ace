<?php

declare(strict_types=1);

/**
 * ==================================
 * SessionHandler ===================
 * ==================================
 */

namespace Ace\ace\Session;

use Ace\ace\Session\SessionInterface;

abstract class SessionHandler implements SessionInterface
{
    protected string $flashKey = '_ace_flash';
    protected string $formMessageKey = '__ace_form_message';

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->manageFlashData();
    }

    public function set(string $key, $value): void
    {
        $this->ensureSessionStarted();
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        $this->ensureSessionStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->ensureSessionStarted();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->ensureSessionStarted();
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy(): void
    {
        $this->ensureSessionStarted();
        session_unset();
        session_destroy();
    }

    public function regenerate(): void
    {
        $this->ensureSessionStarted();
        session_regenerate_id(true);
    }

    public function setArray(array $data): void
    {
        $this->ensureSessionStarted();
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public function unsetArray(array $keys): void
    {
        $this->ensureSessionStarted();
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    public function flash(string $key, $value): void
    {
        $this->ensureSessionStarted();
        $_SESSION[$this->flashKey][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        $this->ensureSessionStarted();
        return $_SESSION[$this->flashKey][$key] ?? $default;
    }

    public function keepFlash(string $key): void
    {
        $this->ensureSessionStarted();
        if (isset($_SESSION[$this->flashKey][$key])) {
            $_SESSION[$this->flashKey]['_keep'][$key] = $_SESSION[$this->flashKey][$key];
        }
    }

    private function manageFlashData(): void
    {
        $this->ensureSessionStarted();
        if (isset($_SESSION[$this->flashKey])) {
            foreach ($_SESSION[$this->flashKey] as $key => $value) {
                if ($key !== '_keep') {
                    unset($_SESSION[$this->flashKey][$key]);
                }
            }
            if (isset($_SESSION[$this->flashKey]['_keep'])) {
                foreach ($_SESSION[$this->flashKey]['_keep'] as $key => $value) {
                    $_SESSION[$this->flashKey][$key] = $value;
                }
                unset($_SESSION[$this->flashKey]['_keep']);
            }
        }
    }

    public function setFormMessage($value): void
    {
        $this->set($this->formMessageKey, $value) ?? null;
    }

    public function getFormMessage()
    {
        $value = $this->get($this->formMessageKey);
        $this->remove($this->formMessageKey);
        return $value ?? [];
    }

    public function setExpiration(int $minutes): void
    {
        $expirationTime = time() + ($minutes * 60);
        $this->set('__ace_session_expiration', $expirationTime);
    }

    public function checkExpiration(): void
    {
        $expirationTime = $this->get('__ace_session_expiration');
        if ($expirationTime !== null && time() > $expirationTime) {
            $this->destroy();
        }
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            throw new \RuntimeException('Session has not been started. Please call start() method before using session.');
        }
    }
}