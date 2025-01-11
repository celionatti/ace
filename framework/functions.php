<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * =========== Ace Function ==============
 * ***************************************
 * =======================================
 */

use Celionatti\Ace\HookManager\Hook;

function assets_url()
{
    if (explode("/", $_SERVER['REQUEST_URI'])[1] === "plugins-assets") {
    $parts = explode("/", $_SERVER['REQUEST_URI']);

    // Remove the first two parts ('/' and 'plugins-assets')
    array_shift($parts);
    array_shift($parts);

    // Rebuild the file path
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);

    // Check if the file exists
    if (file_exists($filePath)) {
        // Set MIME type explicitly
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = match ($extension) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            default => mime_content_type($filePath)
        };

        header('Content-Type: ' . $mimeType);
        header('Cache-Control: max-age=86400'); // Cache for 1 day
        readfile($filePath);
    } else {
        http_response_code(404);
        echo "<center><h3>404 - File Not Found</h3></center>";
    }
    exit;
}
}

function add_action(string $hook, callable $callback, int $priority = 10)
{
    Hook::addAction($hook, $callback, $priority);
}

function do_action(string $hook, ...$args)
{
    Hook::doAction($hook, $args);
}

function add_filter(string $hook, callable $callback, int $priority = 10)
{
    Hook::addFilter($hook, $callback, $priority);
}

function do_filter(string $hook, $value, ...$args)
{
    Hook::applyFilter($hook, $value, $args);
}
