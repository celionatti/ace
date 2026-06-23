<?php

namespace App\Middlewares;

use Ace\Application;

/**
 * Middleware to restrict access to users with a specific permission.
 *
 * Usage in a controller constructor:
 *   $this->registerMiddleware(new PermissionMiddleware('edit-posts'));
 *   $this->registerMiddleware(new PermissionMiddleware('edit-posts', 'delete-posts'));
 */
class PermissionMiddleware extends BaseMiddleware
{
    /** @var string[] */
    private array $permissions;

    /**
     * @param string ...$permissions  One or more permission slugs. User must have at least one.
     */
    public function __construct(string ...$permissions)
    {
        parent::__construct();
        $this->permissions = $permissions;
    }

    protected function run(): void
    {
        if (Application::isGuest()) {
            Application::$app->session->setFlash('error', 'Please login to access this page.');
            Application::$app->response->redirect('/login');
            exit;
        }

        if (!Application::$app->user->hasAnyPermission(...$this->permissions)) {
            throw new \Exception("Forbidden. Required permission: " . implode(' or ', $this->permissions), 403);
        }
    }
}

