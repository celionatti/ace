<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Mini MVC Framework',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost/mvc',
    
    // Auth configuration
    'userClass' => \App\Models\User::class,
];

