<?php

declare(strict_types=1);

/**
 * =====================================================
 * =====================        ========================
 * Authentication
 * =====================        ========================
 * =====================================================
 */

namespace Ace\ace\Authentication;

use Exception;
use DateTime;
use PhpStrike\app\models\FailedLogin;
use PhpStrike\app\models\User;
use Ace\ace\Session\Handlers\DefaultSessionHandler;

class Authentication
{
    protected DefaultSessionHandler $session;
    protected FailedLogin $failedLogin;
    protected User $user;
    protected $loggedInUser;

    public function __construct()
    {
        $this->session = new DefaultSessionHandler();
        $this->failedLogin = new FailedLogin();
        $this->user = new User();
    }

    public function user(): ?array
    {
        if ($this->loggedInUser !== null) {
            return $this->loggedInUser;
        }

        $userId = $this->session->get("user_id");
        $sessionToken = $this->session->get("session_token");

        if ($userId && $sessionToken) {
            $user = $this->user->find($userId);

            if ($user && $user->session_token === $sessionToken) {
                $this->loggedInUser = $user->toArray();
                return $this->loggedInUser;
            } else {
                $this->logout();
                return null;
            }
        }

        $user = $this->autoLogin();
        if ($user) {
            $this->loggedInUser = $user;
            return $this->loggedInUser;
        }

        return null;
    }

    public function login(string $email, string $password, bool $rememberMe = false, string $redirect = null): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Invalid or empty credentials.',
                'type' => 'error',
                'redirect' => URL_ROOT . "{$redirect}"
            ];
        }

        $user = $this->user->findBy(['email' => $email]);

        if (!$user) {
            return $this->handleFailedLogin($email, 'User does not exist.');
        }

        $userArray = $user->toArray();

        if ($userArray['is_blocked'] && $this->isBlocked($email)) {
            return [
                'success' => false,
                'message' => 'Account is currently blocked.',
                'type' => 'warning',
                'redirect' => URL_ROOT . "{$redirect}"
            ];
        }

        if (!password_verify($password, $userArray['password'])) {
            return $this->handleFailedLogin($email, 'Invalid credentials.');
        }

        $this->resetFailedLogins($email);

        session_regenerate_id(true);
        $this->session->set("user_id", $userArray['user_id']);

        $sessionToken = bin2hex(random_bytes(32));
        $this->user->update(['session_token' => $sessionToken], $userArray['user_id'], "user_id");
        $this->session->set("session_token", $sessionToken);

        if ($rememberMe) {
            // Clear any existing remember tokens before setting new one
            $this->user->update(['remember_token' => null], $userArray['user_id'], "user_id");
            $this->setRememberMeToken($userArray['user_id']);
        }

        return [
            'success' => true,
            'message' => 'Login successful.',
            'type' => 'success',
            'redirect' => $redirect ?? URL_ROOT . '/dashboard'
        ];
    }

    private function isProductionEnvironment(): bool
    {
        return $_SERVER['APP_ENV'] === 'production' || bolt_env('APP_ENV') === 'production' ||
               $_SERVER['HTTP_HOST'] !== 'localhost' &&
               !str_contains($_SERVER['HTTP_HOST'], '.test');
    }

    private function clearRememberMeCookie(): void
    {
        $params = session_get_cookie_params();
        setcookie(REMEMBER_ME_NAME, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite']
        ]);
    }

    protected function setRememberMeToken($userId): void
    {
        // Clear existing remember token first
        $this->user->update(['remember_token' => null], $userId, "user_id");

        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        $this->user->update(['remember_token' => $hashedToken], $userId, "user_id");

        $isProduction = $this->isProductionEnvironment();
        $cookieParams = [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => $isProduction,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // Adjust for localhost development
        if (!$isProduction) {
            $cookieParams['secure'] = false;
            if ($_SERVER['HTTP_HOST'] === 'localhost') {
                $cookieParams['domain'] = false;
            }
        }

        setcookie(REMEMBER_ME_NAME, $token, $cookieParams);
    }

    public function autoLogin(): ?array
    {
        if (isset($_COOKIE[REMEMBER_ME_NAME])) {
            $token = $_COOKIE[REMEMBER_ME_NAME];
            $hashedToken = hash('sha256', $token);

            $user = $this->user->findBy(['remember_token' => $hashedToken]);

            if ($user) {
                // Generate new session token and update user
                $sessionToken = bin2hex(random_bytes(32));
                $this->user->update([
                    'session_token' => $sessionToken,
                    'remember_token' => $hashedToken // Keep current token
                ], $user->user_id, "user_id");

                session_regenerate_id(true);
                $this->session->set("user_id", $user->user_id);
                $this->session->set("session_token", $sessionToken);
                $this->loggedInUser = $user->toArray();

                return $this->loggedInUser;
            } else {
                // Invalid token - clear cookie
                $this->clearRememberMeCookie();
            }
        }
        return null;
    }

    public function logout(): void
    {
        $userId = $this->session->get("user_id");

        if ($userId) {
            $this->user->update([
                'remember_token' => null,
                'session_token' => null
            ], $userId, "user_id");
        }

        $this->session->remove("user_id");
        $this->session->remove("session_token");

        if (isset($_COOKIE[REMEMBER_ME_NAME])) {
            setcookie(REMEMBER_ME_NAME, '', time() - 3600, '/');
        }

        session_regenerate_id(true);
    }

    protected function handleFailedLogin(string $email, string $reason): array
    {
        $userExists = $this->user->findBy(['email' => $email]);
        if (!$userExists) {
            return ['success' => false, 'message' => 'User does not exist.', 'type' => 'info'];
        }

        $record = $this->failedLogin->findBy(['email' => $email]);

        if (!$record) {
            $this->failedLogin->create(['email' => $email, 'attempts' => 1, 'blocked_until' => null]);
        } else {
            $this->updateFailedLogin($record, $email);
        }

        return ['success' => false, 'message' => $reason, 'type' => 'warning'];
    }

    protected function updateFailedLogin($record, string $email): void
    {
        $attempts = $record->attempts + 1;
        $blockDuration = $this->calculateBlockDuration($attempts);
        $blockedUntil = $attempts > 4 ? (new DateTime())->modify("+$blockDuration minutes")->format('Y-m-d H:i:s') : null;

        $this->failedLogin->update(['attempts' => $attempts, 'blocked_until' => $blockedUntil], $email, "email");

        if ($attempts > 4) {
            $this->user->update(['is_blocked' => 1], $email, "email");
        }
    }

    protected function resetFailedLogins(string $email): void
    {
        $this->failedLogin->delete(['email' => $email]);
        $this->user->update(['is_blocked' => 0], $email, "email");
    }

    protected function calculateBlockDuration(int $attempts): int
    {
        return min(max(($attempts - 4) * 5, 5), 60);
    }

    public function isBlocked(string $email): bool
    {
        $record = $this->failedLogin->findBy(['email' => $email]);

        if (!$record || !$record['blocked_until']) {
            return false;
        }

        return (new DateTime($record['blocked_until'])) > new DateTime();
    }
}
