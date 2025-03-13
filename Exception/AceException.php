<?php

declare(strict_types=1);

/**
 * ==============================================
 * ================         =====================
 * AceException Class.
 * ================         =====================
 * ==============================================
 */

namespace Ace\Exception;

use ErrorException;

class AceException extends ErrorException
{
    public function getFormattedMessage(): string
    {
        return sprintf(
            "[%s] %s (Line %s in %s)",
            $this->getSeverityAsString(),
            $this->getMessage(),
            $this->getLine(),
            $this->getFile()
        );
    }

    public function getSeverityAsString(): string
    {
        switch ($this->getSeverity()) {
            case E_ERROR: return 'E_ERROR';
            case E_WARNING: return 'E_WARNING';
            case E_NOTICE: return 'E_NOTICE';
            // Add other error types as needed
            default: return 'UNKNOWN_ERROR';
        }
    }
}
