<?php

declare(strict_types=1);

namespace Ace\Database\Interface;

interface DatabaseInterface
{
    /**
     * Connect to the database
     *
     * @param array $config Connection configuration parameters
     * @return bool True on success, false on failure
     */
    public function connect(array $config): bool;

    /**
     * Execute a query and return the result
     *
     * @param string $query The SQL query to execute
     * @param array $params Parameters to bind to the query
     * @return mixed The query result
     */
    public function query(string $query, array $params = []): mixed;

    /**
     * Begin a transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction(): bool;

    /**
     * Commit a transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit(): bool;

    /**
     * Rollback a transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback(): bool;

    /**
     * Get the last inserted ID
     *
     * @return int|string The last inserted ID
     */
    public function lastInsertId(): int|string;

    /**
     * Get the last error message
     *
     * @return string The last error message
     */
    public function getLastError(): string;

    /**
     * Close the database connection
     *
     * @return bool True on success, false on failure
     */
    public function close(): bool;
}