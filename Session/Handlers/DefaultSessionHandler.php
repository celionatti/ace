<?php

declare(strict_types=1);

/**
 * ==================================
 * DefaultSessionHandler ============
 * ==================================
 */

namespace Ace\Session\Handlers;

use Ace\Session\SessionHandler;

class DefaultSessionHandler extends SessionHandler
{
    public function __construct()
    {
        $this->start();
    }
}