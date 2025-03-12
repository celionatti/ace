<?php

declare(strict_types=1);

use Ace\ace\Exception\Handler\ExceptionHandler;

$errorHandler = new ExceptionHandler($_ENV['APP_ENV'] ?? 'production');