<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $request = Request::createFromGlobals();
    $router = new Router();

    // Add middleware
    $router->addMiddleware(AuthMiddleware::class);

    $response = $router->dispatch($request);
    $response->send();
} catch (\Exception $e) {
    // Log the exception and return a 500 response
    error_log($e->getMessage());
    $response = new Response('Internal Server Error', 500);
    $response->send();
}
