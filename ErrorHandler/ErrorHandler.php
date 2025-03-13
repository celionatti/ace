<?php

declare(strict_types=1);

namespace Ace\ErrorHandler;

use Ace\Logger\Logger;

class ErrorHandler
{
    /**
     * @var bool Whether to throw exceptions on errors
     */
    private static bool $throwExceptions = true;

    /**
     * Set whether to throw exceptions on errors
     *
     * @param bool $throw Whether to throw exceptions
     * @return void
     */
    public static function setThrowExceptions(bool $throw): void
    {
        self::$throwExceptions = $throw;
    }

    /**
     * Handle a database error
     *
     * @param string $message The error message
     * @param string $query The SQL query that caused the error
     * @param array $params The parameters used in the query
     * @param string $errorCode The database error code
     * @throws DatabaseException If exceptions are enabled
     * @return void
     */
    public static function handleError(
        string $message,
        string $query = '',
        array $params = [],
        string $errorCode = ''
    ): void {
        // Log the error
        Logger::error($message, [
            'query' => $query,
            'params' => $params,
            'error_code' => $errorCode
        ]);

        // Throw an exception if enabled
        if (self::$throwExceptions) {
            throw new DatabaseException($message, $query, $params, $errorCode);
        }
    }

    /**
     * Format a database error message
     *
     * @param string $driver The database driver
     * @param string $errorCode The error code
     * @param string $errorMessage The error message
     * @return string The formatted error message
     */
    public static function formatErrorMessage(
        string $driver,
        string $errorCode,
        string $errorMessage
    ): string {
        return "[$driver] [$errorCode] $errorMessage";
    }

    /**
     * Get a user-friendly error message for common database errors
     *
     * @param string $driver The database driver
     * @param string $errorCode The error code
     * @return string|null The user-friendly error message or null if not found
     */
    public static function getUserFriendlyError(string $driver, string $errorCode): ?string
    {
        // Common MySQL/MariaDB error codes
        $mysqlErrors = [
            '1045' => 'Authentication failed. Please check your database credentials.',
            '1049' => 'Unknown database. Please check if the database exists.',
            '1062' => 'Duplicate entry. A record with this unique key already exists.',
            '1064' => 'SQL syntax error. Please check your query syntax.',
            '1146' => 'Table does not exist. Please check your table name.',
            '1452' => 'Foreign key constraint failed. The referenced record does not exist.',
            '2002' => 'Connection failed. Please check your database host and port.',
            '2003' => 'Connection refused. Please check if the database server is running.',
            '2005' => 'Unknown host. Please check your database host name.',
            '2006' => 'Server has gone away. The connection was lost.',
            '2013' => 'Lost connection during query. Please try again.',
        ];

        // Common PostgreSQL error codes
        $pgsqlErrors = [
            '3D000' => 'Unknown database. Please check if the database exists.',
            '28P01' => 'Authentication failed. Please check your database credentials.',
            '42P01' => 'Table does not exist. Please check your table name.',
            '23505' => 'Duplicate entry. A record with this unique key already exists.',
            '42601' => 'SQL syntax error. Please check your query syntax.',
            '23503' => 'Foreign key constraint failed. The referenced record does not exist.',
            '08006' => 'Connection failed. Please check your database connection parameters.',
            '08001' => 'Connection refused. Please check if the database server is running.',
        ];

        // Common SQLite error codes
        $sqliteErrors = [
            '14' => 'Unable to open database file. Please check file permissions.',
            '1' => 'SQL syntax error. Please check your query syntax.',
            '19' => 'Constraint failed. A unique constraint was violated.',
            '8' => 'Read-only database. Cannot write to the database.',
            '5' => 'Database is locked. Another process is using the database.',
            '7' => 'Database disk image is malformed. The database file may be corrupted.',
        ];

        // Select the appropriate error list based on the driver
        $errors = match (strtolower($driver)) {
            'mysql', 'mariadb' => $mysqlErrors,
            'pgsql', 'postgres', 'postgresql' => $pgsqlErrors,
            'sqlite' => $sqliteErrors,
            default => []
        };

        // Return the user-friendly error message if found
        return $errors[$errorCode] ?? null;
    }
}