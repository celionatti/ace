<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * =========== Ace Index =================
 * ***************************************
 * =======================================
 */

use Dotenv\Dotenv;
use Celionatti\Ace\Ace;
use Celionatti\Ace\Router\Router;



require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

(new Ace())->run();

assets_url();

// Handle the Request
Router::dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
