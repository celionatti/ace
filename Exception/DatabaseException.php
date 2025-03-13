<?php

declare(strict_types=1);

/**
 * ==============================================
 * ================         =====================
 * DatabaseException Class.
 * ================         =====================
 * ==============================================
 */

namespace Ace\ace\Exception;

class DatabaseException extends \Exception
{
    /**
     * @var string The SQL query that caused the error
     */
    private string $query;

    /**
     * @var array The parameters used in the query
     */
    private array $params;

    /**
     * @var string The database error code
     */
    private string $errorCode;

    /**
     * Constructor
     *
     * @param string $message The error message
     * @param string $query The SQL query that caused the error
     * @param array $params The parameters used in the query
     * @param string $errorCode The database error code
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message, string $query = '', array $params = [], string $errorCode = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
        $this->params = $params;
        $this->errorCode = $errorCode;
    }

    /**
     * Get the SQL query that caused the error
     *
     * @return string The SQL query
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the parameters used in the query
     *
     * @return array The parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the database error code
     *
     * @return string The error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}