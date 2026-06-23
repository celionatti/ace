<?php

use Ace\Application;

if (!function_exists('app')) {
    /**
     * Get the application instance.
     */
    function app(): Application
    {
        return Application::$app;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variables.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration variables using dot notation.
     */
    function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $config = Application::$app->config;
        foreach ($keys as $k) {
            if (is_array($config) && array_key_exists($k, $config)) {
                $config = $config[$k];
            } else {
                return $default;
            }
        }
        return $config;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template.
     */
    function view(string $view, array $params = []): string
    {
        return Application::$app->view->render($view, $params);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL path.
     */
    function redirect(string $url): void
    {
        Application::$app->response->redirect($url);
    }
}

if (!function_exists('route')) {
    /**
     * Generate relative URL path for route linking.
     */
    function route(string $path): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token from the session.
     */
    function csrf_token(): string
    {
        return Application::$app->session->getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token hidden input field.
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('user')) {
    /**
     * Get the currently authenticated user, or null if guest.
     */
    function user(): ?\Ace\Model
    {
        return Application::$app->user;
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if the current user has a specific role.
     *
     * Usage in views: @if(hasRole('admin')) ... @endif
     */
    function hasRole(string $slug): bool
    {
        $user = Application::$app->user;
        return $user && $user->hasRole($slug);
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Check if the current user has a specific permission.
     *
     * Usage in views: @if(hasPermission('edit-posts')) ... @endif
     */
    function hasPermission(string $slug): bool
    {
        $user = Application::$app->user;
        return $user && $user->hasPermission($slug);
    }
}

if (!function_exists('xss_clean')) {
    /**
     * Clean dynamic variables against XSS attacks.
     */
    function xss_clean(mixed $value): mixed
    {
        return \Ace\XssSanitizer::clean($value);
    }
}


