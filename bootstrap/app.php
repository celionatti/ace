<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ======== Ace Bootstrap App ============
 * ***************************************
 * =======================================
 */

use Dotenv\Dotenv;
use Ace\ace\Ace;
use Ace\Router\Router;

// Define the application path
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

try {
    $dotenv = Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
    $dotenv->required(['APP_KEY', 'DB_DATABASE', 'DB_USERNAME', 'DB_CONNECTION']);
} catch(Exception $e) {
    die("Missing required environment variables");
}

// Set default timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

$app = Ace::getInstance();

return $app;