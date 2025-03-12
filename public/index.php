<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * =========== Ace Index =================
 * ***************************************
 * =======================================
 */

$app = require __DIR__ . '/../bootstrap/app.php';

try {
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => 'Application failed to initialize',
        'message' => $e->getMessage(),
        'trace'   => ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $e->getTrace() : []
    ]);
    exit;
}
