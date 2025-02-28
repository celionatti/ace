<?php

use Ace\ace\Ace;

// Bootstrap the application
$app = Ace::getInstance();

// Retrieve the router instance from the application
$router = $app->getRouter();

// Define a simple GET route using a closure
$router->get('/', function ($request, $response) {
    $response->html("Welcome to Ace Framework!");
});

// Define a route that uses a controller action (using the "Controller@method" syntax)
$router->get('/user/{id}', ['UserController', 'show']);

// Group routes with a common prefix and middleware
$router->group([
    'prefix'     => '/admin',
    'middleware' => [\App\Middleware\AdminMiddleware::class] // This middleware should have a 'handle' method
], function ($router) {
    $router->get('/dashboard', ['AdminController', 'dashboard']);
    $router->post('/users', ['AdminController', 'createUser']);
});

// You can also define routes for multiple HTTP methods
$router->match(['PUT', 'PATCH'], '/user/{id}', ['UserController', 'update']);

// Finally, run the application to process the incoming request
$app->run();
