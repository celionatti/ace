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

$router->get('/', function($request, $response) {
    $response->html('<h1>Welcome to Ace Framework</h1>');
});

$router->get('/home/{id}', [SiteController::class, 'index']);

$router->group(['prefix'     => '/admin'], function ($router) {
    $router->get('/dashboard', [SiteController::class, 'dashboard']);
});

$router->addRoute('GET', '/users/{id}/profile', function ($request, $response, $id) {
    $response->html("<h1>Profile Page: {$id}</h1>");
});
