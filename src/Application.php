<?php

namespace Ace;

use Exception;

class Application
{
    public static string $ROOT_DIR;
    public static Application $app;
    
    public string $layout = 'main';
    public Request $request;
    public Response $response;
    public Router $router;
    public Session $session;
    public View $view;
    public ?Database $db = null;
    public ?Model $user = null;
    public array $config = [];

    public function __construct(string $rootDir, array $config = [])
    {
        self::$ROOT_DIR = $rootDir;
        self::$app = $this;
        $this->config = $config;

        // Load env variables
        $this->loadEnv($rootDir . '/.env');

        // Core component instances
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->view = new View(self::$ROOT_DIR . '/views', self::$ROOT_DIR . '/storage/views');
        $this->router = new Router($this->request, $this->response);

        // Database connection if configured
        $dbConfig = $this->config['db'] ?? [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? '',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
        ];

        if (!empty($dbConfig['database'])) {
            $this->db = new Database($dbConfig);
        }

        // Error handling registration
        $this->registerErrorHandlers();

        // Resolve logged in user from session or remember-me cookie if exists
        $userClass = $this->config['userClass'] ?? null;
        if ($userClass) {
            $primaryKey = $this->session->get('user');
            if ($primaryKey) {
                $userModel = new $userClass();
                $this->user = $userModel::findOne([$userModel->primaryKey() => $primaryKey]);
            }

            if (!$this->user) {
                $this->loginFromRememberMeCookie($userClass);
            }
        }
    }

    /**
     * Parse and load .env file into $_ENV
     */
    private function loadEnv(string $envPath): void
    {
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Split on the first '=' character
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Strip surrounding quotes if any
                if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
                    $val = substr($val, 1, -1);
                } elseif (str_starts_with($val, "'") && str_ends_with($val, "'")) {
                    $val = substr($val, 1, -1);
                }

                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }

    /**
     * Global Exception and Error Handlers
     */
    private function registerErrorHandlers(): void
    {
        // Exception handler
        set_exception_handler(function ($exception) {
            $this->handleException($exception);
        });

        // Error handler (convert PHP errors to ErrorException)
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        $code = (int)$exception->getCode();
        
        // Log the exception
        Logger::exception($exception);
        
        // Normalize HTTP status codes
        if ($code < 400 || $code > 599) {
            $code = 500;
        }

        $this->response->setStatusCode($code);

        $isDev = ($_ENV['APP_ENV'] ?? 'development') === 'development';

        $errorData = [
            'exception' => $exception,
            'message' => $exception->getMessage(),
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        // If it's a 404 or in production, hide trace and system info unless in dev mode
        if ($code === 404) {
            echo $this->router->renderView('errors/404', $errorData);
        } else {
            if ($isDev) {
                // Dev environment: Show full error page with details
                echo $this->router->renderView('errors/500', $errorData);
            } else {
                // Production environment: Show clean generic error screen
                echo $this->router->renderView('errors/500', [
                    'message' => 'An internal server error occurred. Please try again later.',
                    'code' => 500
                ]);
            }
        }
        exit;
    }

    /**
     * Run the Application
     */
    public function run(): void
    {
        try {
            // Run global middlewares
            (new \App\Middlewares\SecurityHeadersMiddleware())->execute('');
            (new \App\Middlewares\RateLimitMiddleware())->execute('');
            (new \App\Middlewares\CsrfMiddleware())->execute('');

            echo $this->router->resolve();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Login helper
     */
    public function login(Model $user, bool $remember = false): bool
    {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $value = $user->{$primaryKey};
        $this->session->set('user', $value);

        if ($remember) {
            $this->createRememberMeToken($value);
        }

        return true;
    }

    /**
     * Logout helper
     */
    public function logout(): void
    {
        if ($this->user) {
            $primaryKey = $this->user->primaryKey();
            $userId = $this->user->{$primaryKey};
            $this->clearRememberMeCookie($userId);
        }
        $this->user = null;
        $this->session->remove('user');
    }

    /**
     * Check if current user is guest
     */
    public static function isGuest(): bool
    {
        return !self::$app->user;
    }

    /**
     * Create a new remember me token and set the cookie
     */
    private function createRememberMeToken(int|string $userId): void
    {
        if (!$this->db) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        $expiresAt = date('Y-m-d H:i:s', $expires);

        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at)
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        // Secure cookie flags
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(
            'remember_me',
            "$userId:$token",
            [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Attempt login using the remember_me cookie
     */
    private function loginFromRememberMeCookie(string $userClass): void
    {
        $cookie = $_COOKIE['remember_me'] ?? null;
        if (!$cookie || !$this->db) {
            return;
        }

        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            $this->clearRememberMeCookie();
            return;
        }

        [$userId, $token] = $parts;
        $tokenHash = hash('sha256', $token);

        // Find token in db
        $stmt = $this->db->prepare("
            SELECT * FROM remember_tokens
            WHERE user_id = :user_id AND token_hash = :token_hash AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'token_hash' => $tokenHash]);
        $record = $stmt->fetch();

        if ($record) {
            $userModel = new $userClass();
            $user = $userModel::findOne([$userModel->primaryKey() => $userId]);
            if ($user) {
                $this->user = $user;
                $this->session->set('user', $userId);
                
                // Rotate token
                $this->rotateRememberMeToken($record['id'], $userId);
                return;
            }
        }

        // Token invalid or expired, clear it
        $this->clearRememberMeCookie($userId, $tokenHash);
    }

    /**
     * Rotate remember token on successful auto-login
     */
    private function rotateRememberMeToken(int $tokenId, int|string $userId): void
    {
        if (!$this->db) {
            return;
        }

        $newToken = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newToken);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        $expiresAt = date('Y-m-d H:i:s', $expires);

        // Update database token
        $stmt = $this->db->prepare("
            UPDATE remember_tokens
            SET token_hash = :token_hash, expires_at = :expires_at
            WHERE id = :id
        ");
        $stmt->execute([
            'token_hash' => $newTokenHash,
            'expires_at' => $expiresAt,
            'id' => $tokenId
        ]);

        // Set cookie
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(
            'remember_me',
            "$userId:$newToken",
            [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Clear remember cookie and remove token from db
     */
    private function clearRememberMeCookie(?int $userId = null, ?string $tokenHash = null): void
    {
        // Remove from db if specified
        if ($this->db) {
            if ($userId && $tokenHash) {
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id AND token_hash = :token_hash");
                $stmt->execute(['user_id' => $userId, 'token_hash' => $tokenHash]);
            } elseif ($userId) {
                // Delete all tokens for this user on logout
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $userId]);
            }
        }

        // Expire cookie
        setcookie('remember_me', '', time() - 3600, '/');
        if (isset($_COOKIE['remember_me'])) {
            unset($_COOKIE['remember_me']);
        }
    }
}

