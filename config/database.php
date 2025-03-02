<?php

declare(strict_types=1);

return
[
    'default' => env("DB_CONNECTION"),
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => env("DB_DATABASE"),
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'charset' => 'utf8mb4',
            'options' => []
        ],
        'pgsql' => [
            'type' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => env("DB_DATABASE"),
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'options' => []
        ],
        'sqlite' => [
            'type' => 'sqlite',
            'database' => 'storage/database/database.sqlite',
            'options' => []
        ]
    ]
];