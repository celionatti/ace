<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ace\Application;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\PaymentController;

$rootDir = dirname(__DIR__);

// Load configurations
$appConfig = require_once $rootDir . '/config/app.php';
$dbConfig = require_once $rootDir . '/config/database.php';

$config = array_merge($appConfig, ['db' => $dbConfig]);

// Instantiate Application
$app = new Application($rootDir, $config);

// Run database migrations on startup to create tables automatically
if ($app->db) {
    $app->db->runMigrations();
}

// Load application routes
$router = $app->router;
require_once $rootDir . '/routes/web.php';

// Run the Application
$app->run();

