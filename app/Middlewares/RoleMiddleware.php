<?php

namespace App\Middlewares;

use Ace\Application;

/**
 * Middleware to restrict access to users with a specific role.
 *
 * Usage in a controller constructor:
 *   $this->registerMiddleware(new RoleMiddleware('admin'));
 *   $this->registerMiddleware(new RoleMiddleware('admin', 'editor'));
 */
class RoleMiddleware extends BaseMiddleware
{
    /** @var string[] */
    private array $roles;

    /**
     * @param string ...$roles  One or more role slugs. User must have at least one.
     */
    public function __construct(string ...$roles)
    {
        parent::__construct();
        $this->roles = $roles;
    }

    protected function run(): void
    {
        if (Application::isGuest()) {
            Application::$app->session->setFlash('error', 'Please login to access this page.');
            Application::$app->response->redirect('/login');
            exit;
        }

        if (!Application::$app->user->hasAnyRole(...$this->roles)) {
            throw new \Exception("Forbidden. Required role: " . implode(' or ', $this->roles), 403);
        }
    }
}

