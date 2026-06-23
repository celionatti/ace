<?php

namespace Ace;

class Logger
{
    private static ?string $logFile = null;

    /**
     * Get the log file path.
     */
    private static function getLogFile(): string
    {
        if (self::$logFile === null) {
            $logDir = Application::$ROOT_DIR . '/storage/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/app.log';
        }
        return self::$logFile;
    }

    /**
     * Write a message to the log.
     */
    public static function log(string $level, string $message): void
    {
        $date = date('Y-m-d H:i:s');
        $formattedMessage = sprintf("[%s] [%s] %s%s", $date, strtoupper($level), $message, PHP_EOL);
        file_put_contents(self::getLogFile(), $formattedMessage, FILE_APPEND);
    }

    /**
     * Log an informational message.
     */
    public static function info(string $message): void
    {
        self::log('info', $message);
    }

    /**
     * Log a warning message.
     */
    public static function warning(string $message): void
    {
        self::log('warning', $message);
    }

    /**
     * Log an error message.
     */
    public static function error(string $message): void
    {
        self::log('error', $message);
    }

    /**
     * Log an Exception detail with stack trace.
     */
    public static function exception(\Throwable $exception): void
    {
        $message = sprintf(
            "Exception: %s | Message: %s | File: %s:%d | Trace:%s%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL,
            $exception->getTraceAsString()
        );
        self::log('exception', $message);
    }
}

