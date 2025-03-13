<?php

declare(strict_types=1);

/**
 * =====================================================
 * =====================        ========================
 * Authentication
 * =====================        ========================
 * =====================================================
 */

namespace Ace\Authentication;

use DateTime;
use PDO;
use PDOException;
use Exception;
use Ace\Session\Handlers\DefaultSessionHandler;

class Authentication
{
    protected DefaultSessionHandler $session;
    protected ?array $loggedInUser = null;
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        // Define constants if not already defined
        if (!defined('REMEMBER_ME_NAME')) {
            define('REMEMBER_ME_NAME', 'remember_me');
        }
        if (!defined('URL_ROOT')) {
            define('URL_ROOT', 'http://localhost');
        }

        $this->session = new DefaultSessionHandler();

        // Use provided PDO or create a default SQLite connection
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $this->pdo = new PDO('sqlite:' . __DIR__ . '/auth.sqlite');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->initializeDatabase();
        }
    }

    /**
     * Creates the necessary tables if they don't exist.
     */
    protected function initializeDatabase(): void
    {
        // Create users table. Note: "remember_tokens" stores a JSON-encoded array.
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE,
                password TEXT,
                is_blocked INTEGER DEFAULT 0,
                session_token TEXT,
                remember_tokens TEXT
            )
        ");

        // Create failed_logins table.
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS failed_logins (
                email TEXT PRIMARY KEY,
                attempts INTEGER,
                blocked_until TEXT
            )
        ");
    }

    /**
     * Returns the currently logged in user if available.
     */
    public function user(): ?array
    {
        if ($this->loggedInUser !== null) {
            return $this->loggedInUser;
        }

        $userId = $this->session->get("user_id");
        $sessionToken = $this->session->get("session_token");

        if ($userId && $sessionToken) {
            $user = $this->getUserById((int)$userId);
            if ($user && $user['session_token'] === $sessionToken) {
                $this->loggedInUser = $user;
                return $this->loggedInUser;
            } else {
                $this->logout();
                return null;
            }
        }

        // Attempt auto-login if a remember me cookie exists.
        $user = $this->autoLogin();
        if ($user) {
            $this->loggedInUser = $user;
            return $this->loggedInUser;
        }

        return null;
    }

    /**
     * Processes a login attempt.
     */
    public function login(string $email, string $password, bool $rememberMe = false, string $redirect = null): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            return $this->response(false, "Invalid or empty credentials.", "error", $redirect);
        }

        $user = $this->getUserByEmail($email);
        if (!$user) {
            return $this->handleFailedLogin($email, "User does not exist.");
        }

        if ($user['is_blocked'] && $this->isBlocked($email)) {
            return $this->response(false, "Account is currently blocked.", "warning", $redirect);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->handleFailedLogin($email, "Invalid credentials.");
        }

        // Reset failed login attempts on successful login.
        $this->resetFailedLogins($email);

        session_regenerate_id(true);
        $this->session->set("user_id", $user['user_id']);

        $sessionToken = bin2hex(random_bytes(32));
        $this->updateUser($user['user_id'], ['session_token' => $sessionToken]);
        $this->session->set("session_token", $sessionToken);

        if ($rememberMe) {
            // For multi-device support, simply add a new token without clearing existing ones.
            $this->setRememberMeToken($user['user_id']);
        }

        return $this->response(true, "Login successful.", "success", $redirect ?? URL_ROOT . "/dashboard");
    }

    /**
     * Attempts auto-login based on the remember me cookie.
     */
    public function autoLogin(): ?array
    {
        if (isset($_COOKIE[REMEMBER_ME_NAME])) {
            $token = $_COOKIE[REMEMBER_ME_NAME];
            $hashedToken = hash('sha256', $token);

            $user = $this->getUserByRememberToken($hashedToken);
            if ($user) {
                // Generate a new session token.
                $sessionToken = bin2hex(random_bytes(32));
                $this->updateUser($user['user_id'], ['session_token' => $sessionToken]);

                session_regenerate_id(true);
                $this->session->set("user_id", $user['user_id']);
                $this->session->set("session_token", $sessionToken);
                $this->loggedInUser = $user;
                return $this->loggedInUser;
            } else {
                $this->clearRememberMeCookie();
            }
        }
        return null;
    }

    /**
     * Logs out the current user.
     */
    public function logout(): void
    {
        $userId = $this->session->get("user_id");

        if ($userId) {
            // Remove only the current session token; keep remember tokens intact.
            $this->updateUser((int)$userId, ['session_token' => null]);
        }

        $this->session->remove("user_id");
        $this->session->remove("session_token");
        $this->clearRememberMeCookie();
        session_regenerate_id(true);
    }

    /**
     * Handles failed login attempts.
     */
    protected function handleFailedLogin(string $email, string $reason): array
    {
        $userExists = $this->getUserByEmail($email);
        if (!$userExists) {
            return $this->response(false, "User does not exist.", "info");
        }

        $record = $this->getFailedLogin($email);
        if (!$record) {
            $this->createFailedLogin($email);
        } else {
            $this->updateFailedLogin($record, $email);
        }

        return $this->response(false, $reason, "warning");
    }

    /**
     * Resets failed login attempts for the given email.
     */
    protected function resetFailedLogins(string $email): void
    {
        $this->deleteFailedLogin($email);
        $this->updateUserByEmail($email, ['is_blocked' => 0]);
    }

    /**
     * Checks if the given email is currently blocked.
     */
    protected function isBlocked(string $email): bool
    {
        $record = $this->getFailedLogin($email);
        if (!$record || empty($record['blocked_until'])) {
            return false;
        }
        return (new DateTime($record['blocked_until'])) > new DateTime();
    }

    /**
     * Returns a structured response.
     */
    private function response(bool $success, string $message, string $type, string $redirect = null): array
    {
        return [
            'success'  => $success,
            'message'  => $message,
            'type'     => $type,
            'redirect' => $redirect
        ];
    }

    /**
     * Sets a remember me token for the user.
     * Supports multiple devices by appending tokens to a JSON array.
     */
    protected function setRememberMeToken(int $userId): void
    {
        // Generate a token and its hash.
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Retrieve current remember tokens.
        $user = $this->getUserById($userId);
        $rememberTokens = [];
        if (!empty($user['remember_tokens'])) {
            $rememberTokens = json_decode($user['remember_tokens'], true) ?: [];
        }
        // Append the new hashed token.
        $rememberTokens[] = $hashedToken;

        // Update the user record.
        $this->updateUser($userId, ['remember_tokens' => json_encode($rememberTokens)]);

        // Set the remember me cookie.
        $isProduction = $this->isProductionEnvironment();
        $cookieParams = [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'domain'   => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure'   => $isProduction,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // Adjust for localhost development.
        if (!$isProduction) {
            $cookieParams['secure'] = false;
            if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost') {
                $cookieParams['domain'] = false;
            }
        }

        setcookie(REMEMBER_ME_NAME, $token, $cookieParams);
    }

    /**
     * Determines if the environment is production.
     */
    protected function isProductionEnvironment(): bool
    {
        return (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'production');
    }

    /**
     * Clears the remember me cookie.
     */
    protected function clearRememberMeCookie(): void
    {
        $params = session_get_cookie_params();
        setcookie(REMEMBER_ME_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => $params['secure'] ?? false,
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    // ===== Database Interaction Methods =====

    protected function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    protected function getUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Searches for a user whose "remember_tokens" JSON array contains the hashed token.
     */
    protected function getUserByRememberToken(string $hashedToken): ?array
    {
        $stmt = $this->pdo->query("SELECT * FROM users");
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($user['remember_tokens'])) {
                $tokens = json_decode($user['remember_tokens'], true);
                if (is_array($tokens) && in_array($hashedToken, $tokens, true)) {
                    return $user;
                }
            }
        }
        return null;
    }

    protected function updateUser(int $userId, array $data): void
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        $params['user_id'] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    protected function updateUserByEmail(string $email, array $data): void
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        $params['email'] = $email;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    // ===== Failed Login Methods =====

    protected function getFailedLogin(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM failed_logins WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    protected function createFailedLogin(string $email): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO failed_logins (email, attempts, blocked_until) VALUES (:email, 1, NULL)");
        $stmt->execute(['email' => $email]);
    }

    protected function updateFailedLogin(array $record, string $email): void
    {
        $attempts = $record['attempts'] + 1;
        $blockDuration = min(max(($attempts - 4) * 5, 5), 60);
        $blockedUntil = $attempts > 4 ? (new DateTime())->modify("+$blockDuration minutes")->format('Y-m-d H:i:s') : null;

        $stmt = $this->pdo->prepare("UPDATE failed_logins SET attempts = :attempts, blocked_until = :blocked_until WHERE email = :email");
        $stmt->execute([
            'attempts'     => $attempts,
            'blocked_until'=> $blockedUntil,
            'email'        => $email
        ]);

        if ($attempts > 4) {
            $this->updateUserByEmail($email, ['is_blocked' => 1]);
        }
    }

    protected function deleteFailedLogin(string $email): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM failed_logins WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }
}
