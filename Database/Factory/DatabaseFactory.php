<?php

declare(strict_types=1);

namespace Ace\ace\Database\Factory;

use Ace\ace\Database\Interface\DatabaseInterface;
use Ace\ace\Database\Drivers\MySQLDatabase;
use Ace\ace\Database\Drivers\PostgreSQLDatabase;
use Ace\ace\Database\Drivers\SQLiteDatabase;
use Ace\ace\Logger\Logger;

class DatabaseFactory
{
    /**
     * Create a database connection based on the type
     *
     * @param string $type Database type (mysql, mariadb, pgsql, sqlite)
     * @param array $config Connection configuration
     * @return DatabaseInterface|null The database object or null on error
     */
    public static function create(string $type, array $config = []): ?DatabaseInterface
    {
        try {
            $db = match (strtolower($type)) {
                'mysql', 'mariadb' => new MySQLDatabase(),
                'pgsql', 'postgres', 'postgresql' => new PostgreSQLDatabase(),
                'sqlite' => new SQLiteDatabase(),
                default => throw new \InvalidArgumentException("Unsupported database type: $type")
            };

            if (!empty($config) && !$db->connect($config)) {
                throw new \Exception("Failed to connect to the database: " . $db->getLastError());
            }

            return $db;
        } catch (\Exception $e) {
            // Log the error if a logger is configured
            Logger::error('Database factory error: ' . $e->getMessage());

            return null;
        }
    }
}