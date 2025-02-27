<?php

declare(strict_types=1);

use Ace\ace\Ace;
use PhpStrike\app\controllers\SiteController;
use PhpStrike\app\controllers\AdminController;

/** @var \Ace\ace\Router\Router $router */

/**
 * ========================================
 * Web Router =============================
 * ========================================
 */

// var_dump($router);
// die;

$router->addRoute('GET', '/', function ($request, $response) {
    $response->html('<h1>Home Page</h1>');
});

$router->addRoute('GET', '/users/{id}/profile', function ($request, $response) {
    $response->html('<h1>Profile Page</h1>');
});
