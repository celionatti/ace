<?php

declare(strict_types=1);

use Ace\ace\Ace;
use Ace\app\controllers\SiteController;
use Ace\app\controllers\HomeController;
use PhpStrike\app\controllers\AdminController;

/** @var \Ace\ace\Router\Router $router */

/**
 * ========================================
 * Web Router =============================
 * ========================================
 */

$router->get('/', [HomeController::class, 'index']);
$router->get('/test', [SiteController::class, 'index']);

$router->group(['prefix'     => '/admin'], function ($router) {
    $router->get('/dashboard', [SiteController::class, 'dashboard']);
});
