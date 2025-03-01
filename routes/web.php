<?php

declare(strict_types=1);

use Ace\ace\Ace;
use Ace\app\controllers\SiteController;
use PhpStrike\app\controllers\AdminController;

/** @var \Ace\ace\Router\Router $router */

/**
 * ========================================
 * Web Router =============================
 * ========================================
 */

$router->get('/', [SiteController::class, 'index']);

$router->group(['prefix'     => '/admin'], function ($router) {
    $router->get('/dashboard', [SiteController::class, 'dashboard']);
});

$router->addRoute('GET', '/users/{id}/profile', function ($request, $response, $id) {
    $response->html("<h1>Profile Page: {$id}</h1>");
});
