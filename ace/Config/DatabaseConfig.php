<?php

declare(strict_types=1);

namespace Ace\ace\Config;

class DatabaseConfig
{
    /**
     * @var array Database configuration
     */
    private static array $config = [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'type' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => '',
                'username' => '',
                'password' => '',
                'charset' => 'utf8mb4',
                'options' => []
            ],
            'pgsql' => [
                'type' => 'pgsql',
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'my_database',
                'username' => 'db_user',
                'password' => 'db_password',
                'options' => []
            ],
            'sqlite' => [
                'type' => 'sqlite',
                'database' => 'storage/database/database.sqlite',
                'options' => []
            ]
        ]
    ];

    /**
     * Get the database configuration
     *
     * @param string|null $connection Connection name or null for default
     * @return array The database configuration
     * @throws \Exception If the connection is not defined
     */
    public static function getConfig(?string $connection = null): array
    {
        $connection = $connection ?? self::$config['default'];

        if (!isset(self::$config['connections'][$connection])) {
            throw new \Exception("Database connection '$connection' is not defined");
        }

        return self::$config['connections'][$connection];
    }

    /**
     * Set the database configuration
     *
     * @param array $config The database configuration
     * @return void
     */
    public static function setConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Load configuration from a file
     *
     * @param string $file The path to the configuration file
     * @return bool True on success, false on failure
     */
    public static function loadFromFile(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $config = require $file;

        if (!is_array($config)) {
            return false;
        }

        self::setConfig($config);
        return true;
    }

    public function toArray(): array
    {
        return $this->config;
    }
}