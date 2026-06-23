<?php

/** @var \Ace\Router $router */

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\PaymentController;
use App\Controllers\UploadController;

// ==================================================
// APPLICATION WEB ROUTES
// ==================================================

// Home Route
$router->get('/', [HomeController::class, 'index']);

// Auth Routes
$router->get('/login', [AuthController::class, 'loginView']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerView']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordView']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordView']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/profile', [AuthController::class, 'profile']);

// Payment (Paystack, Stripe, Flutterwave) Routes
$router->get('/pay', [PaymentController::class, 'payView']);
$router->post('/pay/initialize', [PaymentController::class, 'initialize']);
$router->get('/pay/callback', [PaymentController::class, 'callback']);
$router->post('/pay/webhook', [PaymentController::class, 'webhook']);

// Stripe Payment Routes
$router->post('/pay/stripe/initialize', [PaymentController::class, 'stripeInitialize']);
$router->get('/pay/stripe/callback', [PaymentController::class, 'stripeCallback']);

// Flutterwave Payment Routes
$router->post('/pay/flutterwave/initialize', [PaymentController::class, 'flutterwaveInitialize']);
$router->get('/pay/flutterwave/callback', [PaymentController::class, 'flutterwaveCallback']);

// File & Image Upload Routes
$router->get('/upload', [UploadController::class, 'uploadView']);
$router->post('/upload', [UploadController::class, 'upload']);

// Blog Routes
$router->get('/blog', [\App\Controllers\BlogController::class, 'index']);
$router->get('/blog/{id}', [\App\Controllers\BlogController::class, 'show']);
$router->post('/blog/{id}', [\App\Controllers\BlogController::class, 'show']);

// Admin Routes
$router->get('/admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/create', [\App\Controllers\AdminController::class, 'create']);
$router->post('/admin/create', [\App\Controllers\AdminController::class, 'create']);
$router->get('/admin/edit/{id}', [\App\Controllers\AdminController::class, 'edit']);
$router->post('/admin/edit/{id}', [\App\Controllers\AdminController::class, 'edit']);
$router->post('/admin/delete/{id}', [\App\Controllers\AdminController::class, 'delete']);
$router->get('/admin/users', [\App\Controllers\AdminController::class, 'users']);
$router->post('/admin/users/{id}/role', [\App\Controllers\AdminController::class, 'updateUserRole']);
$router->get('/admin/roles', [\App\Controllers\AdminController::class, 'roles']);
$router->post('/admin/roles/{id}/permissions', [\App\Controllers\AdminController::class, 'updateRolePermissions']);

