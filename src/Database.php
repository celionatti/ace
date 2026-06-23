<?php

namespace Ace;

use PDO;
use Exception;

class Database
{
    public PDO $pdo;

    public function __construct(array $config)
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $dbName = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Exception $e) {
            // Check for code 1049 (Unknown database) to auto-create
            if (str_contains($e->getMessage(), 'Unknown database') || $e->getCode() == 1049) {
                try {
                    $tempDsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                    $tempPdo = new PDO($tempDsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Re-attempt connecting with dbName
                    $this->pdo = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (Exception $subEx) {
                    throw new Exception("Database Connection Failed (could not auto-create database): " . $subEx->getMessage(), 500);
                }
            } else {
                throw new Exception("Database Connection Failed: " . $e->getMessage(), 500);
            }
        }
    }

    /**
     * Helper to prepare PDO statement
     */
    public function prepare(string $sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * Inspect MySQL table to retrieve column names dynamically
     */
    public function getTableColumns(string $table): array
    {
        try {
            $statement = $this->pdo->prepare("DESCRIBE `$table`");
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Table might not exist yet
            return [];
        }
    }

    /**
     * Check if a table exists
     */
    public function tableExists(string $table): bool
    {
        try {
            $result = $this->pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Run migration scripts to initialize standard tables
     */
    public function runMigrations(): void
    {
        $manager = new MigrationManager($this->pdo, Application::$ROOT_DIR . '/migrations');
        $manager->applyMigrations();
    }
}

