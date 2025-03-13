<?php

declare(strict_types=1);

namespace Ace\Database;

use Ace\Database\Interface\DatabaseInterface;
use Ace\ErrorHandler\ErrorHandler;
use Ace\Logger\Logger;

abstract class AbstractDatabase implements DatabaseInterface
{
    /**
     * @var mixed The database connection
     */
    protected $connection;

    /**
     * @var string The last error message
     */
    protected string $lastError = '';

    /**
     * @var array Default configuration values
     */
    protected array $defaultConfig = [];

    /**
     * Constructor
     *
     * @param array $config Connection configuration
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->connect($config);
        }
    }

    /**
     * Get the last error message
     *
     * @return string The last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Set the last error message
     *
     * @param string $error The error message
     * @param string $query
     * @param array $params
     * @param string $errorCode
     * @return void
     */
    protected function setLastError(string $error, string $query = '', array $params = [], string $errorCode = ''): void
    {
        $this->lastError = $error;

        // Get a user-friendly error message if available
        $userFriendlyError = ErrorHandler::getUserFriendlyError(
            $this->getDriverName(),
            $errorCode
        );

        if ($userFriendlyError !== null) {
            $this->lastError = $userFriendlyError . ' (' . $error . ')';
        }

        // Log the error
        Logger::error($this->lastError, [
            'query' => $query,
            'params' => $params,
            'error_code' => $errorCode
        ]);
    }

    /**
     * Merge configuration with defaults
     *
     * @param array $config User provided configuration
     * @return array Complete configuration with defaults
     */
    protected function mergeConfig(array $config): array
    {
        return array_merge($this->defaultConfig, $config);
    }

    protected function getDriverName(): string
    {
        // Override in subclasses to return the appropriate driver name
        return 'unknown';
    }

    /**
     * Destructor - close the connection when object is destroyed
     */
    public function __destruct()
    {
        $this->close();
    }
}