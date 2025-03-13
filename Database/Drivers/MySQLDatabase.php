<?php

declare(strict_types=1);

namespace Ace\Database\Drivers;

use Ace\Database\AbstractDatabase;

class MySQLDatabase extends AbstractDatabase
{
    /**
     * @var array Default configuration values
     */
    protected array $defaultConfig = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];

    /**
     * Connect to the database
     *
     * @param array $config Connection configuration parameters
     * @return bool True on success, false on failure
     */
    public function connect(array $config): bool
    {
        try {
            $config = $this->mergeConfig($config);

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $this->connection = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            return true;
        } catch (\PDOException $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Execute a query and return the result
     *
     * @param string $query The SQL query to execute
     * @param array $params Parameters to bind to the query
     * @return mixed The query result or false on failure
     */
    public function query(string $query, array $params = []): mixed
    {
        try {
            if (!$this->connection) {
                throw new \Exception("Database connection not established");
            }

            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new \Exception("Failed to prepare statement: " . implode(' ', $this->connection->errorInfo()));
            }

            if (!$stmt->execute($params)) {
                throw new \Exception("Failed to execute query: " . implode(' ', $stmt->errorInfo()));
            }

            // Determine if this is a SELECT query by checking if it starts with SELECT
            // (This is a simple check, might not work for complex queries with comments)
            if (stripos(trim($query), 'SELECT') === 0) {
                return $stmt->fetchAll();
            }

            // For non-SELECT queries, return the number of affected rows
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Begin a transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction(): bool
    {
        try {
            if (!$this->connection) {
                throw new \Exception("Database connection not established");
            }

            return $this->connection->beginTransaction();
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Commit a transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit(): bool
    {
        try {
            if (!$this->connection) {
                throw new \Exception("Database connection not established");
            }

            return $this->connection->commit();
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Rollback a transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback(): bool
    {
        try {
            if (!$this->connection) {
                throw new \Exception("Database connection not established");
            }

            return $this->connection->rollBack();
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Get the last inserted ID
     *
     * @return int|string The last inserted ID
     */
    public function lastInsertId(): int|string
    {
        try {
            if (!$this->connection) {
                throw new \Exception("Database connection not established");
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            return 0;
        }
    }

    /**
     * Close the database connection
     *
     * @return bool True on success, false on failure
     */
    public function close(): bool
    {
        $this->connection = null;
        return true;
    }
}