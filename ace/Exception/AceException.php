<?php

declare(strict_types=1);

/**
 * ==============================================
 * ================         =====================
 * AceException Class.
 * ================         =====================
 * ==============================================
 */

namespace Ace\ace\Exception;

use Exception;
use Throwable;

class AceException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
