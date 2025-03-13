<?php

declare(strict_types=1);

use Ace\Exception\Handler\ExceptionHandler;

$errorHandler = new ExceptionHandler($_ENV['APP_ENV'] ?? 'production');